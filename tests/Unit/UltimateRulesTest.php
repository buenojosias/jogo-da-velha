<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\UltimateRules;

function ultimateInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new UltimateRules)->initialState(new Modality('ultimate'), new Participants(
        new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o'),
    ), $starter);
}

/** @param list<list<string>> $boards */
function ultimatePosition(array $boards, ?int $target, PlayerMark $current = PlayerMark::X): MatchState
{
    $initial = ultimateInitial();

    return new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $current,
        ['boards' => $boards, 'target_board' => $target]);
}

/** @param list<list<string>> $boards */
function ultimatePlaceBoards(array $boards, int $index, string $mark, array $cells): array
{
    foreach ($cells as $cell) {
        $boards[$index][$cell] = $mark;
    }

    return $boards;
}

test('starts with nine open subboards and either initial player', function (PlayerMark $starter) {
    $rules = new UltimateRules;
    $state = ultimateInitial($starter);

    expect($state->data)->toBe(['boards' => array_fill(0, 9, array_fill(0, 9, '')), 'target_board' => null]);
    expect($state->currentPlayer)->toBe($starter);
    expect($rules->availableBoards($state))->toBe(range(0, 8));
})->with(['X' => [PlayerMark::X], 'O' => [PlayerMark::O]]);

test('directs the next player to the cell-index subboard', function (int $cell) {
    $rules = new UltimateRules;
    $state = ultimateInitial();
    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => $cell]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state->data['target_board'])->toBe($cell);
    expect($rules->availableBoards($result->state))->toBe([$cell]);
    expect($result->state->currentPlayer)->toBe(PlayerMark::O);
    expect($state->data['boards'][0][$cell])->toBe('');
})->with([0, 1, 2, 3, 4, 5, 6, 7, 8]);

test('rejects wrong boards, occupied cells, invalid indices, unknown actions and wrong turns without changing state', function () {
    $rules = new UltimateRules;
    $state = $rules->apply(ultimateInitial(), new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => 4]))->state;
    foreach ([
        [new GameAction(PlayerMark::O, 'place', ['board' => 0, 'cell' => 1]), 'wrong_board'],
        [new GameAction(PlayerMark::O, 'place', ['board' => 4, 'cell' => -1]), 'invalid_cell'],
        [new GameAction(PlayerMark::O, 'place', ['board' => 9, 'cell' => 1]), 'invalid_board'],
        [new GameAction(PlayerMark::O, 'place', ['board' => '4', 'cell' => 1]), 'invalid_board'],
        [new GameAction(PlayerMark::X, 'place', ['board' => 4, 'cell' => 1]), 'not_your_turn'],
        [new GameAction(PlayerMark::O, 'rotate'), 'unsupported_action'],
    ] as [$action, $reason]) {
        $result = $rules->apply($state, $action);
        expect($result->validation->reason)->toBe($reason);
        expect($result->state)->toBe($state);
    }
    $boards = $state->data['boards'];
    $boards[4][0] = 'x';
    $occupied = ultimatePosition($boards, 4, PlayerMark::O);
    $result = $rules->apply($occupied, new GameAction(PlayerMark::O, 'place', ['board' => 4, 'cell' => 0]));
    expect($result->validation->reason)->toBe('cell_occupied');
    expect($result->state)->toBe($occupied);
});

test('derives a conquered board and keeps the target when that destination remains open', function () {
    $rules = new UltimateRules;
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    $boards = ultimatePlaceBoards($boards, 0, 'x', [0, 1]);
    $boards = ultimatePlaceBoards($boards, 1, 'o', [3, 4]);
    $state = ultimatePosition($boards, 0);

    $next = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => 2]))->state;

    expect($rules->subBoardResult($next, 0))->toBe('x');
    expect($rules->localWinningLine($next, 0))->toBe([0, 1, 2]);
    expect($next->data['target_board'])->toBe(2);
    expect($rules->availableBoards($next))->toBe([2]);
    expect($next->status)->toBe(MatchStatus::InProgress);
});

test('releases the next move when the destination subboard is closed', function () {
    $rules = new UltimateRules;
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    $boards = ultimatePlaceBoards($boards, 2, 'x', [0, 1, 2]);
    $boards = ultimatePlaceBoards($boards, 3, 'o', [0, 1]);
    $boards = ultimatePlaceBoards($boards, 4, 'o', [4]);
    $state = ultimatePosition($boards, 0);

    $next = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => 2]))->state;

    expect($next->data['target_board'])->toBeNull();
    expect($rules->availableBoards($next))->not->toContain(2);
    expect($rules->availableBoards($next))->toContain(0, 1, 3);
    expect($rules->apply($next, new GameAction(PlayerMark::O, 'place', ['board' => 2, 'cell' => 3]))->validation->reason)->toBe('board_closed');
});

test('treats a full local board without a line as closed and releases its destination', function () {
    $rules = new UltimateRules;
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    $boards[4] = str_split('xoxxoooxx');
    $boards[5] = str_split('oxooxxxoo');
    $state = ultimatePosition($boards, 0);

    $next = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => 4]))->state;

    expect($rules->subBoardResult($next, 4))->toBe('draw');
    expect($rules->localWinningLine($next, 4))->toBeNull();
    expect($next->data['target_board'])->toBeNull();
    expect($rules->availableBoards($next))->not->toContain(4, 5);
});

test('recognizes every global winning line without counting local draws', function (array $line) {
    $rules = new UltimateRules;
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    $boards = ultimatePlaceBoards($boards, $line[0], 'x', [0, 1, 2]);
    $boards = ultimatePlaceBoards($boards, $line[1], 'x', [0, 1, 2]);
    $boards = ultimatePlaceBoards($boards, $line[2], 'x', [0, 1]);
    $other = array_values(array_diff(range(0, 8), $line));
    foreach ([0, 0, 1, 1, 2, 2, 3] as $index => $cell) {
        $boards[$other[$index % 4]][$cell] = 'o';
    }
    $state = ultimatePosition($boards, $line[2]);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => $line[2], 'cell' => 2]));

    expect($result->state->status)->toBe(MatchStatus::Completed);
    expect($result->state->winner)->toBe(PlayerMark::X);
    expect($rules->globalWinningLine($result->state))->toBe($line);
    expect($rules->availableBoards($result->state))->toBe([]);
    expect($rules->apply($result->state, new GameAction(PlayerMark::O, 'place', ['board' => 8, 'cell' => 8]))->state)->toBe($result->state);
})->with([
    [[0, 1, 2]], [[3, 4, 5]], [[6, 7, 8]],
    [[0, 3, 6]], [[1, 4, 7]], [[2, 5, 8]],
    [[0, 4, 8]], [[2, 4, 6]],
]);

test('draws only when the ninth subboard closes without a global line', function () {
    $rules = new UltimateRules;
    $xHeavy = str_split('xoxxoooxx');
    $oHeavy = str_split('oxooxxxoo');
    $boards = array_merge(array_fill(0, 4, $xHeavy), array_fill(0, 4, $oHeavy), [$xHeavy]);
    $boards[8][8] = '';
    $state = ultimatePosition($boards, 8);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 8, 'cell' => 8]));

    expect($result->state->status)->toBe(MatchStatus::Drawn);
    expect($result->state->winner)->toBeNull();
    expect($result->state->data['target_board'])->toBeNull();
});

test('awards the global win to O when its third conquered board completes a line', function () {
    $rules = new UltimateRules;
    $boards = array_fill(0, 9, array_fill(0, 9, ''));
    foreach ([0, 3] as $index) {
        $boards = ultimatePlaceBoards($boards, $index, 'o', [0, 1, 2]);
    }
    $boards = ultimatePlaceBoards($boards, 6, 'o', [0, 1]);
    foreach ([1 => [0, 1], 2 => [0, 1], 4 => [0, 1], 5 => [0, 1]] as $index => $cells) {
        $boards = ultimatePlaceBoards($boards, $index, 'x', $cells);
    }
    $state = ultimatePosition($boards, 6, PlayerMark::O);

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['board' => 6, 'cell' => 2]));

    expect($result->state->winner)->toBe(PlayerMark::O);
    expect($rules->globalWinningLine($result->state))->toBe([0, 3, 6]);
});

test('prioritizes a global victory over a draw on the eighty-first move', function () {
    $rules = new UltimateRules;
    $xHeavy = str_split('xoxxoooxx');
    $oHeavy = str_split('oxooxxxoo');
    $xWon = str_split('xoooxooxx');
    $boards = [$xWon, $xWon, $xWon];
    $boards[2][8] = '';
    $boards = array_merge($boards, array_fill(0, 5, $xHeavy), [$oHeavy]);
    $state = ultimatePosition($boards, 2);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 2, 'cell' => 8]));

    expect($result->state->status)->toBe(MatchStatus::Completed);
    expect($result->state->winner)->toBe(PlayerMark::X);
    expect($rules->globalWinningLine($result->state))->toBe([0, 1, 2]);
});

test('rejects malformed state before validating an action and leaves validation side-effect free', function () {
    $rules = new UltimateRules;
    $state = ultimateInitial();
    $action = new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => 0]);
    expect($rules->validate($state, $action)->valid)->toBeTrue();
    expect($rules->validate($state, $action)->valid)->toBeTrue();
    expect($state->data['boards'][0][0])->toBe('');
    $invalid = ultimatePosition([['x']], null);
    expect(fn () => $rules->validate($invalid, $action))->toThrow(InvalidArgumentException::class);
});
