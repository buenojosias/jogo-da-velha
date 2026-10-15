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

class UltimateStarterEngine implements Engine
{
    public function __construct(private int $choice) {}

    public function generate(): string
    {
        return pack('V', $this->choice);
    }
}

function openPublicUltimate(int $choice = 0): string
{
    app()->instance(Randomizer::class, new Randomizer(new UltimateStarterEngine($choice)));
    test()->get(route('ultimate'))->assertOk();

    return session('ultimate.active_match_id');
}

/** @param list<list<string>> $boards */
function setPublicUltimate(string $id, array $boards, ?int $target, PlayerMark $current): void
{
    $store = app(SessionMatchStore::class);
    $old = $store->find($id);
    $store->save($id, new MatchState($old->modality, $old->participants, MatchStatus::InProgress, $current,
        ['boards' => $boards, 'target_board' => $target]));
}

test('creates and resumes a public match with a server-chosen starter and no legacy script', function (int $choice, PlayerMark $starter) {
    $id = openPublicUltimate($choice);
    $response = $this->get(route('ultimate'));

    $response->assertOk()->assertViewIs('ultimate-match')->assertSee('Ultimate')->assertDontSee('js/game.js');
    expect(session('ultimate.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->find($id)->data)->toBe(['boards' => array_fill(0, 9, array_fill(0, 9, '')), 'target_board' => null]);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('persists a directed turn and rejects a wrong subboard without changing the official state', function () {
    $id = openPublicUltimate();
    $component = Livewire::test('ultimate-match', ['matchId' => $id]);

    $component->call('place', 0, 4)->assertSee('Subtabuleiro obrigatório: 5')->assertDispatched('ultimate-state-updated', focus: 4);
    $state = app(SessionMatchStore::class)->find($id);
    expect($state->data['target_board'])->toBe(4);
    expect($state->data['boards'][0][4])->toBe('x');
    $component->call('place', 0, 1)->assertSee('subtabuleiro obrigatório indicado');
    expect(app(SessionMatchStore::class)->find($id)->data)->toBe($state->data);
    $this->get(route('ultimate'))->assertSee('Subtabuleiro obrigatório: 5');
});

test('returns to manual choice when the destination subboard closes', function () {
    $id = openPublicUltimate();
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    $boards[0][1] = 'x';
    $boards[0][2] = 'x';
    $boards[1][0] = 'o';
    $boards[2][0] = 'o';
    setPublicUltimate($id, $boards, 0, PlayerMark::X);

    Livewire::test('ultimate-match', ['matchId' => $id])->call('place', 0, 0)
        ->assertSee('Escolha qualquer subtabuleiro disponível.')
        ->assertDispatched('ultimate-state-updated', focus: null);

    $state = app(SessionMatchStore::class)->find($id);
    expect($state->data['target_board'])->toBeNull();
    expect($state->currentPlayer)->toBe(PlayerMark::O);
});

test('shows nine directly playable subboards with accessible available states', function () {
    $id = openPublicUltimate();
    $html = Livewire::test('ultimate-match', ['matchId' => $id])->html();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    $overviewBoards = $xpath->query('//*[@data-testid="ultimate-overview"]/*[@data-testid]');

    expect($overviewBoards)->toHaveCount(9);
    expect($xpath->query('//*[@data-testid="ultimate-overview"]')->item(0)->getAttribute('aria-label'))->toContain('jogue diretamente');
    expect($xpath->query('//*[@data-testid="ultimate-overview"]//button'))->toHaveCount(81);
    expect($xpath->query('//*[@data-testid="ultimate-focus-region"]'))->toHaveCount(0);
    expect($html)->toContain('selectedBoard: null');
    foreach ($overviewBoards as $index => $board) {
        expect($board->getAttribute('aria-label'))->toBe('Subtabuleiro '.($index + 1).', disponível para jogar');
        expect($board->getAttribute('class'))->toContain('border-ds-success');
        expect($board->getAttribute('data-available'))->toBe('true');
        expect($xpath->query('.//button[not(@disabled)]', $board))->toHaveCount(9);
    }
});

test('distinguishes mandatory waiting conquered and drawn overview boards without captions', function () {
    $id = openPublicUltimate();
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    foreach ([0, 1, 2] as $cell) {
        $boards[0][$cell] = 'x';
        $boards[1][$cell] = 'o';
    }
    $boards[2] = str_split('xoxxoooxx');
    $boards[3][4] = 'o';
    setPublicUltimate($id, $boards, 4, PlayerMark::X);

    $html = Livewire::test('ultimate-match', ['matchId' => $id])->html();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    $expected = [
        0 => ['conquistado pelo jogador X', 'border-ds-x', ''],
        1 => ['conquistado pelo jogador O', 'border-ds-o', ''],
        2 => ['empatado', 'border-dotted', '='],
        3 => ['aguardando, indisponível neste turno', 'border-dashed', ''],
        4 => ['obrigatório e disponível para jogar', 'border-ds-success', ''],
    ];

    foreach ($expected as $index => [$status, $border, $indicator]) {
        $board = $xpath->query('//*[@data-testid="ultimate-sub-board-'.$index.'"]')->item(0);
        expect($board->getAttribute('aria-label'))->toBe('Subtabuleiro '.($index + 1).', '.$status);
        expect($board->getAttribute('class'))->toContain($border);
        expect($board->getAttribute('data-available'))->toBe($index === 4 ? 'true' : 'false');
        expect($xpath->query('.//button[not(@disabled)]', $board))->toHaveCount($index === 4 ? 9 : 0);
        expect($xpath->query('.//span[@aria-hidden="true" and contains(@class, "right-0.5")]', $board)->item(0)?->textContent ?? '')->toBe($indicator);
        $overlay = $xpath->query('.//span[@data-winner-overlay]', $board)->item(0);
        expect(trim($overlay?->textContent ?? ''))->toBe($index === 0 ? 'X' : ($index === 1 ? 'O' : ''));
        if ($overlay !== null) {
            expect($overlay->getAttribute('class'))->toContain('pointer-events-none', 'inset-[2px]', 'bg-ds-'.($index === 0 ? 'x' : 'o').'/15');
            foreach ($xpath->query('.//button', $board) as $cell) {
                expect($cell->getAttribute('class'))->toContain('border-ds-board-border')->not->toContain('ring-2');
            }
        }
        expect($board->textContent)->not->toContain('Livre', 'Aguarda', 'Empate', 'Venceu');
    }

    $mandatoryBoard = $xpath->query('//*[@data-testid="ultimate-sub-board-4"]')->item(0);
    expect($mandatoryBoard->getAttribute('class'))->toContain('border-2', 'bg-ds-surface');
    expect($mandatoryBoard->hasAttribute('x-bind:class'))->toBeFalse();
    expect($mandatoryBoard->getAttribute('x-bind:data-selected'))->toContain('selectedBoard === 4');
    expect($html)->toContain('selectedBoard: 4');
});

test('awards a global win once, then preserves score and alternates the starter on the next round', function () {
    $id = openPublicUltimate();
    $store = app(SessionMatchStore::class);
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    foreach ([0, 1] as $index) {
        foreach ([0, 1, 2] as $cell) {
            $boards[$index][$cell] = 'x';
        }
    }
    $boards[2][0] = 'x';
    $boards[2][1] = 'x';
    foreach ([3 => [0, 2], 4 => [0, 2], 5 => [1, 3], 6 => [1, 2]] as $boardIndex => $cells) {
        foreach ($cells as $cell) {
            $boards[$boardIndex][$cell] = 'o';
        }
    }
    setPublicUltimate($id, $boards, 2, PlayerMark::X);
    $starter = $store->startingPlayer($id);
    $component = Livewire::test('ultimate-match', ['matchId' => $id]);

    $component->call('place', 2, 2)->assertSee('Jogador X venceu!')->assertSee('rodada encerrada, indisponível')->assertDontSee('Escolha um subtabuleiro disponível na visão geral para continuar.')->assertDispatched('ultimate-round-finished');
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$component->html());
    expect((new DOMXPath($document))->query('//*[@data-testid="ultimate-overview"]//button[@disabled]'))->toHaveCount(81);
    expect($store->score($id)['x'])->toBe(1);
    expect($store->find($id)->winner)->toBe(PlayerMark::X);
    $component->call('place', 8, 8)->assertSee('A rodada terminou');
    expect($store->score($id)['x'])->toBe(1);
    $component->call('restart')->assertDispatched('ultimate-state-updated', focus: null);
    expect($store->find($id)->data['boards'])->toBe(array_fill(0, 9, array_fill(0, 9, '')));
    expect($store->startingPlayer($id))->toBe($starter === PlayerMark::X ? PlayerMark::O : PlayerMark::X);
    expect($store->score($id)['x'])->toBe(1);
});

test('counts a global draw only after the last open subboard closes', function () {
    $id = openPublicUltimate();
    $xHeavy = str_split('xoxxoooxx');
    $oHeavy = str_split('oxooxxxoo');
    $boards = array_merge(array_fill(0, 4, $xHeavy), array_fill(0, 4, $oHeavy), [$xHeavy]);
    $boards[8][8] = '';
    setPublicUltimate($id, $boards, 8, PlayerMark::X);

    Livewire::test('ultimate-match', ['matchId' => $id])->call('place', 8, 8)
        ->assertSee('Rodada empatada!')->assertDispatched('ultimate-round-finished');
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::Drawn);
    expect(app(SessionMatchStore::class)->score($id)['draws'])->toBe(1);
});

test('new match resets the score and makes old public tabs inactive', function () {
    $id = openPublicUltimate();
    $stale = Livewire::test('ultimate-match', ['matchId' => $id]);
    $stale->call('place', 0, 1);
    Livewire::test('ultimate-match', ['matchId' => $id])->call('newMatch')->assertRedirect(route('ultimate'));
    $newId = session('ultimate.active_match_id');

    expect($newId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    Livewire::test('ultimate-match', ['matchId' => $id])->assertNotFound();
    $stale->call('place', 1, 0)->assertNotFound();
});

test('does not accept a client replacement for the protected match identifier', function () {
    $id = openPublicUltimate();

    $otherSession = new Store('ultimate-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();

    expect(fn () => Livewire::test('ultimate-match', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['boards' => [], 'target_board' => 2, 'currentPlayer' => 'o', 'winner' => 'x', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('ultimate-match', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
    expect(session('ultimate.active_match_id'))->toBe($id);
});

test('replaces a corrupted active record without disturbing another modality', function () {
    $this->get(route('3-basic'))->assertOk();
    $classicId = session('classic.active_match_id');
    $id = openPublicUltimate();
    $stored = session('experimental_matches.'.$id);
    $stored['state']['data']['target_board'] = 20;
    session()->put('experimental_matches.'.$id, $stored);

    $this->get(route('ultimate'))->assertOk();
    expect(session('ultimate.active_match_id'))->not->toBe($id);
    expect(session('classic.active_match_id'))->toBe($classicId);
});

test('rejects a session subboard that claims simultaneous X and O lines', function () {
    $id = openPublicUltimate();
    $stored = session('experimental_matches.'.$id);
    $stored['state']['data']['boards'][0] = str_split('xxxooo   ');
    session()->put('experimental_matches.'.$id, $stored);

    $this->get(route('ultimate'))->assertOk();

    expect(session('ultimate.active_match_id'))->not->toBe($id);
});

test('keeps a separate active match for each of the fourteen public modalities', function () {
    $routeNames = ['3-basic', '4x3-basic', '3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation', '4x3-block', '4x3-hidden', '5x5-2-lines', '5-full', 'dice', 'crazy', 'ultimate'];
    foreach ($routeNames as $name) {
        $this->get(route($name))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'movement4x4.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id', 'block4x3.active_match_id', 'hidden4x3.active_match_id', 'twoLines5x5.active_match_id', 'full5x5.active_match_id', 'dice.active_match_id', 'crazy.active_match_id', 'ultimate.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);

    expect(array_unique($ids))->toHaveCount(14);
    expect(app(SessionMatchStore::class)->find($ids[13])->modality->key)->toBe('ultimate');
});
