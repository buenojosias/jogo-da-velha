<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\Move4x3Rules;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

function publicMove4StartingWithX(TestCase $test): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $test->get(route('4x3-move'))->assertOk();

    return session('movement4x3.active_match_id');
}

test('creates a public sixteen-cell match with either server-drawn starter', function (int $seed, PlayerMark $starter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    $response = $this->get(route('4x3-move', ['starting_player' => $starter === PlayerMark::X ? 'o' : 'x']));

    $response->assertOk()->assertViewIs('experimental-move-4x3')
        ->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('data-testid="new-match-button"', false)
        ->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(16);
    $id = session('movement4x3.active_match_id');
    expect($id)->toBeString()->toHaveLength(40);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('resumes placement and movement without redrawing', function () {
    $id = publicMove4StartingWithX($this);
    $component = Livewire::test('experimental-move-4x3', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 1, 2] as $cell) {
        $component->call('place', $cell);
    }
    $this->get(route('4x3-move'))->assertOk()->assertSee('Coloque uma peça');
    expect(session('movement4x3.active_match_id'))->toBe($id);
    foreach ([3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }
    $component->assertSee('Selecione uma peça e depois uma casa vazia')
        ->call('move', 0, 15)->assertSee('Vez do jogador O');
    $this->get(route('4x3-move'))->assertOk()->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['board'][15])->toBe('x');
});

test('scores a victory once and preserves score and alternating starter on the next round', function () {
    $id = publicMove4StartingWithX($this);
    $component = Livewire::test('experimental-move-4x3', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }
    $component->assertSee('Jogador X venceu!')->assertDispatched('movement-round-finished');
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(3);
    $component->call('place', 15)->assertSee('A rodada terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $component->call('restart')->assertSee('Vez do jogador O')->assertSee('Coloque uma peça');
    expect(session('movement4x3.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 16, ''));
});

test('scores a movement victory and shows its winning cells', function () {
    $id = publicMove4StartingWithX($this);
    $component = Livewire::test('experimental-move-4x3', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }
    $component->call('move', 2, 8)->assertSee('Jogador X venceu!');
    expect(app(Move4x3Rules::class)->winningLine(app(SessionMatchStore::class)->find($id)))->toBe([0, 4, 8]);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
});

test('creates a new active match with zero score without changing the other public modalities', function () {
    $id = publicMove4StartingWithX($this);
    foreach (['3-basic', '4x3-basic', '3-move'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $otherIds = [session('classic.active_match_id'), session('classic4x3.active_match_id'), session('movement3x3.active_match_id')];
    $component = Livewire::test('experimental-move-4x3', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(1)));
    $component->call('newMatch')->assertRedirect(route('4x3-move'));
    $nextId = session('movement4x3.active_match_id');
    expect($nextId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($nextId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($nextId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect([session('classic.active_match_id'), session('classic4x3.active_match_id'), session('movement3x3.active_match_id')])->toBe($otherIds);
});

test('playing movement four by four does not alter the other three public boards', function () {
    $fourId = publicMove4StartingWithX($this);
    foreach (['3-basic', '4x3-basic', '3-move'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $store = app(SessionMatchStore::class);
    $otherIds = [session('classic.active_match_id'), session('classic4x3.active_match_id'), session('movement3x3.active_match_id')];
    $before = array_map(fn (string $id): array => $store->find($id)->data['board'], $otherIds);

    Livewire::test('experimental-move-4x3', ['matchId' => $fourId, 'publicEntry' => true])->call('place', 15);

    expect($store->find($fourId)->data['board'][15])->toBe('x');
    expect(array_map(fn (string $id): array => $store->find($id)->data['board'], $otherIds))->toBe($before);
    expect([session('classic.active_match_id'), session('classic4x3.active_match_id'), session('movement3x3.active_match_id')])->toBe($otherIds);
});

test('replaces invalid, expired, corrupt, or wrong-modality active pointers', function () {
    $id = publicMove4StartingWithX($this);
    $this->get(route('3-move'))->assertOk();
    $threeId = session('movement3x3.active_match_id');
    foreach (['invalid', str_repeat('A', 40), $threeId] as $pointer) {
        session()->put('movement4x3.active_match_id', $pointer);
        $this->get(route('4x3-move'))->assertOk();
        expect(session('movement4x3.active_match_id'))->not->toBe($pointer);
    }
    $corrupt = session('movement4x3.active_match_id');
    session()->put('experimental_matches.'.$corrupt.'.state.data.board', array_fill(0, 16, 'x'));
    $this->get(route('4x3-move'))->assertOk();
    expect(session('movement4x3.active_match_id'))->not->toBe($corrupt);
    expect(app(SessionMatchStore::class)->find($id)->modality->key)->toBe('4x3-move');
    expect(session('movement3x3.active_match_id'))->toBe($threeId);
});

test('prevents cross-modality writes and stale public tabs from changing a fresh match', function () {
    $id = publicMove4StartingWithX($this);
    $this->get(route('3-move'))->assertOk();
    $threeId = session('movement3x3.active_match_id');
    $store = app(SessionMatchStore::class);
    expect(fn () => $store->save($threeId, $store->find($id)))->toThrow(InvalidArgumentException::class);
    expect(fn () => $store->restart($id, $store->find($threeId)))->toThrow(InvalidArgumentException::class);

    $staleTabs = [];
    foreach (['place', 'move', 'restart', 'newMatch'] as $method) {
        $staleTabs[$method] = Livewire::test('experimental-move-4x3', ['matchId' => $id, 'publicEntry' => true]);
    }
    $newTab = Livewire::test('experimental-move-4x3', ['matchId' => $id, 'publicEntry' => true]);
    $newTab->call('newMatch')->assertRedirect(route('4x3-move'));
    foreach (['place' => [0], 'move' => [0, 15], 'restart' => [], 'newMatch' => []] as $method => $arguments) {
        $staleTabs[$method]->call($method, ...$arguments)->assertNotFound();
    }
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 16, ''));
});

test('keeps matches isolated between sessions and locks official Livewire properties', function () {
    $id = publicMove4StartingWithX($this);
    $otherSession = new Store('movement4-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('experimental-move-4x3', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => Livewire::test('experimental-move-4x3', ['matchId' => $id])->set('publicEntry', false))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => [], 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('experimental-move-4x3', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});
