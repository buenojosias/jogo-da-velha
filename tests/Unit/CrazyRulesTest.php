<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\CrazyRules;

function crazyInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new CrazyRules)->initialState(new Modality('crazy'), new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o'),
    ), $starter);
}

function crazyPlace(MatchState $state, string $piece, int $cell): MatchState
{
    return (new CrazyRules)->apply($state, new GameAction($state->currentPlayer, 'place', ['piece' => $piece, 'cell' => $cell]))->state;
}

test('starts with nine empty stacks and three copies of each size per player', function (PlayerMark $starter) {
    $state = crazyInitial($starter);
    $rules = new CrazyRules;
    expect($state->data['stacks'])->toBe(array_fill(0, 9, []));
    expect($state->currentPlayer)->toBe($starter);
    expect($rules->availablePieces($state, PlayerMark::X))->toHaveCount(9);
    expect($rules->availablePieces($state, PlayerMark::O))->toHaveCount(9);
})->with([PlayerMark::X, PlayerMark::O]);

test('stacks larger pieces without losing the covered piece and alternates only on valid moves', function () {
    $rules = new CrazyRules;
    $initial = crazyInitial();
    $afterX = crazyPlace($initial, 'x-1-1', 0);
    $afterO = crazyPlace($afterX, 'o-2-1', 0);
    expect($afterO->data['stacks'][0])->toBe(['x-1-1', 'o-2-1']);
    expect($rules->availablePieces($afterO, PlayerMark::X))->not->toContain('x-1-1');
    expect($afterO->currentPlayer)->toBe(PlayerMark::X);
    expect($initial->data['stacks'][0])->toBe([]);

    foreach ([['x-2-1', 0, 'piece_not_larger'], ['o-3-1', 1, 'not_your_piece'], ['x-1-1', 1, 'piece_used'], ['x-3-1', 9, 'invalid_cell']] as [$piece, $cell, $reason]) {
        $result = $rules->apply($afterO, new GameAction(PlayerMark::X, 'place', ['piece' => $piece, 'cell' => $cell]));
        expect($result->validation->reason)->toBe($reason);
        expect($result->state)->toBe($afterO);
    }
});

test('keeps a coverable line in progress and ends on an un-coverable line', function () {
    $state = crazyInitial();
    foreach ([['x-1-1', 0], ['o-1-1', 3], ['x-1-2', 1], ['o-1-2', 4], ['x-1-3', 2]] as [$piece, $cell]) {
        $state = crazyPlace($state, $piece, $cell);
    }
    expect($state->status)->toBe(MatchStatus::InProgress);
    $rules = new CrazyRules;
    $stacks = array_fill(0, 9, []);
    $stacks[0] = ['x-1-1'];
    $stacks[1] = ['x-1-2'];
    $stacks[2] = ['x-1-3'];
    expect($rules->definitiveLine($stacks, PlayerMark::X))->toBeNull();
    $stacks[3] = ['x-3-1'];
    $stacks[4] = ['x-3-2'];
    $stacks[5] = ['x-3-3'];
    expect($rules->definitiveLine($stacks, PlayerMark::X))->toBe([3, 4, 5]);
});

test('draws on the eighteenth used piece when no visible line remains', function () {
    $initial = crazyInitial();
    $stacks = [
        ['x-1-1', 'x-2-3', 'x-3-1'],
        ['x-1-2', 'o-2-2', 'o-3-1'],
        ['x-1-3', 'o-2-3', 'x-3-2'],
        ['o-1-1', 'x-3-3'],
        ['o-1-2', 'o-3-2'],
        ['o-1-3', 'o-3-3'],
        ['o-2-1'],
        ['x-2-1'],
        [],
    ];
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, ['stacks' => $stacks]);
    $result = (new CrazyRules)->apply($state, new GameAction(PlayerMark::X, 'place', ['piece' => 'x-2-2', 'cell' => 8]));
    expect($result->state->status)->toBe(MatchStatus::Drawn);
    expect($result->state->winner)->toBeNull();
    expect($result->state->currentPlayer)->toBeNull();
});

test('wins with three large visible pieces and rejects actions after finishing', function () {
    $state = crazyInitial();
    foreach ([['x-3-1', 0], ['o-1-1', 3], ['x-3-2', 1], ['o-1-2', 4], ['x-3-3', 2]] as [$piece, $cell]) {
        $state = crazyPlace($state, $piece, $cell);
    }
    $rules = new CrazyRules;
    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($state))->toBe([0, 1, 2]);
    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['piece' => 'o-3-1', 'cell' => 5]));
    expect($result->validation->reason)->toBe('game_finished');
    expect($result->state)->toBe($state);
});

test('recognizes a definitive visible line in every direction', function (array $line) {
    $stacks = array_fill(0, 9, []);
    foreach ($line as $copy => $cell) {
        $stacks[$cell] = ['o-3-'.($copy + 1)];
    }
    expect((new CrazyRules)->definitiveLine($stacks, PlayerMark::O))->toBe($line);
})->with([
    [[0, 1, 2]], [[3, 4, 5]], [[6, 7, 8]],
    [[0, 3, 6]], [[1, 4, 7]], [[2, 5, 8]],
    [[0, 4, 8]], [[2, 4, 6]],
]);

test('rejects malformed stacks and does not mutate state during validation', function () {
    $rules = new CrazyRules;
    $state = crazyInitial();
    $action = new GameAction(PlayerMark::X, 'place', ['piece' => 'x-1-1', 'cell' => 0]);
    expect($rules->validate($state, $action)->valid)->toBeTrue();
    expect($rules->validate($state, $action)->valid)->toBeTrue();
    expect($state->data['stacks'][0])->toBe([]);
    $stacks = array_fill(0, 9, []);
    $stacks[0] = ['x-2-1', 'o-1-1'];
    $corrupt = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X, ['stacks' => $stacks]);
    expect(fn () => $rules->stacks($corrupt))->toThrow(InvalidArgumentException::class);
});
