<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

function openPublicBlock(int $seed = 0): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    test()->get(route('4x3-block'))->assertOk();

    return session('block4x3.active_match_id');
}

test('creates and resumes a public blocking match with either server-drawn starter', function (int $seed, PlayerMark $starter) {
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    $response = $this->get(route('4x3-block', ['starter' => 'x']));

    $response->assertOk()->assertViewIs('block-4x3')->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('data-testid="new-match-button"', false)->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(16);
    $id = session('block4x3.active_match_id');
    expect($id)->toBeString()->toHaveLength(40);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    $this->get(route('4x3-block'))->assertOk();
    expect(session('block4x3.active_match_id'))->toBe($id);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('accepts a blocked line, emits feedback, and keeps the same board after reload', function () {
    $id = openPublicBlock();
    $component = Livewire::test('block-4x3', ['matchId' => $id]);
    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Vez do jogador O')->assertDispatched('block-line', cells: [3]);
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::InProgress);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(0);
    $this->get(route('4x3-block'))->assertOk()->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['board'][2])->toBe('x');
});

test('shows an unblocked win and scores it only once across the next round', function () {
    $id = openPublicBlock();
    $component = Livewire::test('block-4x3', ['matchId' => $id]);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }
    $component->assertSee('Jogador X venceu!')->assertDispatched('block-round-finished');
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(3);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $component->call('place', 3)->assertSee('A rodada terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
});

test('scores a draw and preserves it on restart', function () {
    $id = openPublicBlock();
    $component = Livewire::test('block-4x3', ['matchId' => $id]);
    foreach ([2, 5, 12, 7, 0, 8, 10, 9, 13, 3, 1, 14, 11, 15, 4, 6] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Empate!')->assertDispatched('block-round-finished');
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 1]);
    $component->call('restart');
    expect(app(SessionMatchStore::class)->score($id)['draws'])->toBe(1);
});

test('starts a fresh match with zero score and rejects actions from stale tabs', function () {
    $id = openPublicBlock();
    $stalePlace = Livewire::test('block-4x3', ['matchId' => $id]);
    $staleRestart = Livewire::test('block-4x3', ['matchId' => $id]);
    $staleNewMatch = Livewire::test('block-4x3', ['matchId' => $id]);
    $component = Livewire::test('block-4x3', ['matchId' => $id]);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }
    app()->instance(Randomizer::class, new Randomizer(new Mt19937(1)));
    $component->call('newMatch')->assertRedirect(route('4x3-block'));

    $nextId = session('block4x3.active_match_id');
    expect($nextId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($nextId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($nextId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $stalePlace->call('place', 0)->assertNotFound();
    $staleRestart->call('restart')->assertNotFound();
    $staleNewMatch->call('newMatch')->assertNotFound();
});

test('keeps all eight public modalities independent', function () {
    $routes = ['3-basic', '4x3-basic', '3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation', '4x3-block'];
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'movement4x4.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id', 'block4x3.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);
    expect(array_unique($ids))->toHaveCount(8);
    $store = app(SessionMatchStore::class);
    $before = array_map(fn (string $id): array => $store->find($id)->data['board'], $ids);
    Livewire::test('block-4x3', ['matchId' => $ids[7]])->call('place', 0);
    expect($store->find($ids[7])->data['board'][0])->not->toBe('');
    foreach (range(0, 6) as $index) {
        expect($store->find($ids[$index])->data['board'])->toBe($before[$index]);
    }
    expect(array_map(fn (string $key): string => session($key), $keys))->toBe($ids);
});

test('replaces invalid, expired, corrupt and wrong-modality active pointers', function () {
    openPublicBlock();
    $this->get(route('3-basic'))->assertOk();
    $otherId = session('classic.active_match_id');
    foreach (['invalid', str_repeat('A', 40), $otherId] as $pointer) {
        session()->put('block4x3.active_match_id', $pointer);
        $this->get(route('4x3-block'))->assertOk();
        expect(session('block4x3.active_match_id'))->not->toBe($pointer);
    }
    $corruptId = session('block4x3.active_match_id');
    session()->put('experimental_matches.'.$corruptId.'.state.data.board', array_fill(0, 16, 'invalid'));
    $this->get(route('4x3-block'))->assertOk();
    expect(session('block4x3.active_match_id'))->not->toBe($corruptId);
    $imbalancedId = session('block4x3.active_match_id');
    session()->put('experimental_matches.'.$imbalancedId.'.state.data.board', array_fill(0, 16, 'x'));
    $this->get(route('4x3-block'))->assertOk();
    expect(session('block4x3.active_match_id'))->not->toBe($imbalancedId);
});

test('isolates sessions and locks the match identity and official state', function () {
    $id = openPublicBlock();
    $otherSession = new Store('block-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('block-4x3', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => [], 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('block-4x3', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});
