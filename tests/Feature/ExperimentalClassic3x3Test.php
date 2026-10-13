<?php

use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Http\ClassicMatchManager;
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

test('creates a local match and renders nine empty cells', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))
        ->assertOk()
        ->assertSee('Vez do jogador X')
        ->assertSee('Jogadas realizadas: 0');
    $matchId = session('classic.active_match_id');

    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSee('Jogador X')
        ->assertSee('Jogador O')
        ->assertSee('Clássico 3×3')
        ->assertSee('Automático')
        ->assertSee('Claro')
        ->assertSee('Escuro');

    expect(substr_count($component->html(), 'data-testid="cell-'))->toBe(9);
    expect($component->html())
        ->toContain('data-testid="match-header"')
        ->toContain('data-testid="participant-x"')
        ->toContain('data-testid="participant-o"')
        ->toContain('data-testid="match-status"')
        ->toContain('data-testid="match-actions"')
        ->toContain('data-testid="theme-selector"');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 9, ''));
});

test('selects and records the initial player on the server without redrawing on reload', function (int $seed, PlayerMark $expectedStarter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));

    $oppositeMark = $expectedStarter === PlayerMark::X ? 'o' : 'x';
    $this->get(route('3-basic', ['starting_player' => $oppositeMark]))->assertOk();
    $matchId = session('classic.active_match_id');
    $store = app(SessionMatchStore::class);

    expect($store->startingPlayer($matchId))->toBe($expectedStarter);
    expect($store->find($matchId)->currentPlayer)->toBe($expectedStarter);

    $this->get(route('3-basic'))
        ->assertOk()
        ->assertSee('Vez do jogador '.$expectedStarter->name);

    expect($store->startingPlayer($matchId))->toBe($expectedStarter);
    expect($store->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with([
    'X starter' => [0, PlayerMark::X],
    'O starter' => [1, PlayerMark::O],
]);

test('selects a new initial player for each independent match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));

    $this->get(route('3-basic'))->assertOk();
    $firstId = session('classic.active_match_id');
    $secondId = app(ClassicMatchManager::class)->createNewActive();
    $store = app(SessionMatchStore::class);

    expect($firstId)->not->toBe($secondId);
    expect($store->startingPlayer($firstId))->toBe(PlayerMark::X);
    expect($store->startingPlayer($secondId))->toBe(PlayerMark::O);
});

test('keeps an O-started match and its score after victory, reload, and restart', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(1)));
    $this->get(route('3-basic'))->assertOk();
    $matchId = session('classic.active_match_id');
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSee('Vez do jogador O');

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component
        ->assertSee('Jogador O venceu!')
        ->assertSeeHtml('data-testid="result-dialog"')
        ->assertDispatched('classic-round-finished');

    $store = app(SessionMatchStore::class);
    expect($store->score($matchId))->toBe(['x' => 0, 'o' => 1, 'draws' => 0]);

    $this->get(route('3-basic'))
        ->assertOk()
        ->assertSee('Jogador O venceu!');

    $component->call('restart')->assertSee('Vez do jogador X');
    expect($store->startingPlayer($matchId))->toBe(PlayerMark::X);
    expect($store->score($matchId))->toBe(['x' => 0, 'o' => 1, 'draws' => 0]);
});

test('starts a local match with a zeroed scoreboard', function () {
    $matchId = createClassicSessionMatch();

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSeeHtml('data-testid="score-x"')
        ->assertSeeHtml('data-testid="score-o"')
        ->assertSeeHtml('data-testid="score-draws"');

    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('counts only the confirmed terminal result of a round', function (array $cells, array $expectedScore, string $resultMessage) {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ($cells as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee($resultMessage);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe($expectedScore);
})->with([
    'X victory' => [[0, 3, 1, 4, 2], ['x' => 1, 'o' => 0, 'draws' => 0], 'Jogador X venceu!'],
    'O victory' => [[0, 3, 1, 4, 8, 5], ['x' => 0, 'o' => 1, 'draws' => 0], 'Jogador O venceu!'],
    'draw' => [[0, 1, 2, 4, 3, 5, 7, 6, 8], ['x' => 0, 'o' => 0, 'draws' => 1], 'Empate!'],
]);

test('preserves the scoreboard through a restart, another round and a new request', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSee('Vez do jogador O');
    $response = $this->get(route('3-basic'))->assertOk();
    preg_match('/data-testid="score-x"[^>]*>(\d+)</', $response->getContent(), $renderedScore);
    expect($renderedScore[1] ?? null)->toBe('1');

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 1, 'draws' => 0]);
});

test('keeps scoreboards separate for distinct matches in the same session', function () {
    $firstId = createClassicSessionMatch();
    $secondId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $firstId]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    expect(app(SessionMatchStore::class)->score($firstId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->score($secondId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('does not count rejected or repeated actions after a round ends', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->call('place', 0)
        ->call('place', 0)
        ->assertSee('Esta casa já está ocupada.');

    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);

    foreach ([3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $component->call('place', 5)->assertSee('A partida terminou. Reinicie para jogar novamente.');
    app(SessionMatchStore::class)->save($matchId, app(SessionMatchStore::class)->find($matchId));

    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
});

test('does not let a stale write replace a finished round', function () {
    $matchId = createClassicSessionMatch();
    $store = app(SessionMatchStore::class);
    $staleState = $store->find($matchId);

    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);
    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    $finishedState = $store->find($matchId);
    expect($finishedState->status)->toBe(MatchStatus::Completed);
    expect(fn () => $store->save($matchId, $staleState))->toThrow(InvalidArgumentException::class);
    expect($store->find($matchId))->toEqual($finishedState);
    expect($store->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
});

test('refuses malformed scoreboard data in the session', function (mixed $storedScore) {
    $matchId = createClassicSessionMatch();
    session()->put('experimental_matches.'.$matchId.'.score', $storedScore);

    expect(fn () => app(SessionMatchStore::class)->score($matchId))
        ->toThrow(UnexpectedValueException::class);
})->with([
    'negative total' => [['x' => -1, 'o' => 0, 'draws' => 0]],
    'null total' => [null],
]);

test('starts an existing match without a stored scoreboard at zero', function () {
    $matchId = createClassicSessionMatch();
    session()->forget('experimental_matches.'.$matchId.'.score');

    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
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

test('renders the three confirmed winning cells and a result dialog', function (array $cells, array $expectedWinningCells, string $winner, string $scoreTestId) {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ($cells as $cell) {
        $component->call('place', $cell);
    }

    preg_match_all('/data-testid="cell-(\d+)"[^>]*data-winning-cell="true"/', $component->html(), $matches);

    $component
        ->assertSee("Jogador {$winner} venceu!")
        ->assertSeeHtml('data-testid="result-dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml("data-testid=\"{$scoreTestId}\"")
        ->assertDispatched('classic-round-finished');
    expect(array_map('intval', $matches[1]))->toBe($expectedWinningCells);
    expect(app(SessionMatchStore::class)->score($matchId)[$winner === 'X' ? 'x' : 'o'])->toBe(1);
})->with([
    'X horizontal' => [[0, 3, 1, 4, 2], [0, 1, 2], 'X', 'result-score-x'],
    'O vertical' => [[0, 1, 2, 4, 3, 7], [1, 4, 7], 'O', 'result-score-o'],
]);

test('does not request a result dialog for an unfinished or rejected action', function () {
    $matchId = createClassicSessionMatch();

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertDontSeeHtml('data-testid="result-dialog"')
        ->call('place', 0)
        ->assertNotDispatched('classic-round-finished')
        ->call('place', 0)
        ->assertSee('Esta casa já está ocupada.')
        ->assertNotDispatched('classic-round-finished');
});

test('shows a draw after nine moves', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ([0, 1, 2, 4, 3, 5, 7, 6, 8] as $cell) {
        $component->call('place', $cell);
    }

    $component
        ->assertSee('Empate!')
        ->assertSeeHtml('data-testid="result-dialog"')
        ->assertSeeHtml('data-testid="result-score-draws"')
        ->assertDispatched('classic-round-finished');
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(0);
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Drawn);
});

test('renders a completed result after a new request without dispatching an opening event', function () {
    $matchId = createClassicSessionMatch();
    $component = Livewire::test('experimental-classic-3x3', ['matchId' => $matchId]);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $component->call('place', $cell);
    }

    Livewire::test('experimental-classic-3x3', ['matchId' => $matchId])
        ->assertSee('Jogador X venceu!')
        ->assertSeeHtml('data-testid="result-dialog"')
        ->assertNotDispatched('classic-round-finished');
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
    expect($firstStore->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(fn () => $secondStore->score($matchId))->toThrow(InvalidArgumentException::class);
});

test('returns not found for an unknown match identifier in the component', function () {
    Livewire::test('experimental-classic-3x3', ['matchId' => str_repeat('A', 40)])->assertNotFound();
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
    'scoreboard' => ['score', ['x' => 99, 'o' => 99, 'draws' => 99], PublicPropertyNotFoundException::class],
]);

test('does not register removed experimental routes in any environment', function () {
    expect(Route::has('experimental.classic.create'))->toBeFalse();
    expect(Route::has('experimental.classic.show'))->toBeFalse();

    $originalRoutes = Route::getRoutes();
    $originalEnvironment = app()->environment();

    try {
        app()->detectEnvironment(fn () => 'production');
        Route::setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        Route::getRoutes()->refreshNameLookups();

        expect(Route::has('experimental.classic.create'))->toBeFalse();
        expect(Route::has('experimental.classic.show'))->toBeFalse();
        expect(Route::has('legacy.classic'))->toBeFalse();
        expect(Route::has('3-basic'))->toBeTrue();
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

    $matchId = app(SessionMatchStore::class)->create($state);
    session()->put('classic.active_match_id', $matchId);

    return $matchId;
}

function classicLocalParticipants(): Participants
{
    return new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'),
        new Participant(ParticipantKind::Guest, 'local-o'),
    );
}
