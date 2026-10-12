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

function openPublicFull(int $seed = 0): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    test()->get(route('5-full'))->assertOk();

    return session('full5x5.active_match_id');
}

/** @param list<int> $cells */
function playPublicFull(string $matchId, array $cells): Testable
{
    $component = Livewire::test('full-5x5', ['matchId' => $matchId]);
    foreach ($cells as $cell) {
        $component->call('place', $cell);
    }

    return $component;
}

/** @return list<int> */
function fullMoves(string $firstTwentyFour, PlayerMark $starter): array
{
    $marks = str_split($firstTwentyFour);
    $firstCells = array_keys($marks, $starter->value, true);
    $otherCells = array_keys($marks, $starter === PlayerMark::X ? 'o' : 'x', true);
    $moves = [];
    for ($turn = 0; $turn < 24; $turn++) {
        $moves[] = $turn % 2 === 0 ? array_shift($firstCells) : array_shift($otherCells);
    }

    return $moves;
}

test('creates and resumes an independent public match with either starter', function (int $seed, PlayerMark $starter) {
    $id = openPublicFull($seed);
    $response = $this->get(route('5-full'));

    $response->assertOk()->assertViewIs('full-5x5')->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('Pontos X')->assertSee('Pontos O')->assertSee('25 casas')->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(25);
    expect(session('full5x5.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('updates round points without awarding a match before the board fills', function () {
    $id = openPublicFull();
    $component = playPublicFull($id, [0, 5, 1, 6, 2, 7, 3, 8, 4]);

    $component->assertSee('10')->assertSee('6');
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::InProgress);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(0);
    $before = app(SessionMatchStore::class)->find($id);
    $component->call('place', 0)->assertSee('Esta casa já está ocupada.');
    expect(app(SessionMatchStore::class)->find($id)->data)->toBe($before->data);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('records a final win only once, resumes it and preserves match score on restart', function () {
    $id = openPublicFull();
    $component = playPublicFull($id, fullMoves('ooxxoxoooxoxxoxoxooxxxxo', PlayerMark::X));
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::InProgress);

    $component->call('place', 24)->assertSee('Jogador X venceu!')->assertDispatched('full-board-round-finished');
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
    $component->call('place', 0)->assertSee('A rodada terminou.');
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $this->get(route('5-full'))->assertOk()->assertSee('Pontos finais: X 15 · O 12');
    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 25, ''));
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(session('full5x5.active_match_id'))->toBe($id);
});

test('records an O win and a draw by final points', function (string $board, PlayerMark $starter, ?PlayerMark $winner) {
    $id = openPublicFull($starter === PlayerMark::X ? 0 : 1);
    $component = playPublicFull($id, fullMoves($board, $starter));
    $component->call('place', 24)->assertDispatched('full-board-round-finished');

    $state = app(SessionMatchStore::class)->find($id);
    expect($state->winner)->toBe($winner);
    expect($state->status)->toBe($winner === null ? MatchStatus::Drawn : MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($id))->toBe([
        'x' => $winner === PlayerMark::X ? 1 : 0,
        'o' => $winner === PlayerMark::O ? 1 : 0,
        'draws' => $winner === null ? 1 : 0,
    ]);
})->with([
    'O victory' => ['xoxoxooxxxoooxoooxoxxoxx', PlayerMark::O, PlayerMark::O],
    'equal points' => ['ooxxoxoooxoxxoxoxooxxxxo', PlayerMark::O, null],
]);

test('starts a new zero-score match and refuses stale tabs', function () {
    $id = openPublicFull();
    $stalePlace = Livewire::test('full-5x5', ['matchId' => $id]);
    $staleRestart = Livewire::test('full-5x5', ['matchId' => $id]);
    $staleNewMatch = Livewire::test('full-5x5', ['matchId' => $id]);
    playPublicFull($id, fullMoves('ooxxoxoooxoxxoxoxooxxxxo', PlayerMark::X))
        ->call('place', 24)->call('newMatch')->assertRedirect(route('5-full'));

    $nextId = session('full5x5.active_match_id');
    expect($nextId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($nextId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $stalePlace->call('place', 0)->assertNotFound();
    $staleRestart->call('restart')->assertNotFound();
    $staleNewMatch->call('newMatch')->assertNotFound();
});

test('isolates the eleven active modalities and replaces invalid full-board pointers', function () {
    $routes = ['3-basic', '4x3-basic', '3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation', '4x3-block', '4x3-hidden', '5x5-2-lines', '5-full'];
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'movement4x4.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id', 'block4x3.active_match_id', 'hidden4x3.active_match_id', 'twoLines5x5.active_match_id', 'full5x5.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);
    expect(array_unique($ids))->toHaveCount(11);
    $store = app(SessionMatchStore::class);
    $before = array_map(fn (string $id): array => $store->find($id)->data, $ids);
    Livewire::test('full-5x5', ['matchId' => $ids[10]])->call('place', 0);
    foreach (range(0, 9) as $index) {
        expect($store->find($ids[$index])->data)->toBe($before[$index]);
    }
    session()->put('full5x5.active_match_id', $ids[9]);
    $this->get(route('5-full'))->assertOk();
    expect(session('full5x5.active_match_id'))->not->toBe($ids[9]);
    $corruptId = session('full5x5.active_match_id');
    session()->put('experimental_matches.'.$corruptId.'.state.data.board', array_fill(0, 16, ''));
    $this->get(route('5-full'))->assertOk();
    expect(session('full5x5.active_match_id'))->not->toBe($corruptId);
    $wrongResultId = session('full5x5.active_match_id');
    session()->put('experimental_matches.'.$wrongResultId.'.state.status', 'completed');
    session()->put('experimental_matches.'.$wrongResultId.'.state.current_player', null);
    session()->put('experimental_matches.'.$wrongResultId.'.state.winner', 'x');
    $this->get(route('5-full'))->assertOk();
    expect(session('full5x5.active_match_id'))->not->toBe($wrongResultId);
});

test('locks match identity and computed points against client writes or another session', function () {
    $id = openPublicFull();
    $otherSession = new Store('full-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('full-5x5', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => [], 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99], 'roundPoints' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('full-5x5', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});
