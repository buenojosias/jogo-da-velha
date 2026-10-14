<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Move3x3Rules;

test('starts with an empty board and the chosen player', function (PlayerMark $starter) {
    $modality = new Modality('3-move');
    $participants = movementParticipants();

    $state = (new Move3x3Rules)->initialState($modality, $participants, $starter);

    expect($state->modality)->toBe($modality);
    expect($state->participants)->toBe($participants);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->currentPlayer)->toBe($starter);
    expect($state->winner)->toBeNull();
    expect($state->data)->toBe([
        'board' => array_fill(0, 9, ''),
        'pieces' => ['x' => [], 'o' => []],
        'move_count' => ['x' => 0, 'o' => 0],
        'last_moved_piece' => ['x' => null, 'o' => null],
    ]);
})->with(['X' => [PlayerMark::X], 'O' => [PlayerMark::O]]);

test('rejects another modality', function () {
    expect(fn () => (new Move3x3Rules)->initialState(new Modality('3-basic'), movementParticipants(), PlayerMark::X))
        ->toThrow(InvalidArgumentException::class);
});

test('places alternating pieces without changing the original state', function () {
    $rules = new Move3x3Rules;
    $initial = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::O);

    $first = $rules->apply($initial, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));
    $second = $rules->apply($first->state, new GameAction(PlayerMark::X, 'place', ['cell' => 8]));

    expect($first->validation->valid)->toBeTrue();
    expect($first->state)->not->toBe($initial);
    expect($first->state->data['board'][0])->toBe('o');
    expect($first->state->currentPlayer)->toBe(PlayerMark::X);
    expect($second->state->data['board'][8])->toBe('x');
    expect($second->state->currentPlayer)->toBe(PlayerMark::O);
    expect($initial->data['board'])->toBe(array_fill(0, 9, ''));
});

test('enters movement only after both players have placed three pieces', function () {
    $rules = new Move3x3Rules;
    $state = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);

    foreach ([0, 1, 2, 3, 4] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    expect($rules->validate($state, new GameAction(PlayerMark::O, 'move', ['from' => 1, 'to' => 8]))->reason)->toBe('movement_not_started');
    expect($state->currentPlayer)->toBe(PlayerMark::O);

    $sixth = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 6]));

    expect($sixth->state->status)->toBe(MatchStatus::InProgress);
    expect($sixth->state->currentPlayer)->toBe(PlayerMark::X);
    expect($sixth->state->data['board'])->toBe(['x', 'o', 'x', 'o', 'x', '', 'o', '', '']);
    expect($sixth->state->data['pieces'])->toBe(['x' => [0, 2, 4], 'o' => [1, 3, 6]]);
    expect($rules->validate($sixth->state, new GameAction(PlayerMark::X, 'place', ['cell' => 5]))->reason)->toBe('placement_complete');
    expect($rules->validate($sixth->state, new GameAction(PlayerMark::X, 'move', ['from' => 2, 'to' => 8]))->valid)->toBeTrue();

    $latePlacement = $rules->apply($sixth->state, new GameAction(PlayerMark::X, 'place', ['cell' => 5]));
    expect($latePlacement->validation->reason)->toBe('placement_complete');
    expect($latePlacement->state)->toBe($sixth->state);
});

test('moves a piece to a distant empty cell and frees its origin', function () {
    $rules = new Move3x3Rules;
    $state = movementAfterPlacements();

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 0, 'to' => 5]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state)->not->toBe($state);
    expect($result->state->data['board'][0])->toBe('');
    expect($result->state->data['board'][5])->toBe('x');
    expect($result->state->data['board'])->toHaveCount(9);
    expect(count(array_keys($result->state->data['board'], 'x', true)))->toBe(3);
    expect(count(array_keys($result->state->data['board'], 'o', true)))->toBe(3);
    expect($result->state->currentPlayer)->toBe(PlayerMark::O);
    expect($state->data['board'][0])->toBe('x');
    expect($state->data['board'][5])->toBe('');
});

test('recognizes a placement victory and returns its winning cells', function (array $moves, PlayerMark $winner, array $line) {
    $rules = new Move3x3Rules;
    $state = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);

    foreach ($moves as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe($winner);
    expect($state->currentPlayer)->toBeNull();
    expect($rules->winningLine($state))->toBe($line);
})->with([
    'X horizontal' => [[0, 3, 1, 4, 2], PlayerMark::X, [0, 1, 2]],
    'O horizontal on sixth placement' => [[0, 3, 8, 4, 6, 5], PlayerMark::O, [3, 4, 5]],
    'X diagonal' => [[0, 1, 4, 2, 8], PlayerMark::X, [0, 4, 8]],
]);

test('recognizes a movement victory for either player', function (array $moves, PlayerMark $winner, array $line) {
    $rules = new Move3x3Rules;
    $state = movementAfterPlacements();

    foreach ($moves as [$from, $to]) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'move', ['from' => $from, 'to' => $to]))->state;
    }

    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe($winner);
    expect($rules->winningLine($state))->toBe($line);
})->with([
    'X diagonal with distant move' => [[[2, 8]], PlayerMark::X, [0, 4, 8]],
    'O vertical after X moves' => [[[0, 5], [1, 0]], PlayerMark::O, [0, 3, 6]],
]);

test('reports each classic winning line without changing the completed state', function (array $line) {
    $rules = new Move3x3Rules;
    $board = array_fill(0, 9, '');

    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }

    $remainingCells = array_values(array_diff(range(0, 8), $line));
    $board[$remainingCells[0]] = 'o';
    $board[$remainingCells[1]] = 'o';
    $state = new MatchState(new Modality('3-move'), movementParticipants(), MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);

    expect($rules->winningLine($state))->toBe($line);
    expect($state->data['board'])->toBe($board);
})->with([
    'top row' => [[0, 1, 2]],
    'middle row' => [[3, 4, 5]],
    'bottom row' => [[6, 7, 8]],
    'left column' => [[0, 3, 6]],
    'middle column' => [[1, 4, 7]],
    'right column' => [[2, 5, 8]],
    'descending diagonal' => [[0, 4, 8]],
    'ascending diagonal' => [[2, 4, 6]],
]);

test('rejects an occupied placement and keeps the same state', function () {
    $rules = new Move3x3Rules;
    $initial = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);
    $state = $rules->apply($initial, new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state;

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($result->validation->reason)->toBe('cell_occupied');
    expect($result->state)->toBe($state);
    expect($state->data['board'][0])->toBe('x');
});

test('rejects invalid placement indices without changing the state', function (array $payload) {
    $rules = new Move3x3Rules;
    $state = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', $payload));

    expect($result->validation->reason)->toBe('invalid_cell');
    expect($result->state)->toBe($state);
})->with([
    'negative' => [['cell' => -1]],
    'past the board' => [['cell' => 9]],
    'numeric string' => [['cell' => '4']],
    'float' => [['cell' => 4.0]],
    'boolean' => [['cell' => true]],
    'missing' => [[]],
]);

test('rejects movement errors without changing the state', function (array $payload, string $reason) {
    $rules = new Move3x3Rules;
    $state = movementAfterPlacements();

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'move', $payload));

    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
    expect($state->data['board'])->toBe(['x', 'o', 'x', 'o', 'x', '', 'o', '', '']);
})->with([
    'empty source' => [['from' => 5, 'to' => 7], 'empty_source'],
    'opponent source' => [['from' => 1, 'to' => 5], 'not_your_piece'],
    'occupied target' => [['from' => 0, 'to' => 1], 'cell_occupied'],
    'same source and target' => [['from' => 0, 'to' => 0], 'same_cell'],
    'negative source' => [['from' => -1, 'to' => 5], 'invalid_cell'],
    'out-of-range target' => [['from' => 0, 'to' => 9], 'invalid_cell'],
    'string source' => [['from' => '0', 'to' => 5], 'invalid_cell'],
    'missing target' => [['from' => 0], 'invalid_cell'],
]);

test('rejects a fourth placement by a player before the other player has placed three pieces', function () {
    $rules = new Move3x3Rules;
    $state = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);

    foreach ([0, 1, 2, 3, 4] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    $stateWithXTurn = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X, $state->data);
    $result = $rules->apply($stateWithXTurn, new GameAction(PlayerMark::X, 'place', ['cell' => 5]));

    expect($result->validation->reason)->toBe('piece_limit_reached');
    expect($result->state)->toBe($stateWithXTurn);
});

test('rejects wrong turns, unknown actions, and actions after victory', function () {
    $rules = new Move3x3Rules;
    $initial = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);
    $wrongTurn = $rules->apply($initial, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));
    $unknown = $rules->apply($initial, new GameAction(PlayerMark::X, 'roll', []));
    $prematureMove = $rules->apply($initial, new GameAction(PlayerMark::X, 'move', ['from' => 0, 'to' => 8]));
    $state = $initial;

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    $afterVictory = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 5]));

    expect($wrongTurn->validation->reason)->toBe('not_your_turn');
    expect($wrongTurn->state)->toBe($initial);
    expect($unknown->validation->reason)->toBe('unsupported_action');
    expect($unknown->state)->toBe($initial);
    expect($prematureMove->validation->reason)->toBe('movement_not_started');
    expect($prematureMove->state)->toBe($initial);
    expect($afterVictory->validation->reason)->toBe('game_finished');
    expect($afterVictory->state)->toBe($state);
});

test('reports the first applicable rejection in a stable order', function () {
    $rules = new Move3x3Rules;
    $state = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);

    expect($rules->validate($state, new GameAction(PlayerMark::O, 'roll', ['cell' => -1]))->reason)->toBe('unsupported_action');
    expect($rules->validate($state, new GameAction(PlayerMark::O, 'place', ['cell' => -1]))->reason)->toBe('not_your_turn');
    expect($rules->validate($state, new GameAction(PlayerMark::X, 'move', ['from' => -1, 'to' => -1]))->reason)->toBe('movement_not_started');
});

test('validate has no effects and successive movements never produce an automatic draw', function () {
    $rules = new Move3x3Rules;
    $state = movementAfterPlacements();

    expect($rules->winningLine($state))->toBeNull();
    expect($rules->validate($state, new GameAction(PlayerMark::X, 'move', ['from' => 0, 'to' => 5]))->valid)->toBeTrue();
    expect($rules->validate($state, new GameAction(PlayerMark::X, 'move', ['from' => 0, 'to' => 5]))->valid)->toBeTrue();
    expect($state->data['board'][0])->toBe('x');

    for ($turn = 0; $turn < 24; $turn++) {
        $next = null;
        foreach ($state->data['pieces'][$state->currentPlayer->value] as $from) {
            foreach (array_keys($state->data['board'], '', true) as $to) {
                $candidate = $rules->apply($state, new GameAction($state->currentPlayer, 'move', ['from' => $from, 'to' => $to]));
                if ($candidate->validation->valid && $candidate->state->status === MatchStatus::InProgress) {
                    $next = $candidate->state;
                    break 2;
                }
            }
        }

        expect($next)->not->toBeNull();
        $state = $next;
    }

    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->currentPlayer)->toBe(PlayerMark::X);
    expect($state->data['move_count'])->toBe(['x' => 12, 'o' => 12]);
    expect($rules->winningLine($state))->toBeNull();
});

function movementParticipants(): Participants
{
    return new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'),
        new Participant(ParticipantKind::Guest, 'local-o'),
    );
}

function movementAfterPlacements(): MatchState
{
    $rules = new Move3x3Rules;
    $state = $rules->initialState(new Modality('3-move'), movementParticipants(), PlayerMark::X);

    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    return $state;
}

test('allows three individual moves of the same piece before blocking it on the fourth', function () {
    $rules = new Move3x3Rules;
    $state = movementAfterPlacements();

    foreach ([[0, 5], [1, 7], [5, 0], [7, 1], [0, 5], [1, 7]] as [$from, $to]) {
        $result = $rules->apply($state, new GameAction($state->currentPlayer, 'move', ['from' => $from, 'to' => $to]));
        expect($result->validation->valid)->toBeTrue();
        $state = $result->state;
    }

    expect($state->data['move_count'])->toBe(['x' => 3, 'o' => 3]);
    expect($state->data['last_moved_piece'])->toBe(['x' => 0, 'o' => 0]);
    expect($rules->blockedCell($state))->toBe(5);
    $blocked = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 5, 'to' => 0]));
    expect($blocked->validation->reason)->toBe('same_piece_twice');
    expect($blocked->state)->toBe($state);
    $availableMoves = 0;
    foreach ([2, 4] as $from) {
        foreach ([0, 1, 8] as $to) {
            if ($rules->validate($state, new GameAction(PlayerMark::X, 'move', ['from' => $from, 'to' => $to]))->valid) {
                $availableMoves++;
            }
        }
    }
    expect($availableMoves)->toBe(6);

    $differentPiece = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 2, 'to' => 0]));
    expect($differentPiece->validation->valid)->toBeTrue();
    expect($differentPiece->state->data['pieces']['x'])->toBe([5, 0, 4]);
    expect($differentPiece->state->data['move_count'])->toBe(['x' => 4, 'o' => 3]);
    expect($rules->blockedCell($differentPiece->state))->toBe(7);

    $otherBlocked = $rules->apply($differentPiece->state, new GameAction(PlayerMark::O, 'move', ['from' => 7, 'to' => 2]));
    expect($otherBlocked->validation->reason)->toBe('same_piece_twice');
    expect($otherBlocked->state)->toBe($differentPiece->state);

    $otherDifferent = $rules->apply($differentPiece->state, new GameAction(PlayerMark::O, 'move', ['from' => 3, 'to' => 2]));
    expect($otherDifferent->validation->valid)->toBeTrue();
    expect($otherDifferent->state->data['move_count'])->toBe(['x' => 4, 'o' => 4]);
    expect($rules->blockedCell($otherDifferent->state))->toBe(0);

    $availableAgain = $rules->apply($otherDifferent->state, new GameAction(PlayerMark::X, 'move', ['from' => 5, 'to' => 3]));
    expect($availableAgain->validation->valid)->toBeTrue();
    expect($availableAgain->state->data['pieces']['x'][0])->toBe(3);
    expect($availableAgain->state->data['last_moved_piece']['x'])->toBe(0);
});

test('allows a different piece to complete a line after the restriction activates', function () {
    $rules = new Move3x3Rules;
    $initial = movementAfterPlacements();
    $data = [
        'board' => ['x', 'x', '', 'o', 'x', 'o', '', '', 'o'],
        'pieces' => ['x' => [0, 1, 4], 'o' => [3, 5, 8]],
        'move_count' => ['x' => 3, 'o' => 3],
        'last_moved_piece' => ['x' => 0, 'o' => 0],
    ];
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, $data);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 4, 'to' => 2]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state->status)->toBe(MatchStatus::Completed);
    expect($result->state->winner)->toBe(PlayerMark::X);
    expect($result->state->data['move_count']['x'])->toBe(4);
    expect($rules->winningLine($result->state))->toBe([0, 1, 2]);
});
