<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

function openPublicTwoLines(int $seed = 0): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    test()->get(route('5x5-2-lines'))->assertOk();

    return session('twoLines5x5.active_match_id');
}

/** @param list<int> $cells */
function playPublicTwoLines(string $matchId, array $cells): Testable
{
    $component = Livewire::test('two-lines-5x5', ['matchId' => $matchId]);
    foreach ($cells as $cell) {
        $component->call('place', $cell);
    }

    return $component;
}

test('creates and resumes a public match with either server-drawn starter', function (int $seed, PlayerMark $starter) {
    $id = openPublicTwoLines($seed);
    $response = $this->get(route('5x5-2-lines'));

    $response->assertOk()->assertViewIs('two-lines-5x5')->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('Forme duas linhas independentes')->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(25);
    expect(session('twoLines5x5.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('keeps one line in progress and rejects an occupied cell', function () {
    $id = openPublicTwoLines();
    $component = playPublicTwoLines($id, [0, 5, 1, 6, 2]);
    $before = app(SessionMatchStore::class)->find($id);

    $component->assertSee('Vez do jogador O')->call('place', 0)->assertSee('Esta casa já está ocupada.');
    expect($before->status)->toBe(MatchStatus::InProgress);
    expect(app(SessionMatchStore::class)->find($id)->data)->toBe($before->data);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(0);
});

test('wins on two independent lines and highlights every contributing cell', function (int $seed, PlayerMark $winner) {
    $id = openPublicTwoLines($seed);
    $component = playPublicTwoLines($id, [0, 5, 1, 6, 2, 9, 10, 14, 11, 19, 12]);

    $component->assertSee('Jogador '.$winner->name.' venceu!')->assertDispatched('two-lines-round-finished');
    expect(app(SessionMatchStore::class)->find($id)->winner)->toBe($winner);
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(6);
    expect(app(SessionMatchStore::class)->score($id)[$winner->value])->toBe(1);
    $component->call('place', 20)->assertSee('A rodada terminou.');
    expect(app(SessionMatchStore::class)->score($id)[$winner->value])->toBe(1);
    $this->get(route('5x5-2-lines'))->assertOk()->assertSee('data-winning-cell="true"', false);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('draws on a full board without two lines and preserves the draw on restart', function () {
    $id = openPublicTwoLines();
    $marks = str_split('ooxxoxooxxoxxooxooxxoxxxo');
    $xCells = array_keys($marks, 'x', true);
    $oCells = array_keys($marks, 'o', true);
    $moves = [];
    for ($turn = 0; $turn < 25; $turn++) {
        $moves[] = $turn % 2 === 0 ? array_shift($xCells) : array_shift($oCells);
    }

    $component = playPublicTwoLines($id, $moves);

    $component->assertSee('Rodada empatada!')->assertDispatched('two-lines-round-finished');
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 1]);
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(0);
    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->score($id)['draws'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 25, ''));
});

test('preserves score and alternates the initial player on the next round', function () {
    $id = openPublicTwoLines();
    $component = playPublicTwoLines($id, [0, 5, 1, 6, 2, 9, 10, 14, 11, 19, 12]);

    $component->call('restart')->assertSee('Vez do jogador O');
    expect(session('twoLines5x5.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 25, ''));
});

test('starts a new zero-score match and rejects actions from stale tabs', function () {
    $id = openPublicTwoLines();
    $stalePlace = Livewire::test('two-lines-5x5', ['matchId' => $id]);
    $staleRestart = Livewire::test('two-lines-5x5', ['matchId' => $id]);
    $staleNewMatch = Livewire::test('two-lines-5x5', ['matchId' => $id]);
    playPublicTwoLines($id, [0, 5, 1, 6, 2, 9, 10, 14, 11, 19, 12])
        ->call('newMatch')->assertRedirect(route('5x5-2-lines'));

    $nextId = session('twoLines5x5.active_match_id');
    expect($nextId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($nextId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $stalePlace->call('place', 20)->assertNotFound();
    $staleRestart->call('restart')->assertNotFound();
    $staleNewMatch->call('newMatch')->assertNotFound();
});

test('isolates all ten public modalities and replaces invalid active pointers', function () {
    $routes = ['3-basic', '4x3-basic', '3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation', '4x3-block', '4x3-hidden', '5x5-2-lines'];
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'movement4x4.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id', 'block4x3.active_match_id', 'hidden4x3.active_match_id', 'twoLines5x5.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);
    expect(array_unique($ids))->toHaveCount(10);
    $store = app(SessionMatchStore::class);
    $before = array_map(fn (string $id): array => $store->find($id)->data, $ids);
    Livewire::test('two-lines-5x5', ['matchId' => $ids[9]])->call('place', 0);
    foreach (range(0, 8) as $index) {
        expect($store->find($ids[$index])->data)->toBe($before[$index]);
    }
    session()->put('twoLines5x5.active_match_id', $ids[0]);
    $this->get(route('5x5-2-lines'))->assertOk();
    expect(session('twoLines5x5.active_match_id'))->not->toBe($ids[0]);
    $corruptId = session('twoLines5x5.active_match_id');
    session()->put('experimental_matches.'.$corruptId.'.state.data.board', array_fill(0, 16, ''));
    $this->get(route('5x5-2-lines'))->assertOk();
    expect(session('twoLines5x5.active_match_id'))->not->toBe($corruptId);
    $imbalancedId = session('twoLines5x5.active_match_id');
    session()->put('experimental_matches.'.$imbalancedId.'.state.data.board', array_fill(0, 25, 'x'));
    $this->get(route('5x5-2-lines'))->assertOk();
    expect(session('twoLines5x5.active_match_id'))->not->toBe($imbalancedId);
    $invalidResultId = session('twoLines5x5.active_match_id');
    session()->put('experimental_matches.'.$invalidResultId.'.state.status', 'completed');
    session()->put('experimental_matches.'.$invalidResultId.'.state.current_player', null);
    session()->put('experimental_matches.'.$invalidResultId.'.state.winner', 'x');
    $this->get(route('5x5-2-lines'))->assertOk();
    expect(session('twoLines5x5.active_match_id'))->not->toBe($invalidResultId);
});

test('locks identity, turn and score against browser manipulation and another session', function () {
    $id = openPublicTwoLines();
    $otherSession = new Store('two-lines-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('two-lines-5x5', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => [], 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('two-lines-5x5', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});
