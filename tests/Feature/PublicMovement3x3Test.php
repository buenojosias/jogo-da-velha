<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\Move3x3Rules;
use App\Http\MovementMatchManager;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

test('opens public movement without login and draws the starting player on the server', function (int $seed, PlayerMark $starter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));

    $page = $this->get(route('3-move', ['starting_player' => $starter === PlayerMark::X ? 'o' : 'x']));

    $page->assertOk()->assertViewIs('experimental-move-3x3')
        ->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('data-testid="new-match-button"', false)
        ->assertDontSee('js/game.js')
        ->assertDontSee('let selectedPiece = null');
    expect(substr_count($page->getContent(), 'data-testid="cell-'))->toBe(9);
    $matchId = session('movement3x3.active_match_id');
    expect($matchId)->toBeString()->toHaveLength(40);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('resumes placement and movement without redrawing or changing the scoreboard', function () {
    publicMovementStartingWithX($this);
    $matchId = session('movement3x3.active_match_id');
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $matchId, 'publicEntry' => true]);

    foreach ([0, 1, 2] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('3-move'))->assertOk()->assertSee('Vez do jogador O')->assertSee('Coloque uma peça');
    expect(session('movement3x3.active_match_id'))->toBe($matchId);

    foreach ([3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('3-move'))->assertOk()->assertSee('Selecione uma peça e depois uma casa vazia');
    $component->call('move', 0, 5)->assertSee('Vez do jogador O');
    $this->get(route('3-move'))->assertOk()->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(['', 'o', 'x', 'o', 'x', 'x', 'o', '', '']);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('keeps a completed result and score across reload and next round', function () {
    publicMovementStartingWithX($this);
    $matchId = session('movement3x3.active_match_id');
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $matchId, 'publicEntry' => true]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('3-move'))->assertOk()->assertSee('Jogador X venceu!');
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);
    $component->call('move', 0, 5)->assertSee('A rodada terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);

    $component->call('restart')->assertSee('Vez do jogador O')->assertSee('Coloque uma peça');
    expect(session('movement3x3.active_match_id'))->toBe($matchId);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);
});

test('creates a new public match with a new draw and zero score without changing the classics', function () {
    publicMovementStartingWithX($this);
    $oldId = session('movement3x3.active_match_id');
    $this->get(route('3-basic'))->assertOk();
    $classic3Id = session('classic.active_match_id');
    $this->get(route('4x3-basic'))->assertOk();
    $classic4Id = session('classic4x3.active_match_id');
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(1)));
    $component->call('newMatch')->assertRedirect(route('3-move'));
    $newId = session('movement3x3.active_match_id');

    expect($newId)->not->toBe($oldId);
    expect(app(SessionMatchStore::class)->startingPlayer($newId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($oldId)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(session('classic.active_match_id'))->toBe($classic3Id);
    expect(session('classic4x3.active_match_id'))->toBe($classic4Id);
    $this->get(route('3-move'))->assertOk()->assertSee('Vez do jogador O');
});

test('replaces missing, malformed, expired or wrong-modality active pointers', function (mixed $pointer) {
    $this->get(route('3-basic'))->assertOk();
    $classicId = session('classic.active_match_id');
    session()->put('movement3x3.active_match_id', $pointer === 'classic' ? $classicId : $pointer);

    $this->get(route('3-move'))->assertOk();
    $movementId = session('movement3x3.active_match_id');

    expect($movementId)->toBeString()->toHaveLength(40)->not->toBe($classicId);
    expect(app(SessionMatchStore::class)->find($movementId)->modality->key)->toBe('3-move');
    expect(session('classic.active_match_id'))->toBe($classicId);
})->with(['missing' => [null], 'malformed' => ['bad-id'], 'expired' => [str_repeat('A', 40)], 'wrong modality' => ['classic']]);

test('replaces a corrupt active record without touching the classic session keys', function () {
    $this->get(route('3-move'))->assertOk();
    $oldId = session('movement3x3.active_match_id');
    session()->put('experimental_matches.'.$oldId.'.state.data.board', ['invalid']);

    $this->get(route('3-move'))->assertOk()->assertSee('Coloque uma peça');

    expect(session('movement3x3.active_match_id'))->not->toBe($oldId);
    expect(app(SessionMatchStore::class)->find(session('movement3x3.active_match_id'))->data['board'])->toBe(array_fill(0, 9, ''));
});

test('does not allow another session to adopt an active movement match', function () {
    $handler = new ArraySessionHandler(120);
    $firstSession = new Store('movement-test', $handler, 'first-session');
    $secondSession = new Store('movement-test', $handler, 'second-session');
    $firstManager = new MovementMatchManager($firstSession, new SessionMatchStore($firstSession), new Move3x3Rules, new Randomizer(new Mt19937(0)));
    $secondManager = new MovementMatchManager($secondSession, new SessionMatchStore($secondSession), new Move3x3Rules, new Randomizer(new Mt19937(1)));
    $firstId = $firstManager->resumeOrCreate();
    $secondSession->put('movement3x3.active_match_id', $firstId);

    $secondId = $secondManager->resumeOrCreate();

    expect($secondId)->not->toBe($firstId);
    expect($secondManager->isActive($firstId))->toBeFalse();
    expect($firstManager->isActive($firstId))->toBeTrue();
});

test('rejects stale public tabs and protected-property manipulation', function () {
    publicMovementStartingWithX($this);
    $oldId = session('movement3x3.active_match_id');
    $placeTab = Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true]);
    $moveTab = Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true]);
    $restartTab = Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true]);
    $newMatchTab = Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true]);

    expect(fn () => Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true])->set('matchId', str_repeat('B', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true])->set('publicEntry', false))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => array_fill(0, 9, 'x'), 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('experimental-move-3x3', ['matchId' => $oldId, 'publicEntry' => true])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }

    $newId = app(MovementMatchManager::class)->createNewActive();
    $placeTab->call('place', 0)->assertNotFound();
    $moveTab->call('move', 0, 1)->assertNotFound();
    $restartTab->call('restart')->assertNotFound();
    $newMatchTab->call('newMatch')->assertNotFound();
    expect(app(SessionMatchStore::class)->find($oldId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 9, ''));
});

test('keeps a standalone session match independent and forbids a public-only action there', function () {
    $this->get(route('3-move'))->assertOk();
    $publicId = session('movement3x3.active_match_id');
    $standaloneId = app(MovementMatchManager::class)->create();

    Livewire::test('experimental-move-3x3', ['matchId' => $standaloneId])->call('newMatch')->assertNotFound();
    expect(session('movement3x3.active_match_id'))->toBe($publicId);
    expect(app(SessionMatchStore::class)->find($standaloneId)->modality->key)->toBe('3-move');
});

function publicMovementStartingWithX(TestCase $testCase): void
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $testCase->get(route('3-move'))->assertOk()->assertSee('Vez do jogador X');
}
