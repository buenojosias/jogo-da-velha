<?php

use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Games\Rules\Classic4x3Rules;
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

test('creates a four by four match with a server-selected starting player', function (int $seed, PlayerMark $starter) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));

    $oppositeMark = $starter === PlayerMark::X ? 'o' : 'x';
    $response = $this->get(route('experimental.classic4x3.create', ['starting_player' => $oppositeMark]))->assertRedirect();
    $matchId = basename($response->headers->get('Location'));
    $page = $this->get(route('experimental.classic4x3.show', $matchId));

    $page->assertOk()->assertViewIs('experimental-classic-4x3')
        ->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('grid-cols-4');
    expect(substr_count($page->getContent(), 'data-testid="cell-'))->toBe(16);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe($starter);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);

    $this->get(route('experimental.classic4x3.show', $matchId))->assertOk()->assertSee('Vez do jogador '.$starter->name);
    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe($starter);
})->with([
    'X starter' => [0, PlayerMark::X],
    'O starter' => [1, PlayerMark::O],
]);

test('persists moves and turn changes between Livewire requests', function () {
    $matchId = create4x3SessionMatch();

    Livewire::test('experimental-classic-4x3', ['matchId' => $matchId])
        ->assertSee('Vez do jogador X')
        ->call('place', 0)
        ->assertSee('Vez do jogador O');

    Livewire::test('experimental-classic-4x3', ['matchId' => $matchId])
        ->assertSee('Jogadas realizadas: 1')
        ->call('place', 15)
        ->assertSee('Vez do jogador X');

    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][0])->toBe('x');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][15])->toBe('o');
});

test('shows an error for rejected moves without changing the board or score', function () {
    $matchId = create4x3SessionMatch();

    Livewire::test('experimental-classic-4x3', ['matchId' => $matchId])
        ->call('place', 0)
        ->call('place', 0)
        ->assertSee('Esta casa já está ocupada.')
        ->call('place', '15')
        ->assertSee('Escolha uma casa válida.');

    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][0])->toBe('x');
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'][15])->toBe('');
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('shows the confirmed winning trio and scores each direction', function (array $moves, array $line, PlayerMark $winner) {
    $matchId = create4x3SessionMatch();
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $matchId]);

    foreach ($moves as $cell) {
        $component->call('place', $cell);
    }

    preg_match_all('/data-testid="cell-(\d+)"[^>]*data-winning-cell="true"/', $component->html(), $matches);

    $component->assertSee('Jogador '.$winner->name.' venceu!')
        ->assertSeeHtml('data-testid="result-dialog"')
        ->assertDispatched('classic4x3-round-finished');
    expect(array_map('intval', $matches[1]))->toBe($line);
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($matchId)[$winner->value])->toBe(1);

    $component->call('place', 15)->assertSee('A partida terminou. Reinicie para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($matchId)[$winner->value])->toBe(1);
})->with([
    'X horizontal' => [[0, 4, 1, 5, 2], [0, 1, 2], PlayerMark::X],
    'O vertical' => [[0, 1, 2, 5, 4, 9], [1, 5, 9], PlayerMark::O],
    'X descending diagonal' => [[0, 1, 5, 2, 10], [0, 5, 10], PlayerMark::X],
    'O ascending diagonal' => [[0, 7, 1, 10, 4, 13], [7, 10, 13], PlayerMark::O],
]);

test('counts a draw and shows a neutral result without highlighted cells', function () {
    $matchId = create4x3SessionMatch();
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $matchId]);

    foreach ([1, 0, 3, 2, 4, 6, 5, 7, 10, 8, 11, 9, 12, 14, 13, 15] as $cell) {
        $component->call('place', $cell);
    }

    $component->assertSee('Empate!')->assertDispatched('classic4x3-round-finished');
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(0);
    expect(app(SessionMatchStore::class)->find($matchId)->status)->toBe(MatchStatus::Drawn);
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 1]);
});

test('restarts with the opposite starter while preserving the score and surviving reload', function () {
    $matchId = create4x3SessionMatch();
    $component = Livewire::test('experimental-classic-4x3', ['matchId' => $matchId]);

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('place', $cell);
    }

    $this->get(route('experimental.classic4x3.show', $matchId))
        ->assertOk()->assertSee('Jogador X venceu!');
    $component->call('restart')->assertSee('Vez do jogador O')->assertSee('Jogadas realizadas: 0');

    expect(app(SessionMatchStore::class)->startingPlayer($matchId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->find($matchId)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
    $this->get(route('experimental.classic4x3.show', $matchId))->assertOk()->assertSee('Vez do jogador O');
});

test('keeps classic modalities and their scoreboards independent', function () {
    $classic3Id = create3x3SessionMatchFor4x3Test();
    $classic4Id = create4x3SessionMatch();

    Livewire::test('experimental-classic-4x3', ['matchId' => $classic4Id])->call('place', 0);

    expect(app(SessionMatchStore::class)->find($classic3Id)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(app(SessionMatchStore::class)->find($classic4Id)->data['board'][0])->toBe('x');
    expect(app(SessionMatchStore::class)->score($classic3Id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(session('classic.active_match_id'))->toBeNull();
    Livewire::test('experimental-classic-3x3', ['matchId' => $classic4Id])->assertNotFound();
    Livewire::test('experimental-classic-4x3', ['matchId' => $classic3Id])->assertNotFound();
});

test('does not change the public three by three active match when creating an experimental four by four match', function () {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $this->get(route('3-basic'))->assertOk();
    $publicMatchId = session('classic.active_match_id');

    $this->get(route('experimental.classic4x3.create'))->assertRedirect();

    expect(session('classic.active_match_id'))->toBe($publicMatchId);
    expect(app(SessionMatchStore::class)->find($publicMatchId)->data['board'])->toBe(array_fill(0, 9, ''));
    $this->get(route('4x3-basic'))->assertOk()->assertViewIs('experimental-classic-4x3');
});

test('replaces a public pointer that accidentally targets a four by four match', function () {
    $classic4Id = create4x3SessionMatch();
    session()->put('classic.active_match_id', $classic4Id);

    $this->get(route('3-basic'))->assertOk()->assertSee('Tabuleiro 3 por 3');

    $publicId = session('classic.active_match_id');
    expect($publicId)->not->toBe($classic4Id);
    expect(app(SessionMatchStore::class)->find($publicId)->modality->key)->toBe('3-basic');
    expect(app(SessionMatchStore::class)->find($classic4Id)->data['board'])->toBe(array_fill(0, 16, ''));
});

test('rejects cross-modality saves and restarts without changing either record', function () {
    $classic3Id = create3x3SessionMatchFor4x3Test();
    $classic4Id = create4x3SessionMatch();
    $store = app(SessionMatchStore::class);

    expect(fn () => $store->save($classic3Id, $store->find($classic4Id)))->toThrow(InvalidArgumentException::class);
    expect(fn () => $store->restart($classic4Id, $store->find($classic3Id)))->toThrow(InvalidArgumentException::class);
    expect($store->find($classic3Id)->data['board'])->toBe(array_fill(0, 9, ''));
    expect($store->find($classic4Id)->data['board'])->toBe(array_fill(0, 16, ''));
});

test('refuses mismatched board size and corrupt session state', function (string $field, mixed $value) {
    $matchId = create4x3SessionMatch();
    session()->put('experimental_matches.'.$matchId.'.state.'.$field, $value);

    expect(fn () => app(SessionMatchStore::class)->find($matchId))->toThrow(UnexpectedValueException::class);
    $this->get(route('experimental.classic4x3.show', $matchId))->assertNotFound();
})->with([
    'nine cells for four by four' => ['data.board', array_fill(0, 9, '')],
    'invalid mark' => ['data.board', array_replace(array_fill(0, 16, ''), [1 => 'z'])],
    'unknown modality' => ['modality', 'unknown'],
    'invalid status' => ['status', 'finished'],
    'invalid participant' => ['participants.x.id', ''],
]);

test('rejects a corrupt four by four scoreboard', function () {
    $matchId = create4x3SessionMatch();
    session()->put('experimental_matches.'.$matchId.'.score', ['x' => -1, 'o' => 0, 'draws' => 0]);

    expect(fn () => app(SessionMatchStore::class)->score($matchId))->toThrow(UnexpectedValueException::class);
    $this->get(route('experimental.classic4x3.show', $matchId))->assertNotFound();
});

test('does not expose a match from another session or a mutable official property', function () {
    $handler = new ArraySessionHandler(120);
    $firstSession = new Store('classic-test', $handler, 'first-session');
    $secondSession = new Store('classic-test', $handler, 'second-session');
    $state = (new Classic4x3Rules)->initialState(new Modality('4x3-basic'), classic4x3TestParticipants(), PlayerMark::X);
    $matchId = (new SessionMatchStore($firstSession))->create($state);

    expect((new SessionMatchStore($secondSession))->find($matchId))->toBeNull();
    Livewire::test('experimental-classic-4x3', ['matchId' => $matchId])->assertNotFound();

    $currentSessionId = create4x3SessionMatch();
    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $currentSessionId])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $currentSessionId])->set('board', array_fill(0, 16, 'x')))
        ->toThrow(PublicPropertyNotFoundException::class);
    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $currentSessionId])->set('winner', 'x'))
        ->toThrow(PublicPropertyNotFoundException::class);
    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $currentSessionId])->set('currentPlayer', 'o'))
        ->toThrow(PublicPropertyNotFoundException::class);
    expect(fn () => Livewire::test('experimental-classic-4x3', ['matchId' => $currentSessionId])->set('score', ['x' => 99, 'o' => 99, 'draws' => 99]))
        ->toThrow(PublicPropertyNotFoundException::class);
});

test('does not register the experimental four by four routes in production', function () {
    $originalRoutes = Route::getRoutes();
    $originalEnvironment = app()->environment();

    try {
        app()->detectEnvironment(fn () => 'production');
        Route::setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        Route::getRoutes()->refreshNameLookups();

        expect(Route::has('experimental.classic4x3.create'))->toBeFalse();
        expect(Route::has('experimental.classic4x3.show'))->toBeFalse();
        expect(Route::has('4x3-basic'))->toBeTrue();
    } finally {
        Route::setRoutes($originalRoutes);
        app()->detectEnvironment(fn () => $originalEnvironment);
    }
});

function classic4x3TestParticipants(): Participants
{
    return new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'),
        new Participant(ParticipantKind::Guest, 'local-o'),
    );
}

function create4x3SessionMatch(): string
{
    $state = (new Classic4x3Rules)->initialState(new Modality('4x3-basic'), classic4x3TestParticipants(), PlayerMark::X);

    return app(SessionMatchStore::class)->create($state);
}

function create3x3SessionMatchFor4x3Test(): string
{
    $state = (new Classic3x3Rules)->initialState(new Modality('3-basic'), classic4x3TestParticipants(), PlayerMark::X);

    return app(SessionMatchStore::class)->create($state);
}
