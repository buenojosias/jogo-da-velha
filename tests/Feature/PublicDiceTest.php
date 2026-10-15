<?php

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine;
use Random\Randomizer;

class DiceFeatureEngine implements Engine
{
    public function __construct(private int $face) {}

    public function generate(): string
    {
        return pack('V', $this->face - 1);
    }
}

function openPublicDice(int $face = 1): string
{
    app()->instance(Randomizer::class, new Randomizer(new DiceFeatureEngine($face)));
    test()->get(route('dice'))->assertOk();

    return session('dice.active_match_id');
}

/** @param array{list<string>, list<string>} $boards */
function preparePublicDice(string $matchId, array $boards, PlayerMark $current, ?int $pending, ?int $last): void
{
    $store = app(SessionMatchStore::class);
    $initial = $store->find($matchId);
    $store->save($matchId, new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $current,
        ['boards' => $boards, 'pending_roll' => $pending, 'last_roll' => $last]));
}

test('creates and resumes an independent public match with two boards and either starter', function (int $face, PlayerMark $starter) {
    $id = openPublicDice($face);
    $response = $this->get(route('dice'));

    $response->assertOk()->assertViewIs('dice-match')->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('Tabuleiro Esquerdo')->assertSee('Tabuleiro Direito')->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(18);
    expect(session('dice.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [1, PlayerMark::X], 'O' => [2, PlayerMark::O]]);

test('renders six flat faces with one through six separate pips', function () {
    $id = openPublicDice();
    $html = Livewire::test('dice-match', ['matchId' => $id])->html();
    $faces = preg_split('/class="dice-flat-face"/', $html);

    foreach (range(1, 6) as $face) {
        expect(substr_count($faces[$face] ?? '', 'class="dice-pip '))->toBe($face);
    }
    expect($html)->not->toContain('⚀', '⚁', '⚂', '⚃', '⚄', '⚅');
});

test('keeps the rolled column across reloads and rejects misplaced actions', function () {
    $id = openPublicDice(2);
    $component = Livewire::test('dice-match', ['matchId' => $id]);
    $component->call('place', 0, 1)->assertSee('Role o dado antes');
    $component->call('roll')->assertReturned(2)->assertSee('Dado 2: escolha uma casa vazia na coluna 2 do tabuleiro esquerdo')
        ->assertDispatched('dice-roll-availability-updated', blocked: true);

    expect(app(SessionMatchStore::class)->find($id)->data['pending_roll'])->toBe(2);
    $this->get(route('dice'))->assertOk()->assertSee('Dado 2: escolha');
    $component->call('roll')->assertReturned(null)->assertSee('Escolha uma casa na coluna sorteada antes de rolar novamente');
    $component->call('place', 1, 1)->assertSee('Escolha uma casa na coluna indicada');
    $component->call('place', 0, 0)->assertSee('Escolha uma casa na coluna indicada');
    expect(app(SessionMatchStore::class)->find($id)->data['boards'])->toBe([array_fill(0, 9, ''), array_fill(0, 9, '')]);
    $component->call('place', 0, 4)->assertSee('Vez do jogador X')
        ->assertDispatched('dice-roll-availability-updated', blocked: false);
    expect(app(SessionMatchStore::class)->find($id)->data['boards'][0][4])->toBe('o');
    expect(app(SessionMatchStore::class)->find($id)->data['pending_roll'])->toBeNull();
});

test('skips a full rolled column and keeps both boards unchanged', function () {
    $id = openPublicDice(4);
    $boards = [array_fill(0, 9, ''), array_fill(0, 9, '')];
    $boards[1][0] = 'x';
    $boards[1][3] = 'o';
    $boards[1][6] = 'x';
    preparePublicDice($id, $boards, PlayerMark::O, null, null);

    Livewire::test('dice-match', ['matchId' => $id])->call('roll')->assertReturned(4)->assertSee('Coluna 4 cheia! Jogador O perdeu a vez.')
        ->assertDispatched('dice-roll-availability-updated', blocked: false);
    $state = app(SessionMatchStore::class)->find($id);
    expect($state->currentPlayer)->toBe(PlayerMark::X);
    expect($state->data['pending_roll'])->toBeNull();
    expect($state->data['last_roll'])->toBe(4);
    expect($state->data['boards'])->toBe($boards);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('awards a win on either board and preserves the score after the next round', function (int $face, int $board, PlayerMark $winner) {
    $id = openPublicDice($face);
    $boards = [array_fill(0, 9, ''), array_fill(0, 9, '')];
    $boards[$board][0] = $winner->value;
    $boards[$board][1] = $winner->value;
    preparePublicDice($id, $boards, $winner, $face, $face);

    $component = Livewire::test('dice-match', ['matchId' => $id])->call('place', $board, 2)
        ->assertSee('Jogador '.$winner->name.' venceu!')->assertDispatched('dice-round-finished');
    expect(app(SessionMatchStore::class)->score($id)[$winner->value])->toBe(1);
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(3);
    $component->call('place', $board, 5)->assertSee('A rodada terminou.');
    expect(app(SessionMatchStore::class)->score($id)[$winner->value])->toBe(1);
    $component->call('restart')->assertDispatched('dice-roll-availability-updated', blocked: false)
        ->assertDispatched('dice-roll-reset');
    expect(app(SessionMatchStore::class)->find($id)->data['boards'])->toBe([array_fill(0, 9, ''), array_fill(0, 9, '')]);
    expect(app(SessionMatchStore::class)->find($id)->data['pending_roll'])->toBeNull();
    expect(app(SessionMatchStore::class)->score($id)[$winner->value])->toBe(1);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($winner === PlayerMark::X ? PlayerMark::O : PlayerMark::X);
})->with(['left X' => [3, 0, PlayerMark::X], 'right O' => [6, 1, PlayerMark::O]]);

test('draws only after both boards fill without a winner', function () {
    $id = openPublicDice(6);
    $drawBoard = str_split('xoxxoooxx');
    $boards = [$drawBoard, $drawBoard];
    $boards[1][8] = '';
    preparePublicDice($id, $boards, PlayerMark::X, 6, 6);

    Livewire::test('dice-match', ['matchId' => $id])->call('place', 1, 8)
        ->assertSee('Empate nos dois tabuleiros!')->assertDispatched('dice-round-finished');
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::Drawn);
    expect(app(SessionMatchStore::class)->score($id)['draws'])->toBe(1);
});

test('restarts a pending roll without resetting the match score and starts a new match on request', function () {
    $id = openPublicDice(1);
    $component = Livewire::test('dice-match', ['matchId' => $id])->call('roll');
    expect(app(SessionMatchStore::class)->find($id)->data['pending_roll'])->toBe(1);

    $component->call('restart')->assertDispatched('dice-roll-availability-updated', blocked: false);
    expect(app(SessionMatchStore::class)->find($id)->data['pending_roll'])->toBeNull();
    expect(app(SessionMatchStore::class)->find($id)->data['last_roll'])->toBeNull();
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe(PlayerMark::O);
    $stale = Livewire::test('dice-match', ['matchId' => $id]);
    $component->call('newMatch')->assertRedirect(route('dice'));
    $nextId = session('dice.active_match_id');
    expect($nextId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($nextId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    $stale->call('roll')->assertNotFound();
});

test('rejects invalid session records and isolates the twelve active modalities', function () {
    $routes = ['3-basic', '4x3-basic', '3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation', '4x3-block', '4x3-hidden', '5x5-2-lines', '5-full', 'dice'];
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'movement4x4.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id', 'block4x3.active_match_id', 'hidden4x3.active_match_id', 'twoLines5x5.active_match_id', 'full5x5.active_match_id', 'dice.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);
    expect(array_unique($ids))->toHaveCount(12);
    $store = app(SessionMatchStore::class);
    $before = array_map(fn (string $id): array => $store->find($id)->data, $ids);
    Livewire::test('dice-match', ['matchId' => $ids[11]])->call('roll');
    foreach (range(0, 10) as $index) {
        expect($store->find($ids[$index])->data)->toBe($before[$index]);
    }
    session()->put('dice.active_match_id', $ids[0]);
    $this->get(route('dice'))->assertOk();
    expect(session('dice.active_match_id'))->not->toBe($ids[0]);
    $corrupt = session('dice.active_match_id');
    session()->put('experimental_matches.'.$corrupt.'.state.data.boards', [array_fill(0, 9, '')]);
    $this->get(route('dice'))->assertOk();
    expect(session('dice.active_match_id'))->not->toBe($corrupt);
    $badPending = session('dice.active_match_id');
    session()->put('experimental_matches.'.$badPending.'.state.data.pending_roll', 7);
    $this->get(route('dice'))->assertOk();
    expect(session('dice.active_match_id'))->not->toBe($badPending);
});

test('keeps official boards, turn, roll and score server-side', function () {
    $id = openPublicDice();
    $otherSession = new Store('dice-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('dice-match', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['boards' => [], 'pending_roll' => 6, 'last_roll' => 6, 'currentPlayer' => 'o', 'winner' => 'x', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('dice-match', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});
