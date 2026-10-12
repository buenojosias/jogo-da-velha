<?php

use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Move3x3Rules;
use App\Http\SessionMatchStore;
use Illuminate\Routing\RouteCollection;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

test('creates and resumes an isolated movement match with a server-selected starter', function (int $seed, PlayerMark $starter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));

    $response = $this->get(route('experimental.movement.create', ['starting_player' => 'x']))->assertRedirect();
    $matchId = basename($response->headers->get('Location'));
    $page = $this->get(route('experimental.movement.show', $matchId));

    $page->assertOk()->assertViewIs('experimental-move-3x3')
        ->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('Coloque uma peça');
    expect(substr_count($page->getContent(), 'data-testid="cell-'))->toBe(9);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);

    $this->get(route('experimental.movement.show', $matchId))->assertOk()->assertSee('Vez do jogador '.$starter->name);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('places pieces, enters movement, rejects invalid moves and persists accepted movement', function () {
    $matchId = movementSessionMatch();
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $matchId]);

    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Selecione uma peça e depois uma casa vazia')
        ->assertSee('Vez do jogador X')
        ->call('place', 5)->assertSee('Agora mova uma das suas peças.')
        ->call('move', 1, 5)->assertSee('Selecione uma peça sua.')
        ->call('move', 0, 1)->assertSee('Esta casa já está ocupada.');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(['x', 'o', 'x', 'o', 'x', '', 'o', '', '']);

    $component->call('move', 0, 5)->assertSee('Vez do jogador O')->assertDispatched('movement-state-changed');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(['', 'o', 'x', 'o', 'x', 'x', 'o', '', '']);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    $this->get(route('experimental.movement.show', $matchId))->assertOk()->assertSee('Vez do jogador O');
});

test('detects a placement win, highlights it and scores only once', function () {
    $matchId = movementSessionMatch();
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $matchId]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Jogador X venceu!')->assertDispatched('movement-round-finished');
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(3);
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);
    $component->call('place', 8)->assertSee('A rodada terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);

    $this->get(route('experimental.movement.show', $matchId))->assertOk()->assertSee('Jogador X venceu!');
    $component->call('restart')->assertSee('Vez do jogador O')->assertSee('Coloque uma peça');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->score($matchId)['x'])->toBe(1);
});

test('detects a movement win and keeps both scores separate', function () {
    $matchId = movementSessionMatch();
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $matchId]);

    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }

    $component->call('move', 2, 8)->assertSee('Jogador X venceu!')->assertDispatched('movement-round-finished');
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
    expect(app(Move3x3Rules::class)->winningLine(app(SessionMatchStore::class)->find($matchId)))->toBe([0, 4, 8]);
});

test('scores an O victory during placement without entering movement', function () {
    $matchId = movementSessionMatch();
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $matchId]);

    foreach ([0, 3, 8, 4, 6, 5] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Jogador O venceu!')->assertDispatched('movement-round-finished');
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 1, 'draws' => 0]);
    expect(app(Move3x3Rules::class)->winningLine(app(SessionMatchStore::class)->find($matchId)))->toBe([3, 4, 5]);
});

test('rejects invalid or cross-modality session records and keeps classics untouched', function () {
    $matchId = movementSessionMatch();
    $store = app(SessionMatchStore::class);
    $this->get(route('3-move'))->assertOk()->assertViewIs('experimental-move-3x3');
    $this->get(route('3-basic'))->assertOk();
    expect(session('classic.active_match_id'))->not->toBe($matchId);
    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])->assertNotFound();

    session()->put('experimental_matches.'.$matchId.'.state.data.board', array_fill(0, 9, 'x'));
    expect(fn () => $store->find($matchId))->toThrow(UnexpectedValueException::class);
    $this->get(route('experimental.movement.show', $matchId))->assertNotFound();
});

test('does not allow movement and classic matches to overwrite each other', function () {
    $movementId = movementSessionMatch();
    $this->get(route('3-basic'))->assertOk();
    $classicId = session('classic.active_match_id');
    $store = app(SessionMatchStore::class);

    expect(fn () => $store->save($classicId, $store->find($movementId)))->toThrow(InvalidArgumentException::class);
    expect(fn () => $store->restart($movementId, $store->find($classicId)))->toThrow(InvalidArgumentException::class);
    expect($store->find($movementId)->modality->key)->toBe('3-move');
    expect($store->find($classicId)->modality->key)->toBe('3-basic');
    expect($store->score($movementId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect($store->score($classicId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('keeps movement matches and their scoreboards independent within one session', function () {
    $firstId = movementSessionMatch();
    $secondId = movementSessionMatch();
    $component = Livewire::test('experimental-move-3x3', ['matchId' => $firstId]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    expect($firstId)->not->toBe($secondId);
    expect(app(SessionMatchStore::class)->score($firstId)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->score($secondId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->find($secondId)->data['board'])->toBe(array_fill(0, 9, ''));
});

test('does not expose other sessions or mutable official properties', function () {
    $handler = new ArraySessionHandler(120);
    $first = new Store('movement-test', $handler, 'first');
    $second = new Store('movement-test', $handler, 'second');
    $participants = new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'),
        new Participant(ParticipantKind::Guest, 'local-o'),
    );
    $matchId = (new SessionMatchStore($first))->create((new Move3x3Rules)->initialState(new Modality('3-move'), $participants, PlayerMark::X));

    expect((new SessionMatchStore($second))->find($matchId))->toBeNull();
    Livewire::test('experimental-move-3x3', ['matchId' => $matchId])->assertNotFound();

    $ownMatchId = movementSessionMatch();
    expect(fn () => Livewire::test('experimental-move-3x3', ['matchId' => $ownMatchId])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => array_fill(0, 9, 'x'), 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('experimental-move-3x3', ['matchId' => $ownMatchId])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});

test('does not register experimental movement routes in production', function () {
    $originalRoutes = Route::getRoutes();
    $originalEnvironment = app()->environment();

    try {
        app()->detectEnvironment(fn () => 'production');
        Route::setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        Route::getRoutes()->refreshNameLookups();

        expect(Route::has('experimental.movement.create'))->toBeFalse();
        expect(Route::has('experimental.movement.show'))->toBeFalse();
        expect(Route::has('3-move'))->toBeTrue();
    } finally {
        Route::setRoutes($originalRoutes);
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

function movementSessionMatch(): string
{
    $participants = new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'),
        new Participant(ParticipantKind::Guest, 'local-o'),
    );

    return app(SessionMatchStore::class)->create(
        (new Move3x3Rules)->initialState(new Modality('3-move'), $participants, PlayerMark::X),
    );
}
