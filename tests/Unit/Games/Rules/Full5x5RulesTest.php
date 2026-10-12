<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Full5x5Rules;

function fullInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new Full5x5Rules)->initialState(
        new Modality('5-full'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

/** @param list<int> $xCells
 * @param  list<int>  $oCells
 */
function fullPosition(array $xCells, array $oCells = [], ?PlayerMark $current = PlayerMark::X): MatchState
{
    $initial = fullInitial();
    $board = array_fill(0, 25, '');
    foreach ($xCells as $cell) {
        $board[$cell] = 'x';
    }
    foreach ($oCells as $cell) {
        $board[$cell] = 'o';
    }

    return new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $current, ['board' => $board]);
}

test('starts with an empty board and the selected player', function (PlayerMark $starter) {
    $state = fullInitial($starter);

    expect($state->data['board'])->toBe(array_fill(0, 25, ''));
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect((new Full5x5Rules)->scores($state))->toBe(['x' => 0, 'o' => 0]);
})->with(['X' => [PlayerMark::X], 'O' => [PlayerMark::O]]);

test('scores maximal lines in each direction', function (array $cells, int $points) {
    $rules = new Full5x5Rules;
    $state = fullPosition($cells);

    expect($rules->scores($state)['x'])->toBe($points);
    expect($rules->scoringSequences($state, PlayerMark::X))->toBe([$cells]);
})->with([
    'horizontal three' => [[0, 1, 2], 3],
    'horizontal four' => [[0, 1, 2, 3], 6],
    'horizontal five' => [[0, 1, 2, 3, 4], 10],
    'vertical three' => [[0, 5, 10], 3],
    'vertical four' => [[0, 5, 10, 15], 6],
    'vertical five' => [[0, 5, 10, 15, 20], 10],
    'descending three' => [[0, 6, 12], 3],
    'descending four' => [[0, 6, 12, 18], 6],
    'descending five' => [[0, 6, 12, 18, 24], 10],
    'ascending three' => [[2, 6, 10], 3],
    'ascending four' => [[3, 7, 11, 15], 6],
    'ascending five' => [[4, 8, 12, 16, 20], 10],
]);

test('does not score lines shorter than three or across board edges', function (array $cells) {
    expect((new Full5x5Rules)->scores(fullPosition($cells))['x'])->toBe(0);
})->with([
    'two marks' => [[0, 1]],
    'horizontal wrap' => [[3, 4, 5]],
    'diagonal wrap' => [[3, 9, 15]],
]);

test('scores separate same-direction lines and crossing directions independently', function (array $cells, int $points) {
    expect((new Full5x5Rules)->scores(fullPosition($cells))['x'])->toBe($points);
})->with([
    'separate horizontal lines' => [[0, 1, 2, 15, 16, 17], 6],
    'horizontal vertical crossing' => [[1, 5, 6, 7, 11], 6],
    'opposite diagonals' => [[0, 2, 6, 10, 12], 6],
]);

test('updates both players points without ending a partly filled round', function () {
    $rules = new Full5x5Rules;
    $state = fullInitial();
    foreach ([0, 5, 1, 6, 2, 7, 3, 8, 4] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    expect($rules->scores($state))->toBe(['x' => 10, 'o' => 6]);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->currentPlayer)->toBe(PlayerMark::O);
    expect($rules->finalResult($state))->toBeNull();
});

test('keeps a board with twenty-four pieces in progress and ends on the last move', function (string $board, PlayerMark $lastPlayer, MatchStatus $status, ?PlayerMark $winner, array $scores) {
    $initial = fullInitial();
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $lastPlayer, ['board' => [...str_split($board), '']]);
    $rules = new Full5x5Rules;

    expect($state->status)->toBe(MatchStatus::InProgress);
    $result = $rules->apply($state, new GameAction($lastPlayer, 'place', ['cell' => 24]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state->status)->toBe($status);
    expect($result->state->winner)->toBe($winner);
    expect($result->state->currentPlayer)->toBeNull();
    expect($rules->scores($result->state))->toBe($scores);
    expect($rules->finalResult($result->state))->toBe(['status' => $status, 'winner' => $winner, 'points' => $scores]);
    expect($state->data['board'][24])->toBe('');
    expect($rules->apply($result->state, new GameAction($lastPlayer, 'place', ['cell' => 0]))->state)->toBe($result->state);
})->with([
    'X wins' => ['ooxxoxoooxoxxoxoxooxxxxo', PlayerMark::X, MatchStatus::Completed, PlayerMark::X, ['x' => 15, 'o' => 12]],
    'O wins' => ['xoxoxooxxxoooxoooxoxxoxx', PlayerMark::O, MatchStatus::Completed, PlayerMark::O, ['x' => 9, 'o' => 22]],
    'equal points' => ['ooxxoxoooxoxxoxoxooxxxxo', PlayerMark::O, MatchStatus::Drawn, null, ['x' => 12, 'o' => 12]],
]);

test('rejects invalid actions without mutating the original state', function (PlayerMark $player, string $type, array $payload, string $reason) {
    $rules = new Full5x5Rules;
    $state = fullInitial();
    $action = new GameAction($player, $type, $payload);

    expect($rules->validate($state, $action)->reason)->toBe($reason);
    $result = $rules->apply($state, $action);
    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
    expect($state->data['board'])->toBe(array_fill(0, 25, ''));
})->with([
    'other player' => [PlayerMark::O, 'place', ['cell' => 0], 'not_your_turn'],
    'unknown type' => [PlayerMark::X, 'move', ['cell' => 0], 'unsupported_action'],
    'negative index' => [PlayerMark::X, 'place', ['cell' => -1], 'invalid_cell'],
    'too large' => [PlayerMark::X, 'place', ['cell' => 25], 'invalid_cell'],
    'string index' => [PlayerMark::X, 'place', ['cell' => '2'], 'invalid_cell'],
]);

test('rejects an occupied cell while preserving the old state', function () {
    $rules = new Full5x5Rules;
    $state = $rules->apply(fullInitial(), new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state;

    expect($rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]))->state)->toBe($state);
    expect($rules->validate($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]))->reason)->toBe('cell_occupied');
});
