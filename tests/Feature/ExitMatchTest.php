<?php

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Http\ExitMatchAction;
use App\Http\SessionMatchStore;
use Livewire\Livewire;

function exitModalities(): array
{
    return [
        'classic 3x3' => ['3-basic', 'classic.active_match_id'],
        'movement 3x3' => ['3-move', 'movement3x3.active_match_id'],
        'rotation 3x3' => ['3-rotation', 'rotation3x3.active_match_id'],
        'classic 4x4' => ['4x3-basic', 'classic4x3.active_match_id'],
        'movement 4x4' => ['4x3-move', 'movement4x3.active_match_id'],
        'rotation 4x4' => ['4x3-rotation', 'rotation4x3.active_match_id'],
        'block' => ['4x3-block', 'block4x3.active_match_id'],
        'hidden' => ['4x3-hidden', 'hidden4x3.active_match_id'],
        'four in line' => ['4x4-move', 'movement4x4.active_match_id'],
        'two lines' => ['5x5-2-lines', 'twoLines5x5.active_match_id'],
        'full board' => ['5-full', 'full5x5.active_match_id'],
        'dice' => ['dice', 'dice.active_match_id'],
        'crazy' => ['crazy', 'crazy.active_match_id'],
        'ultimate' => ['ultimate', 'ultimate.active_match_id'],
    ];
}

it('shows Sair after Nova partida and no catalogue header link in each modality', function (string $route, string $sessionKey) {
    $html = $this->get(route($route))->assertOk()->getContent();

    expect($html)->toContain('data-testid="exit-button"')
        ->not->toContain('data-testid="catalog-return"');
    expect(strpos($html, 'data-testid="new-match-button"'))->toBeLessThan(strpos($html, 'data-testid="exit-button"'));
    expect(session($sessionKey))->toBeString();
})->with(exitModalities());

it('returns directly to the catalogue for a fresh match in each modality', function (string $route, string $sessionKey) {
    $this->get(route($route))->assertOk();
    $id = session($sessionKey);

    Livewire::test('exit-match', ['matchId' => $id, 'modality' => $route])
        ->call('requestExit')
        ->assertRedirect(route('welcome'));

    expect(session($sessionKey))->toBe($id);
})->with(exitModalities());

it('opens confirmation for moves, cancels without changes and keeps the same match', function () {
    $this->get(route('3-basic'))->assertOk();
    $id = session('classic.active_match_id');
    Livewire::test('experimental-classic-3x3', ['matchId' => $id, 'publicEntry' => true])->call('place', 0);
    $before = app(SessionMatchStore::class)->find($id);
    $exit = Livewire::test('exit-match', ['matchId' => $id, 'modality' => '3-basic']);

    $exit->call('requestExit')->assertSet('confirming', true)->assertDispatched('exit-confirm');
    $exit->call('cancel')->assertSet('confirming', false)->assertDispatched('exit-cancelled');
    expect(app(SessionMatchStore::class)->find($id))->toEqual($before);
    $exit->call('requestExit')->call('keep')->assertRedirect(route('welcome'));
    expect(session('classic.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->find($id))->toEqual($before);
    $this->get(route('3-basic'))->assertSee('Jogadas realizadas: 1');
});

it('confirms when only the score remains and discards only the current modality', function () {
    $this->get(route('3-basic'))->assertOk();
    $id = session('classic.active_match_id');
    $this->get(route('dice'))->assertOk();
    $otherId = session('dice.active_match_id');
    $winner = app(SessionMatchStore::class)->find($id)->currentPlayer->value;
    $classic = Livewire::test('experimental-classic-3x3', ['matchId' => $id, 'publicEntry' => true]);
    foreach ([0, 3, 1, 4, 2] as $cell) {
        $classic->call('place', $cell);
    }
    $classic->call('restart');
    expect(app(SessionMatchStore::class)->score($id)[$winner])->toBe(1);
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 9, ''));
    $stale = Livewire::test('experimental-classic-3x3', ['matchId' => $id, 'publicEntry' => true]);
    $exit = Livewire::test('exit-match', ['matchId' => $id, 'modality' => '3-basic']);

    $exit->call('requestExit')->assertSet('confirming', true);
    $exit->call('discard')->assertRedirect(route('welcome'));

    $newId = session('classic.active_match_id');
    expect($newId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(app(SessionMatchStore::class)->find($newId)->data['board'])->toBe(array_fill(0, 9, ''));
    expect(session('dice.active_match_id'))->toBe($otherId);
    $stale->call('place', 5)->assertNotFound();
    $this->get(route('3-basic'))->assertOk();
    expect(session('classic.active_match_id'))->toBe($newId);
});

it('recognizes progress stored in the special modality state without exposing hidden marks', function (string $route, string $sessionKey, array $data) {
    $this->get(route($route))->assertOk();
    $id = session($sessionKey);
    $store = app(SessionMatchStore::class);
    $initial = $store->find($id);
    $store->save($id, new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $initial->currentPlayer, $data));

    expect(app(ExitMatchAction::class)->hasProgress($store->find($id), $id))->toBeTrue();
    Livewire::test('exit-match', ['matchId' => $id, 'modality' => $route])
        ->call('requestExit')->assertSet('confirming', true);
})->with([
    'pending dice roll' => ['dice', 'dice.active_match_id', ['boards' => [array_fill(0, 9, ''), array_fill(0, 9, '')], 'pending_roll' => 1, 'last_roll' => 1]],
    'used crazy piece' => ['crazy', 'crazy.active_match_id', ['stacks' => [['x-1-1'], [], [], [], [], [], [], [], []]]],
    'ultimate subboard' => ['ultimate', 'ultimate.active_match_id', ['boards' => [['x', '', '', '', '', '', '', '', ''], ...array_fill(0, 8, array_fill(0, 9, ''))], 'target_board' => 0]],
    'hidden position' => ['4x3-hidden', 'hidden4x3.active_match_id', ['board' => ['x', ...array_fill(0, 15, '')], 'order' => ['x' => [0], 'o' => []]]],
]);

it('creates a fresh active match with zero score when discarding each modality', function (string $route, string $sessionKey) {
    $this->get(route($route))->assertOk();
    $id = session($sessionKey);
    $exit = app(ExitMatchAction::class);

    $exit->discard($route, $id);

    $newId = session($sessionKey);
    expect($newId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect($exit->hasProgress(app(SessionMatchStore::class)->find($newId), $newId))->toBeFalse();
    Livewire::test('exit-match', ['matchId' => $id, 'modality' => $route])->assertNotFound();
})->with(exitModalities());

it('requires an open confirmation and the active match before changing the session', function () {
    $this->get(route('dice'))->assertOk();
    $id = session('dice.active_match_id');
    $exit = Livewire::test('exit-match', ['matchId' => $id, 'modality' => 'dice']);

    $exit->call('discard')->assertNotFound();
    expect(session('dice.active_match_id'))->toBe($id);
});
