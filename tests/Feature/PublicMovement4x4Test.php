<?php

use App\Games\PlayerMark;
use App\Games\Rules\Move4x4Rules;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

function publicMove4x4StartingWithX(): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    test()->get(route('4x4-move'))->assertOk();

    return session('movement4x4.active_match_id');
}

test('creates and resumes a public sixteen-cell game with either server-drawn starter', function (int $seed, PlayerMark $starter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    $response = $this->get(route('4x4-move', ['starting_player' => 'x']));
    $response->assertOk()->assertViewIs('experimental-move-4x4')->assertSee('Vez do jogador '.$starter->name)->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(16);
    $id = session('movement4x4.active_match_id');
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    $this->get(route('4x4-move'))->assertOk();
    expect(session('movement4x4.active_match_id'))->toBe($id);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('places eight pieces, moves freely, and resumes the moved board', function () {
    $id = publicMove4x4StartingWithX();
    $component = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 5, 1, 6, 2, 8, 4, 9] as $cell) {
        $component->call('place', $cell);
    }
    $component->assertSee('Selecione uma peça e depois uma casa vazia')->call('move', 4, 15)->assertSee('Vez do jogador O');
    $this->get(route('4x4-move'))->assertOk()->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['board'][15])->toBe('x');
});

test('shows four winning cells, scores once, and preserves score on the next round', function () {
    $id = publicMove4x4StartingWithX();
    $component = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 4, 1, 5, 2, 6, 3] as $cell) {
        $component->call('place', $cell);
    }
    $component->assertSee('Jogador X venceu!')->assertDispatched('movement-round-finished');
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(4);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $component->call('place', 15)->assertSee('A rodada terminou. Inicie uma nova rodada para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 16, ''));
});

test('wins during movement and highlights four winning cells', function () {
    $id = publicMove4x4StartingWithX();
    $component = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 5, 1, 6, 2, 8, 4, 9] as $cell) {
        $component->call('place', $cell);
    }
    $component->call('move', 4, 3)->assertSee('Jogador X venceu!');
    expect(app(Move4x4Rules::class)->winningLine(app(SessionMatchStore::class)->find($id)))->toBe([0, 1, 2, 3]);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
});

test('new match resets only its own score and stale tabs cannot act', function () {
    $id = publicMove4x4StartingWithX();
    foreach (['3-basic', '4x3-basic', '3-move', '4x3-move', '3-rotation', '4x3-rotation'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id'];
    $before = array_map(fn (string $key): string => session($key), $keys);
    $stale = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    $staleRestart = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    $staleNewMatch = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    $fresh = Livewire::test('experimental-move-4x4', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 4, 1, 5, 2, 6, 3] as $cell) {
        $fresh->call('place', $cell);
    }
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $fresh->call('newMatch')->assertRedirect(route('4x4-move'));
    $newId = session('movement4x4.active_match_id');
    expect($newId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(array_map(fn (string $key): string => session($key), $keys))->toBe($before);
    $stale->call('place', 0)->assertNotFound();
    $staleRestart->call('restart')->assertNotFound();
    $staleNewMatch->call('newMatch')->assertNotFound();
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 16, ''));
});

test('rejects corrupt active records and wrong-modality pointers', function () {
    publicMove4x4StartingWithX();
    $this->get(route('3-move'))->assertOk();
    $other = session('movement3x3.active_match_id');
    $store = app(SessionMatchStore::class);
    expect(fn () => $store->save($other, $store->find(session('movement4x4.active_match_id'))))->toThrow(InvalidArgumentException::class);
    session()->put('movement4x4.active_match_id', $other);
    $this->get(route('4x4-move'))->assertOk();
    expect(session('movement4x4.active_match_id'))->not->toBe($other);
    $corrupt = session('movement4x4.active_match_id');
    session()->put('experimental_matches.'.$corrupt.'.state.data.board', array_fill(0, 16, 'x'));
    $this->get(route('4x4-move'))->assertOk();
    expect(session('movement4x4.active_match_id'))->not->toBe($corrupt);
});

test('keeps other sessions out and locks public match identity', function () {
    $id = publicMove4x4StartingWithX();
    $otherSession = new Store('movement4x4-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('experimental-move-4x4', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
