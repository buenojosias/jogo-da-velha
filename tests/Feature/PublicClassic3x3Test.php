<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Http\ClassicMatchManager;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

test('opens the public classic without login using Livewire and a new session match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));

    $response = $this->get(route('3-basic'));

    $response
        ->assertOk()
        ->assertViewIs('experimental-classic')
        ->assertSee('Vez do jogador X')
        ->assertSee('data-testid="classic-board"', false)
        ->assertSee('data-testid="new-match-button"', false)
        ->assertDontSee('js/game.js');

    $matchId = session('classic.active_match_id');
    expect($matchId)->toBeString()->toHaveLength(40);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('resumes an in-progress public match without drawing a new starter', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $matchId = session('classic.active_match_id');

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId, 'publicEntry' => true])
        ->call('place', 0)
        ->assertSee('Vez do jogador O');

    $this->get(route('3-basic'))
        ->assertOk()
        ->assertSee('Jogadas realizadas: 1')
        ->assertSee('Vez do jogador O');

    expect(session('classic.active_match_id'))->toBe($matchId);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe(PlayerMark::X);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][0])->toBe('x');
});

test('resumes a completed public round and preserves its score through the next round', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $matchId = session('classic.active_match_id');
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId, 'publicEntry' => true]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('3-basic'))
        ->assertOk()
        ->assertSee('Jogador X venceu!')
        ->assertSee('data-testid="result-dialog"', false);

    expect(session('classic.active_match_id'))->toBe($matchId);
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);

    $component->call('restart')->assertSee('Vez do jogador O');
    $this->get(route('3-basic'))->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
});

test('creates a separate public match with a fresh score and server-drawn starter', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $oldId = session('classic.active_match_id');
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $oldId, 'publicEntry' => true]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->call('newMatch')->assertRedirect(route('3-basic'));
    $newId = session('classic.active_match_id');

    expect($newId)->not->toBe($oldId);
    expect(app(SessionMatchStore::class)->startingPlayer($newId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->score($oldId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);

    $this->get(route('3-basic'))->assertOk()->assertSee('Vez do jogador O');
});

test('replaces a missing, expired, or malformed active match safely', function (mixed $activePointer) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    session()->put('classic.active_match_id', $activePointer);

    $this->get(route('3-basic'))->assertOk()->assertSee('Vez do jogador X');

    $matchId = session('classic.active_match_id');
    expect($matchId)->toBeString()->toHaveLength(40);
    expect($matchId)->not->toBe($activePointer);
    expect(app(SessionMatchStore::class)->find($matchId))->not->toBeNull();
})->with([
    'missing pointer' => [null],
    'invalid shape' => [['not-an-id']],
    'malformed identifier' => ['bad-id'],
    'expired match record' => [str_repeat('A', 40)],
]);

test('ignores a browser-supplied match identifier and keeps the active match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $matchId = session('classic.active_match_id');

    $this->get(route('3-basic', ['matchId' => str_repeat('B', 40)]))
        ->assertOk()
        ->assertSee('Vez do jogador X');

    expect(session('classic.active_match_id'))->toBe($matchId);
});

test('replaces a corrupt active record without exposing its invalid state', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $oldId = session('classic.active_match_id');
    session()->put('experimental_matches.'.$oldId.'.state.data.board', ['invalid']);

    $this->get(route('3-basic'))->assertOk()->assertSee('Jogadas realizadas: 0');

    $newId = session('classic.active_match_id');
    expect($newId)->not->toBe($oldId);
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 9, ''));
});

test('does not treat another session’s match as the active match', function () {
    $handler = new ArraySessionHandler(120);
    $firstSession = new Store('classic-test', $handler, 'first-session');
    $secondSession = new Store('classic-test', $handler, 'second-session');
    $firstManager = new ClassicMatchManager($firstSession, new SessionMatchStore($firstSession), new Classic3x3Rules, new Randomizer(new Mt19937(0)));
    $secondManager = new ClassicMatchManager($secondSession, new SessionMatchStore($secondSession), new Classic3x3Rules, new Randomizer(new Mt19937(1)));
    $firstId = $firstManager->resumeOrCreate();
    $secondSession->put('classic.active_match_id', $firstId);

    $secondId = $secondManager->resumeOrCreate();

    expect($secondId)->not->toBe($firstId);
    expect($firstManager->isActive($firstId))->toBeTrue();
    expect($secondManager->isActive($firstId))->toBeFalse();
    expect($secondManager->isActive($secondId))->toBeTrue();
});

test('keeps the active identifier locked and refuses new-match calls outside the public active match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $activeId = session('classic.active_match_id');
    $otherId = app(ClassicMatchManager::class)->create();

    expect(fn () => Livewire::test('experimental-classic-3x3', ['matchId' => $activeId, 'publicEntry' => true])->set('matchId', $otherId))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => Livewire::test('experimental-classic-3x3', ['matchId' => $activeId, 'publicEntry' => true])->set('publicEntry', false))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    Livewire::test('experimental-classic-3x3', ['matchId' => $otherId, 'publicEntry' => true])
        ->assertNotFound();
    Livewire::test('experimental-classic-3x3', ['matchId' => $otherId])
        ->call('newMatch')
        ->assertNotFound();
    expect(session('classic.active_match_id'))->toBe($activeId);
});

test('rejects actions from a stale public tab after another match becomes active', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $oldId = session('classic.active_match_id');
    $staleComponent = Livewire::test('experimental-classic-3x3', ['matchId' => $oldId, 'publicEntry' => true]);
    $newId = app(ClassicMatchManager::class)->createNewActive();

    $staleComponent->call('place', 0)->assertNotFound();

    expect(app(SessionMatchStore::class)->find($oldId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 9, ''));
});
