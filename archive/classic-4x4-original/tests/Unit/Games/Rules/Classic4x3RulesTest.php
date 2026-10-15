<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic4x3Rules;

test('starts with sixteen empty cells and the chosen player', function (PlayerMark $startingPlayer) {
    $modality = new Modality('4x3-basic');
    $participants = classic4x3Participants();

    $state = (new Classic4x3Rules)->initialState($modality, $participants, $startingPlayer);

    expect($state->modality)->toBe($modality);
    expect($state->participants)->toBe($participants);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->currentPlayer)->toBe($startingPlayer);
    expect($state->winner)->toBeNull();
    expect($state->data)->toBe(['board' => array_fill(0, 16, '')]);
})->with([
    'X starts' => [PlayerMark::X],
    'O starts' => [PlayerMark::O],
]);

test('rejects a modality other than 4x3-basic', function () {
    $rules = new Classic4x3Rules;

    expect(fn () => $rules->initialState(new Modality('3-basic'), classic4x3Participants(), PlayerMark::X))
        ->toThrow(InvalidArgumentException::class);
});

test('places marks and alternates turns without changing earlier states', function (PlayerMark $startingPlayer, PlayerMark $nextPlayer) {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), $startingPlayer);

    $first = $rules->apply($initial, new GameAction($startingPlayer, 'place', ['cell' => 0]));
    $second = $rules->apply($first->state, new GameAction($nextPlayer, 'place', ['cell' => 15]));

    expect($first->validation->valid)->toBeTrue();
    expect($first->state)->not->toBe($initial);
    expect($first->state->data['board'][0])->toBe($startingPlayer->value);
    expect($first->state->currentPlayer)->toBe($nextPlayer);
    expect($second->validation->valid)->toBeTrue();
    expect($second->state->data['board'][15])->toBe($nextPlayer->value);
    expect($second->state->currentPlayer)->toBe($startingPlayer);
    expect($initial->data['board'])->toBe(array_fill(0, 16, ''));
    expect($first->state->data['board'][15])->toBe('');
})->with([
    'X then O' => [PlayerMark::X, PlayerMark::O],
    'O then X' => [PlayerMark::O, PlayerMark::X],
]);

test('rejects a move by the player outside their turn without changing the state', function () {
    $rules = new Classic4x3Rules;
    $state = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($result->validation->reason)->toBe('not_your_turn');
    expect($result->state)->toBe($state);
    expect($state->data['board'])->toBe(array_fill(0, 16, ''));
});

test('rejects an occupied cell without changing the state', function () {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $state = $rules->apply($initial, new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state;

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($result->validation->reason)->toBe('cell_occupied');
    expect($result->state)->toBe($state);
    expect($state->data['board'][0])->toBe('x');
    expect($state->currentPlayer)->toBe(PlayerMark::O);
});

test('rejects an invalid cell index without changing the state', function (array $payload) {
    $rules = new Classic4x3Rules;
    $state = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', $payload));

    expect($result->validation->reason)->toBe('invalid_cell');
    expect($result->state)->toBe($state);
    expect($state->data['board'])->toBe(array_fill(0, 16, ''));
})->with([
    'negative' => [['cell' => -1]],
    'past the board' => [['cell' => 16]],
    'numeric string' => [['cell' => '4']],
    'floating point' => [['cell' => 4.0]],
    'boolean' => [['cell' => true]],
    'null' => [['cell' => null]],
    'missing' => [[]],
]);

test('rejects an unsupported action before inspecting turn or cell', function () {
    $rules = new Classic4x3Rules;
    $state = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'move', ['cell' => -1]));
    $wrongTurn = $rules->validate($state, new GameAction(PlayerMark::O, 'place', ['cell' => -1]));

    expect($result->validation->reason)->toBe('unsupported_action');
    expect($result->state)->toBe($state);
    expect($wrongTurn->reason)->toBe('not_your_turn');
});

test('rejects actions while the match is awaiting', function () {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::Awaiting, PlayerMark::X, $initial->data);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]));

    expect($result->validation->reason)->toBe('game_not_started');
    expect($result->state)->toBe($state);
});

test('recognizes every horizontal, vertical and diagonal three-cell segment', function (array $line) {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $board = array_fill(0, 16, '');
    $board[$line[0]] = 'x';
    $board[$line[1]] = 'x';
    $board[array_values(array_diff(range(0, 15), $line))[0]] = 'o';
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, ['board' => $board]);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => $line[2]]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state->status)->toBe(MatchStatus::Completed);
    expect($result->state->winner)->toBe(PlayerMark::X);
    expect($result->state->currentPlayer)->toBeNull();
    expect($rules->winningLine($result->state))->toBe($line);
    expect($state->data['board'][$line[2]])->toBe('');
})->with([
    'horizontal row 1 left' => [[0, 1, 2]],
    'horizontal row 1 right' => [[1, 2, 3]],
    'horizontal row 2 left' => [[4, 5, 6]],
    'horizontal row 2 right' => [[5, 6, 7]],
    'horizontal row 3 left' => [[8, 9, 10]],
    'horizontal row 3 right' => [[9, 10, 11]],
    'horizontal row 4 left' => [[12, 13, 14]],
    'horizontal row 4 right' => [[13, 14, 15]],
    'vertical column 1 top' => [[0, 4, 8]],
    'vertical column 1 bottom' => [[4, 8, 12]],
    'vertical column 2 top' => [[1, 5, 9]],
    'vertical column 2 bottom' => [[5, 9, 13]],
    'vertical column 3 top' => [[2, 6, 10]],
    'vertical column 3 bottom' => [[6, 10, 14]],
    'vertical column 4 top' => [[3, 7, 11]],
    'vertical column 4 bottom' => [[7, 11, 15]],
    'descending diagonal 1' => [[0, 5, 10]],
    'descending diagonal 2' => [[1, 6, 11]],
    'descending diagonal 3' => [[4, 9, 14]],
    'descending diagonal 4' => [[5, 10, 15]],
    'ascending diagonal 1' => [[2, 5, 8]],
    'ascending diagonal 2' => [[3, 6, 9]],
    'ascending diagonal 3' => [[6, 9, 12]],
    'ascending diagonal 4' => [[7, 10, 13]],
]);

test('recognizes wins for O in each direction', function (array $line) {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::O);
    $board = array_fill(0, 16, '');
    $board[$line[0]] = 'o';
    $board[$line[1]] = 'o';
    $board[array_values(array_diff(range(0, 15), $line))[0]] = 'x';
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::O, ['board' => $board]);

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => $line[2]]));

    expect($result->state->status)->toBe(MatchStatus::Completed);
    expect($result->state->winner)->toBe(PlayerMark::O);
    expect($rules->winningLine($result->state))->toBe($line);
})->with([
    'horizontal' => [[1, 2, 3]],
    'vertical' => [[5, 9, 13]],
    'descending diagonal' => [[5, 10, 15]],
    'ascending diagonal' => [[7, 10, 13]],
]);

test('does not accept sequences that wrap across row boundaries', function (array $cells) {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $board = array_fill(0, 16, '');
    $board[$cells[0]] = 'x';
    $board[$cells[1]] = 'x';
    $board[array_values(array_diff(range(0, 15), $cells))[0]] = 'o';
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, ['board' => $board]);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => $cells[2]]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state->status)->toBe(MatchStatus::InProgress);
    expect($result->state->winner)->toBeNull();
    expect($result->state->currentPlayer)->toBe(PlayerMark::O);
    expect($rules->winningLine($result->state))->toBeNull();
})->with([
    'horizontal from first row to second' => [[2, 3, 4]],
    'horizontal from second row to third' => [[7, 8, 9]],
    'descending diagonal through right edge' => [[2, 7, 12]],
    'ascending diagonal through left edge' => [[1, 4, 7]],
]);

test('reports no winning line while a match is in progress or drawn', function () {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $draw = playClassic4x3Moves($rules, $initial, [1, 0, 3, 2, 4, 6, 5, 7, 10, 8, 11, 9, 12, 14, 13, 15]);

    expect($rules->winningLine($initial))->toBeNull();
    expect($rules->winningLine($draw))->toBeNull();
});

test('declares a draw when the sixteenth move fills the board without a winner', function () {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);

    $state = playClassic4x3Moves($rules, $initial, [1, 0, 3, 2, 4, 6, 5, 7, 10, 8, 11, 9, 12, 14, 13, 15]);

    expect($state->data['board'])->not->toContain('');
    expect($state->status)->toBe(MatchStatus::Drawn);
    expect($state->winner)->toBeNull();
    expect($state->currentPlayer)->toBeNull();
});

test('prioritizes a win on the sixteenth move over a draw', function () {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);

    $state = playClassic4x3Moves($rules, $initial, [0, 1, 2, 3, 4, 6, 5, 8, 7, 10, 9, 13, 11, 15, 12, 14]);

    expect($state->data['board'])->not->toContain('');
    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe(PlayerMark::O);
    expect($state->currentPlayer)->toBeNull();
    expect($rules->winningLine($state))->toContain(14);
});

test('rejects moves after a win or draw without changing the finished state', function (array $moves) {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $finished = playClassic4x3Moves($rules, $initial, $moves);

    $result = $rules->apply($finished, new GameAction(PlayerMark::O, 'move', ['cell' => -1]));

    expect($result->validation->reason)->toBe('game_finished');
    expect($result->state)->toBe($finished);
})->with([
    'victory' => [[0, 4, 1, 5, 2]],
    'draw' => [[1, 0, 3, 2, 4, 6, 5, 7, 10, 8, 11, 9, 12, 14, 13, 15]],
]);

test('validates repeatedly without changing state or retaining hidden state', function () {
    $rules = new Classic4x3Rules;
    $state = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);

    $first = $rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]));
    $second = $rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]));
    $rejected = $rules->validate($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($first->valid)->toBeTrue();
    expect($second->valid)->toBeTrue();
    expect($rejected->reason)->toBe('not_your_turn');
    expect($state->data['board'])->toBe(array_fill(0, 16, ''));
    expect($state->currentPlayer)->toBe(PlayerMark::X);
});

test('rejects malformed board data instead of using invalid cells', function (array $data) {
    $rules = new Classic4x3Rules;
    $initial = $rules->initialState(new Modality('4x3-basic'), classic4x3Participants(), PlayerMark::X);
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, $data);

    expect(fn () => $rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0])))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'missing board' => [[]],
    'wrong length' => [['board' => ['', 'x']]],
    'invalid mark' => [['board' => array_replace(array_fill(0, 16, ''), [1 => 'z'])]],
]);

function classic4x3Participants(): Participants
{
    return new Participants(
        new Participant(ParticipantKind::Guest, 'visitor-x'),
        new Participant(ParticipantKind::Guest, 'visitor-o'),
    );
}

/**
 * @param  list<int>  $moves
 */
function playClassic4x3Moves(Classic4x3Rules $rules, MatchState $state, array $moves): MatchState
{
    foreach ($moves as $cellIndex) {
        $currentPlayer = $state->currentPlayer;

        if ($currentPlayer === null) {
            throw new LogicException('The match ended before all test moves were applied.');
        }

        $result = $rules->apply($state, new GameAction($currentPlayer, 'place', ['cell' => $cellIndex]));

        expect($result->validation->valid)->toBeTrue();

        $state = $result->state;
    }

    return $state;
}
