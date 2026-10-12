<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Games\Rules\Classic4x3Rules;
use App\Http\ClassicMatchManager;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

test('opens the public four by four classic without login and creates a server-side match', function (int $seed, PlayerMark $starter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));

    $page = $this->get(route('4x3-basic'));

    $page->assertOk()->assertViewIs('experimental-classic-4x3')
        ->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('data-testid="new-match-button"', false)
        ->assertDontSee('js/game.js');
    expect(substr_count($page->getContent(), 'data-testid="cell-'))->toBe(16);

    $matchId = session('classic4x3.active_match_id');
    expect($matchId)->toBeString()->toHaveLength(40);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('resumes the same four by four round and scoreboard without drawing again', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $matchId = session('classic4x3.active_match_id');

    Livewire::test('experimental-classic-4x3', ['matchId' => $matchId, 'publicEntry' => true])
        ->call('place', 0)->assertSee('Vez do jogador O');
    $this->get(route('4x3-basic'))->assertOk()->assertSee('Jogadas realizadas: 1');

    expect(session('classic4x3.active_match_id'))->toBe($matchId);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe(PlayerMark::X);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][0])->toBe('x');
});

test('preserves a completed result and score between public rounds', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $matchId = session('classic4x3.active_match_id');
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $matchId, 'publicEntry' => true]);

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('4x3-basic'))->assertOk()->assertSee('Jogador X venceu!');
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);

    $component->call('place', 3)->assertSee('A partida terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);

    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
});

test('counts a public four by four draw once and keeps it on reload', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $matchId = session('classic4x3.active_match_id');
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $matchId, 'publicEntry' => true]);

    foreach ([1, 0, 3, 2, 4, 6, 5, 7, 10, 8, 11, 9, 12, 14, 13, 15] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('4x3-basic'))->assertOk()->assertSee('Empate!');
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 1]);
    $component->call('place', 0)->assertSee('A partida terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($matchId)['draws'])->toBe(1);
});

test('creates a new four by four match with a fresh score without resetting the three by three match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $classic3Id = session('classic.active_match_id');
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $old4Id = session('classic4x3.active_match_id');
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $old4Id, 'publicEntry' => true]);

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->call('newMatch')->assertRedirect(route('4x3-basic'));
    $new4Id = session('classic4x3.active_match_id');

    expect($new4Id)->not->toBe($old4Id);
    expect(app(SessionMatchStore::class)->startingPlayer($new4Id))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($new4Id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($old4Id)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->find($new4Id)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(session('classic.active_match_id'))->toBe($classic3Id);
    $this->get(route('3-basic'))->assertOk();
    expect(session('classic4x3.active_match_id'))->toBe($new4Id);
});

test('keeps both public classics independent while navigating between them', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $classic3Id = session('classic.active_match_id');
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $classic4Id = session('classic4x3.active_match_id');

    Livewire::test('experimental-classic-3x3', ['matchId' => $classic3Id, 'publicEntry' => true])->call('place', 0);
    Livewire::test('experimental-classic-4x3', ['matchId' => $classic4Id, 'publicEntry' => true])->call('place', 15);

    $this->get(route('3-basic'))->assertSee('Jogadas realizadas: 1');
    $this->get(route('4x3-basic'))->assertSee('Jogadas realizadas: 1');
    expect(session('classic.active_match_id'))->toBe($classic3Id);
    expect(session('classic4x3.active_match_id'))->toBe($classic4Id);
    expect(app(SessionMatchStore::class)->find($classic3Id)->data['board'][0])->toBe('x');
    expect(app(SessionMatchStore::class)->find($classic4Id)->data['board'][15])->toBe('x');
});

test('replaces an invalid or cross-modality four by four active pointer', function (mixed $pointer) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $classic3Id = session('classic.active_match_id');
    session()->put('classic4x3.active_match_id', $pointer === 'three-by-three' ? $classic3Id : $pointer);

    $this->get(route('4x3-basic'))->assertOk();
    $classic4Id = session('classic4x3.active_match_id');

    expect($classic4Id)->toBeString()->toHaveLength(40)->not->toBe($classic3Id);
    expect(app(SessionMatchStore::class)->find($classic4Id)->modality->key)->toBe('4x3-basic');
    expect(session('classic.active_match_id'))->toBe($classic3Id);
})->with(['missing' => [null], 'malformed' => ['bad-id'], 'expired' => [str_repeat('A', 40)], 'wrong modality' => ['three-by-three']]);

test('replaces a corrupt active four by four record', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $oldId = session('classic4x3.active_match_id');
    session()->put('experimental_matches.'.$oldId.'.state.data.board', ['invalid']);

    $this->get(route('4x3-basic'))->assertOk()->assertSee('Jogadas realizadas: 0');
    expect(session('classic4x3.active_match_id'))->not->toBe($oldId);
});

test('does not allow another session to adopt a four by four match', function () {
    $handler = new ArraySessionHandler(120);
    $firstSession = new Store('classic-test', $handler, 'first-session');
    $secondSession = new Store('classic-test', $handler, 'second-session');
    $firstManager = new ClassicMatchManager($firstSession, new SessionMatchStore($firstSession), new Classic3x3Rules, new Randomizer(new Mt19937(0)));
    $secondManager = new ClassicMatchManager($secondSession, new SessionMatchStore($secondSession), new Classic3x3Rules, new Randomizer(new Mt19937(1)));
    $firstId = $firstManager->resumeOrCreate4x3(new Classic4x3Rules);
    $secondSession->put('classic4x3.active_match_id', $firstId);

    $secondId = $secondManager->resumeOrCreate4x3(new Classic4x3Rules);

    expect($secondId)->not->toBe($firstId);
    expect($secondManager->isActive4x3($firstId))->toBeFalse();
    expect($firstManager->isActive4x3($firstId))->toBeTrue();
});

test('rejects stale tabs and Livewire property manipulation for the public four by four match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('4x3-basic'))->assertOk();
    $oldId = session('classic4x3.active_match_id');
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $oldId, 'publicEntry' => true]);

    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $oldId, 'publicEntry' => true])->set('matchId', str_repeat('B', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $oldId, 'publicEntry' => true])->set('publicEntry', false))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    $newId = app(ClassicMatchManager::class)->createNewActive4x3(new Classic4x3Rules);
    $component->call('place', 0)->assertNotFound();
    expect(app(SessionMatchStore::class)->find($oldId)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 16, ''));
});
