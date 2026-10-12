<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Hidden4x3Rules;
use Random\Engine;
use Random\Engine\Mt19937;
use Random\Randomizer;

function hiddenRules(int $seed = 0): Hidden4x3Rules
{
    return new Hidden4x3Rules(new Randomizer(new Mt19937($seed)));
}

function hiddenInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return hiddenRules()->initialState(new Modality('4x3-hidden'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter);
}

function hiddenChoose(Hidden4x3Rules $rules, MatchState $state, int $cell): MatchState
{
    return $rules->apply($state, new GameAction($state->currentPlayer, 'choose', ['cell' => $cell]))->state;
}

test('starts with sixteen empty cells and the selected player', function (PlayerMark $starter) {
    $state = hiddenInitial($starter);
    expect($state->data)->toBe(['board' => array_fill(0, 16, ''), 'order' => ['x' => [], 'o' => []]]);
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
})->with([PlayerMark::X, PlayerMark::O]);

test('places at most five pieces per player then removes the oldest before placing', function () {
    $rules = hiddenRules();
    $state = hiddenInitial();
    foreach ([0, 1, 2, 3, 5, 6, 7, 8, 12, 10] as $cell) {
        $state = hiddenChoose($rules, $state, $cell);
    }
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->data['order'])->toBe(['x' => [0, 2, 5, 7, 12], 'o' => [1, 3, 6, 8, 10]]);

    $previous = $state;
    $state = hiddenChoose($rules, $state, 14);
    expect($state->data['order']['x'])->toBe([2, 5, 7, 12, 14]);
    expect($state->data['board'][0])->toBe('');
    expect($state->data['board'][14])->toBe('x');
    expect($state->currentPlayer)->toBe(PlayerMark::O);
    expect($previous->data['board'][0])->toBe('x');
});

test('consumes a turn without moving pieces when choosing an opponent piece', function () {
    $rules = hiddenRules();
    $state = hiddenChoose($rules, hiddenInitial(), 0);
    $before = $state;

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'choose', ['cell' => 0]));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state)->not->toBe($before);
    expect($result->state->data)->toBe($before->data);
    expect($result->state->currentPlayer)->toBe(PlayerMark::X);
});

test('moves an own piece to a server-selected empty cell and updates order deterministically', function () {
    $first = hiddenRules(42);
    $second = hiddenRules(42);
    $state = hiddenChoose($first, hiddenInitial(), 0);
    $state = hiddenChoose($first, $state, 1);
    $before = $state;

    $moved = hiddenChoose($first, $state, 0);
    $repeated = hiddenChoose($second, $state, 0);
    $destination = $moved->data['order']['x'][0];

    expect($destination)->toBe($repeated->data['order']['x'][0]);
    expect($destination)->not->toBeIn([0, 1]);
    expect($moved->data['board'][0])->toBe('');
    expect($moved->data['board'][$destination])->toBe('x');
    expect($moved->data['board'][1])->toBe('o');
    expect($before->data['board'][0])->toBe('x');
});

test('reveals a win created by moving an own piece', function () {
    $rules = new Hidden4x3Rules(new Randomizer(new class implements Engine
    {
        public function generate(): string
        {
            return "\0\0\0\0";
        }
    }));
    $initial = hiddenInitial();
    $board = array_fill(0, 16, '');
    foreach ([0, 1, 4] as $cell) {
        $board[$cell] = 'x';
    }
    foreach ([3, 5, 6] as $cell) {
        $board[$cell] = 'o';
    }
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X,
        ['board' => $board, 'order' => ['x' => [0, 1, 4], 'o' => [3, 5, 6]]]);

    $won = hiddenChoose($rules, $state, 4);

    expect($won->winner)->toBe(PlayerMark::X);
    expect($won->data['board'][4])->toBe('');
    expect($rules->winningLine($won))->toBe([0, 1, 2]);
});

test('does not invent a draw after repeated consumed turns', function () {
    $rules = hiddenRules();
    $state = hiddenChoose($rules, hiddenInitial(), 0);
    $state = hiddenChoose($rules, $state, 1);

    for ($turn = 0; $turn < 20; $turn++) {
        $state = hiddenChoose($rules, $state, $turn % 2 === 0 ? 1 : 0);
    }

    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->data['order'])->toBe(['x' => [0], 'o' => [1]]);
});

test('rejects unsupported, out-of-turn, invalid and finished actions without changing state', function () {
    $rules = hiddenRules();
    $initial = hiddenInitial();
    foreach ([
        [PlayerMark::O, 'choose', ['cell' => 0], 'not_your_turn'],
        [PlayerMark::X, 'place', ['cell' => 0], 'unsupported_action'],
        [PlayerMark::X, 'choose', ['cell' => -1], 'invalid_cell'],
        [PlayerMark::X, 'choose', ['cell' => 16], 'invalid_cell'],
        [PlayerMark::X, 'choose', ['cell' => '1'], 'invalid_cell'],
    ] as [$player, $type, $payload, $reason]) {
        $action = new GameAction($player, $type, $payload);
        expect($rules->validate($initial, $action)->reason)->toBe($reason);
        $result = $rules->apply($initial, $action);
        expect($result->validation->reason)->toBe($reason);
        expect($result->state)->toBe($initial);
    }
    expect($initial->data['board'])->toBe(array_fill(0, 16, ''));

    $state = $initial;
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $state = hiddenChoose($rules, $state, $cell);
    }
    expect($state->winner)->toBe(PlayerMark::X);
    expect($rules->apply($state, new GameAction(PlayerMark::O, 'choose', ['cell' => 6]))->state)->toBe($state);
});

test('recognizes each of the twenty-four three-cell lines without wrapping rows', function (array $line) {
    $rules = hiddenRules();
    $state = hiddenInitial();
    $board = $state->data['board'];
    $board[$line[0]] = 'x';
    $board[$line[1]] = 'x';
    $other = array_slice(array_values(array_diff(range(0, 15), $line)), 0, 2);
    $board[$other[0]] = 'o';
    $board[$other[1]] = 'o';
    $before = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X,
        ['board' => $board, 'order' => ['x' => [$line[0], $line[1]], 'o' => $other]]);

    $won = hiddenChoose($rules, $before, $line[2]);
    expect($won->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($won))->toBe($line);
})->with([
    [[0, 1, 2]], [[1, 2, 3]], [[4, 5, 6]], [[5, 6, 7]], [[8, 9, 10]], [[9, 10, 11]], [[12, 13, 14]], [[13, 14, 15]],
    [[0, 4, 8]], [[4, 8, 12]], [[1, 5, 9]], [[5, 9, 13]], [[2, 6, 10]], [[6, 10, 14]], [[3, 7, 11]], [[7, 11, 15]],
    [[0, 5, 10]], [[1, 6, 11]], [[4, 9, 14]], [[5, 10, 15]], [[2, 5, 8]], [[3, 6, 9]], [[6, 9, 12]], [[7, 10, 13]],
]);

test('accepts a win by O when O starts', function () {
    $rules = hiddenRules();
    $state = hiddenInitial(PlayerMark::O);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $state = hiddenChoose($rules, $state, $cell);
    }
    expect($state->winner)->toBe(PlayerMark::O);
    expect($rules->winningLine($state))->toBe([0, 1, 2]);
});

test('rejects an inconsistent order and never treats a wrapped diagonal as a win', function () {
    $rules = hiddenRules();
    $state = hiddenInitial();
    $board = $state->data['board'];
    foreach ([3, 8, 13] as $cell) {
        $board[$cell] = 'x';
    }
    foreach ([0, 1] as $cell) {
        $board[$cell] = 'o';
    }
    $wrappedBoard = $board;
    $wrappedBoard[13] = '';
    $wrapped = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X,
        ['board' => $wrappedBoard, 'order' => ['x' => [3, 8], 'o' => [0, 1]]]);
    $after = hiddenChoose($rules, $wrapped, 13);
    expect($after->status)->toBe(MatchStatus::InProgress);
    expect($rules->winningLine($after))->toBeNull();
    $corrupt = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X,
        ['board' => $board, 'order' => ['x' => [3, 3, 13], 'o' => [0, 1]]]);
    expect(fn () => $rules->validate($corrupt, new GameAction(PlayerMark::X, 'choose', ['cell' => 4])))
        ->toThrow(InvalidArgumentException::class);
});
