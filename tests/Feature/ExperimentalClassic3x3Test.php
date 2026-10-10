<?php

use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Http\SessionMatchStore;
use Illuminate\Routing\RouteCollection;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

test('creates a local match and renders nine empty cells', function () {
    $response = $this->get(route('experimental.classic.create'));
    $response->assertRedirect();

    $matchId = basename($response->headers->get('Location'));

    $this->get(route('experimental.classic.show', $matchId))
        ->assertOk()
        ->assertSee('Vez do jogador X')
        ->assertSee('Jogadas realizadas: 0');

    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSee('Jogador X')
        ->assertSee('Jogador O');

    expect(substr_count($component->html(), 'data-testid="cell-'))->toBe(9);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));
});

test('persists a valid move and alternates the turn across component requests', function () {
    $matchId = createClassicSessionMatch();

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->call('place', 0)
        ->assertSee('Vez do jogador O')
        ->assertSee('Jogadas realizadas: 1');

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSee('Vez do jogador O')
        ->call('place', 4)
        ->assertSee('Vez do jogador X');

    $state = app(SessionMatchStore::class)->find($matchId);
    expect($state->data['board'])->toBe(['x', '', '', '', 'o', '', '', '', '']);
    expect($state->currentPlayer)->toBe(PlayerMark::X);
});

test('rejects occupied and invalid cells without modifying the official state', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->call('place', 0)
        ->call('place', 0)
        ->assertSee('Esta casa já está ocupada.');

    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][0])->toBe('x');

    $component->call('place', '8')->assertSee('Escolha uma casa válida.');

    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(['x', '', '', '', '', '', '', '', '']);
});

test('shows a victory and rejects further moves after the match finishes', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Jogador X venceu!')
        ->call('place', 5)
        ->assertSee('A partida terminou. Reinicie para jogar novamente.');

    $state = app(SessionMatchStore::class)->find($matchId);
    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe(PlayerMark::X);
    expect($state->data['board'][5])->toBe('');
});

test('shows a draw after nine moves', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ([0, 1, 2, 4, 3, 5, 7, 6, 8] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Empate!');
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Drawn);
});

test('restarts with an empty board, clears errors, and alternates the starter', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->call('place', 0)
        ->call('place', 0)
        ->assertSee('Esta casa já está ocupada.')
        ->call('restart')
        ->assertDontSee('Esta casa já está ocupada.')
        ->assertSee('Vez do jogador O')
        ->assertSee('Jogadas realizadas: 0');

    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));

    $component->call('restart')->assertSee('Vez do jogador X');

    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe(PlayerMark::X);
});

test('keeps two matches in the same session independent', function () {
    $firstId = createClassicSessionMatch();
    $secondId = createClassicSessionMatch();

    Livewire::test('experimental-classic-3x3', ['matchId' => $firstId])->call('place', 0);

    expect($firstId)->not->toBe($secondId);
    expect(app(SessionMatchStore::class)->find($firstId)->data['board'][0])->toBe('x');
    expect(app(SessionMatchStore::class)->find($secondId)->data['board'])->toBe(array_fill(0, 9, ''));
});

test('does not expose a match to a different session', function () {
    $handler = new ArraySessionHandler(120);
    $firstSession = new Store('experimental-test', $handler, 'first-session');
    $secondSession = new Store('experimental-test', $handler, 'second-session');
    $firstStore = new SessionMatchStore($firstSession);
    $secondStore = new SessionMatchStore($secondSession);
    $state = (new Classic3x3Rules)->initialState(new Modality('3-basic'), classicLocalParticipants(), PlayerMark::X);

    $matchId = $firstStore->create($state);

    expect($firstStore->find($matchId))->toEqual($state);
    expect($secondStore->find($matchId))->toBeNull();
});

test('returns not found for an unknown match identifier', function () {
    $this->get(route('experimental.classic.show', str_repeat('A', 40)))->assertNotFound();
});

test('refuses to reconstruct a malformed board stored in the session', function () {
    $matchId = createClassicSessionMatch();
    session()->put('experimental_matches.'.$matchId.'.state.data.board', ['x']);

    expect(fn () => app(SessionMatchStore::class)->find($matchId))
        ->toThrow(UnexpectedValueException::class);
});

test('rejects attempts to replace the match identifier or mutate official state through Livewire', function (string $property, mixed $value, string $exception) {
    $matchId = createClassicSessionMatch();
    $otherId = createClassicSessionMatch();

    expect(fn () => Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])->set($property, $value))
        ->toThrow($exception);

    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->find($otherId)->data['board'])->toBe(array_fill(0, 9, ''));
})->with([
    'locked match ID' => ['matchId', str_repeat('A', 40), CannotUpdateLockedPropertyException::class],
    'board' => ['board', array_fill(0, 9, 'x'), PublicPropertyNotFoundException::class],
    'winner' => ['winner', 'x', PublicPropertyNotFoundException::class],
    'current player' => ['currentPlayer', 'o', PublicPropertyNotFoundException::class],
]);

test('does not register experimental routes in production', function () {
    $originalRoutes = Route::getRoutes();
    $originalEnvironment = app()->environment();

    try {
        app()->detectEnvironment(fn () => 'production');
        Route::setRoutes(new RouteCollection);
        require base_path('routes/web.php');

        expect(Route::has('experimental.classic.create'))->toBeFalse();
        expect(Route::has('experimental.classic.show'))->toBeFalse();
    } finally {
        Route::setRoutes($originalRoutes);
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

function createClassicSessionMatch(): string
{
    $state = (new Classic3x3Rules)->initialState(
        new Modality('3-basic'),
        classicLocalParticipants(),
        PlayerMark::X,
    );

    return app(SessionMatchStore::class)->create($state);
}

function classicLocalParticipants(): Participants
{
    return new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'),
        new Participant(ParticipantKind::Guest, 'local-o'),
    );
}
