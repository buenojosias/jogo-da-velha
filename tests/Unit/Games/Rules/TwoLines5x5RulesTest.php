<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\TwoLines5x5Rules;

function twoLinesInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new TwoLines5x5Rules)->initialState(
        new Modality('5x5-2-lines'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

/** @param list<int> $xCells
 * @param  list<int>  $oCells
 */
function twoLinesPosition(array $xCells, array $oCells = [], MatchStatus $status = MatchStatus::InProgress, ?PlayerMark $winner = null): MatchState
{
    $initial = twoLinesInitial();
    $board = array_fill(0, 25, '');
    foreach ($xCells as $cell) {
        $board[$cell] = 'x';
    }
    foreach ($oCells as $cell) {
        $board[$cell] = 'o';
    }

    return new MatchState($initial->modality, $initial->participants, $status,
        $status->isFinished() ? null : PlayerMark::X, ['board' => $board], $winner);
}

test('starts with twenty-five empty cells and either selected starter', function (PlayerMark $starter) {
    $state = twoLinesInitial($starter);

    expect($state->data['board'])->toBe(array_fill(0, 25, ''));
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
})->with(['X' => [PlayerMark::X], 'O' => [PlayerMark::O]]);

test('counts one line in each geometric direction', function (array $cells) {
    $state = twoLinesPosition($cells);

    expect((new TwoLines5x5Rules)->lineCount($state, PlayerMark::X))->toBe(1);
})->with([
    'horizontal' => [[0, 1, 2]],
    'vertical' => [[0, 5, 10]],
    'descending diagonal' => [[0, 6, 12]],
    'ascending diagonal' => [[2, 6, 10]],
]);

test('groups overlapping triples of four or five marks into one line', function (array $cells) {
    $state = twoLinesPosition($cells);

    expect((new TwoLines5x5Rules)->lineCount($state, PlayerMark::X))->toBe(1);
})->with(['four' => [[0, 1, 2, 3]], 'five' => [[0, 1, 2, 3, 4]]]);

test('reports independent line cells for both players without splitting overlapping windows', function () {
    $state = twoLinesPosition([0, 1, 2, 3], [15, 16, 17]);

    expect((new TwoLines5x5Rules)->lineProgress($state))->toBe([
        'x' => ['count' => 1, 'cells' => [0, 1, 2, 3]],
        'o' => ['count' => 1, 'cells' => [15, 16, 17]],
    ]);
});

test('counts disconnected lines in one direction separately', function () {
    $state = twoLinesPosition([0, 1, 2, 15, 16, 17]);

    expect((new TwoLines5x5Rules)->lineCount($state, PlayerMark::X))->toBe(2);
});

test('counts crossing lines of different directions separately', function (array $cells) {
    $state = twoLinesPosition($cells);

    expect((new TwoLines5x5Rules)->lineCount($state, PlayerMark::X))->toBe(2);
})->with([
    'horizontal and vertical' => [[1, 5, 6, 7, 11]],
    'opposite diagonals' => [[0, 2, 6, 10, 12]],
]);

test('never joins positions across a row or diagonal boundary', function (array $cells) {
    $state = twoLinesPosition($cells);

    expect((new TwoLines5x5Rules)->lineCount($state, PlayerMark::X))->toBe(0);
})->with([
    'horizontal wrap' => [[3, 4, 5]],
    'diagonal wrap' => [[3, 9, 15]],
]);

test('requires a second independent line and highlights all winning cells', function () {
    $rules = new TwoLines5x5Rules;
    $state = twoLinesInitial();
    foreach ([0, 5, 1, 6, 2, 9, 10, 14, 11, 19, 12] as $turn => $cell) {
        $before = $state;
        $result = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]));
        expect($result->validation->valid)->toBeTrue();
        $state = $result->state;
        expect($before->data['board'][$cell])->toBe('');
        if ($turn < 10) {
            expect($state->status)->toBe(MatchStatus::InProgress);
        }
    }

    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe(PlayerMark::X);
    expect($rules->winningCells($state))->toBe([0, 1, 2, 10, 11, 12]);
});

test('highlights all connected windows and both independent lines', function () {
    $rules = new TwoLines5x5Rules;
    $state = twoLinesPosition([0, 1, 2, 3, 4, 10, 11, 12], [], MatchStatus::Completed, PlayerMark::X);

    expect($rules->lineCount($state, PlayerMark::X))->toBe(2);
    expect($rules->winningCells($state))->toBe([0, 1, 2, 3, 4, 10, 11, 12]);
});

test('declares a draw on a full board without two lines', function () {
    $rules = new TwoLines5x5Rules;
    $marks = str_split('ooxxoxooxxoxxooxooxxoxxxo');
    $marks[2] = '';
    $initial = twoLinesInitial();
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, ['board' => $marks]);

    $draw = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => 2]))->state;

    expect($draw->status)->toBe(MatchStatus::Drawn);
    expect($draw->winner)->toBeNull();
    expect($draw->currentPlayer)->toBeNull();
    expect($rules->winningCells($draw))->toBe([]);
});

test('prioritizes a win on the final available cell', function () {
    $rules = new TwoLines5x5Rules;
    $marks = str_split('xoxxooooooxxxxxxooxxoxoxo');
    $marks[23] = '';
    $initial = twoLinesInitial();
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, ['board' => $marks]);

    $won = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => 23]))->state;

    expect($won->status)->toBe(MatchStatus::Completed);
    expect($won->winner)->toBe(PlayerMark::X);
    expect($rules->winningCells($won))->not->toBe([]);
});

test('rejects invalid actions without changing the original state', function (PlayerMark $player, string $type, array $payload, string $reason) {
    $rules = new TwoLines5x5Rules;
    $state = twoLinesInitial();
    $result = $rules->apply($state, new GameAction($player, $type, $payload));

    expect($rules->validate($state, new GameAction($player, $type, $payload))->reason)->toBe($reason);
    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
})->with([
    'wrong turn' => [PlayerMark::O, 'place', ['cell' => 0], 'not_your_turn'],
    'unknown action' => [PlayerMark::X, 'move', ['cell' => 0], 'unsupported_action'],
    'negative cell' => [PlayerMark::X, 'place', ['cell' => -1], 'invalid_cell'],
    'out of bounds' => [PlayerMark::X, 'place', ['cell' => 25], 'invalid_cell'],
    'string index' => [PlayerMark::X, 'place', ['cell' => '2'], 'invalid_cell'],
]);

test('rejects occupied cells and all play after a terminal result', function () {
    $rules = new TwoLines5x5Rules;
    $state = $rules->apply(twoLinesInitial(), new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state;
    $occupied = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));
    expect($occupied->validation->reason)->toBe('cell_occupied');
    expect($occupied->state)->toBe($state);

    $draw = twoLinesPosition([], [], MatchStatus::Drawn);
    $afterDraw = $rules->apply($draw, new GameAction(PlayerMark::X, 'place', ['cell' => 1]));
    expect($afterDraw->validation->reason)->toBe('game_finished');
    expect($afterDraw->state)->toBe($draw);
});
