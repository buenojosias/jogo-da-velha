<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\DiceRules;
use Random\Engine;
use Random\Randomizer;

class FixedDiceEngine implements Engine
{
    public function __construct(private int $face) {}

    public function generate(): string
    {
        return pack('V', $this->face - 1);
    }
}

function diceRules(int $face = 1): DiceRules
{
    return new DiceRules(new Randomizer(new FixedDiceEngine($face)));
}

function diceInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return diceRules()->initialState(
        new Modality('dice'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

/** @param array{list<string>, list<string>} $boards */
function dicePosition(array $boards, ?int $pending, ?int $last, PlayerMark $current = PlayerMark::X): MatchState
{
    $initial = diceInitial();

    return new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $current,
        ['boards' => $boards, 'pending_roll' => $pending, 'last_roll' => $last]);
}

test('starts with two empty boards and no pending roll', function (PlayerMark $starter) {
    $state = diceInitial($starter);

    expect($state->data)->toBe(['boards' => [array_fill(0, 9, ''), array_fill(0, 9, '')], 'pending_roll' => null, 'last_roll' => null]);
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
})->with(['X' => [PlayerMark::X], 'O' => [PlayerMark::O]]);

test('maps every server-generated face to the correct board and column', function (int $face, int $board, int $column) {
    $rules = diceRules($face);
    $before = diceInitial();
    $result = $rules->apply($before, new GameAction(PlayerMark::X, 'roll'));

    expect($result->validation->valid)->toBeTrue();
    expect($result->state->data['pending_roll'])->toBe($face);
    expect($result->state->data['last_roll'])->toBe($face);
    expect($result->state->currentPlayer)->toBe(PlayerMark::X);
    expect($before->data['last_roll'])->toBeNull();
    $place = $rules->apply($result->state, new GameAction(PlayerMark::X, 'place', ['board' => $board, 'cell' => $column]));
    expect($place->validation->valid)->toBeTrue();
    expect($place->state->data['boards'][$board][$column])->toBe('x');
    expect($place->state->data['pending_roll'])->toBeNull();
    expect($place->state->currentPlayer)->toBe(PlayerMark::O);
})->with([
    'one left first' => [1, 0, 0], 'two left second' => [2, 0, 1], 'three left third' => [3, 0, 2],
    'four right first' => [4, 1, 0], 'five right second' => [5, 1, 1], 'six right third' => [6, 1, 2],
]);

test('rejects placing before a roll and rolling twice without changing state', function () {
    $rules = diceRules(2);
    $initial = diceInitial();
    $beforeRoll = $rules->apply($initial, new GameAction(PlayerMark::X, 'place', ['board' => 0, 'cell' => 1]));
    $rolled = $rules->apply($initial, new GameAction(PlayerMark::X, 'roll'))->state;
    $secondRoll = $rules->apply($rolled, new GameAction(PlayerMark::X, 'roll'));

    expect($beforeRoll->validation->reason)->toBe('roll_required');
    expect($beforeRoll->state)->toBe($initial);
    expect($secondRoll->validation->reason)->toBe('roll_pending');
    expect($secondRoll->state)->toBe($rolled);
    expect($rolled->data['pending_roll'])->toBe(2);
});

test('ignores any client-supplied face and uses the server randomizer', function () {
    $rules = diceRules(1);
    $state = diceInitial();

    $rolled = $rules->apply($state, new GameAction(PlayerMark::X, 'roll', ['face' => 6]))->state;

    expect($rolled->data['pending_roll'])->toBe(1);
    expect($rolled->data['last_roll'])->toBe(1);
});

test('rejects wrong board, column and occupied cell while keeping the roll pending', function (array $payload, string $reason) {
    $rules = diceRules(2);
    $boards = [array_fill(0, 9, ''), array_fill(0, 9, '')];
    $boards[0][1] = 'o';
    $state = dicePosition($boards, 2, 2);
    $action = new GameAction(PlayerMark::X, 'place', $payload);

    expect($rules->validate($state, $action)->reason)->toBe($reason);
    $result = $rules->apply($state, $action);
    expect($result->state)->toBe($state);
    expect($state->data['pending_roll'])->toBe(2);
})->with([
    'other board' => [['board' => 1, 'cell' => 1], 'wrong_column'],
    'other column' => [['board' => 0, 'cell' => 0], 'wrong_column'],
    'occupied' => [['board' => 0, 'cell' => 1], 'cell_occupied'],
    'bad board type' => [['board' => '0', 'cell' => 4], 'invalid_board'],
    'bad board index' => [['board' => 2, 'cell' => 4], 'invalid_board'],
    'bad cell type' => [['board' => 0, 'cell' => '4'], 'invalid_cell'],
    'bad cell index' => [['board' => 0, 'cell' => 9], 'invalid_cell'],
]);

test('rejects unknown actions and wrong turns without mutation', function (GameAction $action, string $reason) {
    $rules = diceRules();
    $state = diceInitial();
    $result = $rules->apply($state, $action);

    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
})->with([
    'unknown' => [new GameAction(PlayerMark::X, 'choose'), 'unsupported_action'],
    'wrong player' => [new GameAction(PlayerMark::O, 'roll'), 'not_your_turn'],
]);

test('skips the turn when the exact rolled column is full without touching either board', function () {
    $rules = diceRules(4);
    $boards = [array_fill(0, 9, ''), array_fill(0, 9, '')];
    foreach ([0, 3, 6] as $cell) {
        $boards[1][$cell] = 'o';
    }
    $state = dicePosition($boards, null, null);
    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'roll'));

    expect($result->state->data['boards'])->toBe($boards);
    expect($result->state->data['pending_roll'])->toBeNull();
    expect($result->state->data['last_roll'])->toBe(4);
    expect($result->state->currentPlayer)->toBe(PlayerMark::O);
    expect($state->data['last_roll'])->toBeNull();
});

test('wins on either board and reports its winning cells', function (int $boardIndex, array $line) {
    $rules = diceRules($boardIndex === 0 ? 3 : 6);
    $boards = [array_fill(0, 9, ''), array_fill(0, 9, '')];
    $boards[$boardIndex][$line[0]] = 'x';
    $boards[$boardIndex][$line[1]] = 'x';
    $state = dicePosition($boards, $boardIndex === 0 ? 3 : 6, $boardIndex === 0 ? 3 : 6);
    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => $boardIndex, 'cell' => $line[2]]));

    expect($result->state->status)->toBe(MatchStatus::Completed);
    expect($result->state->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($result->state))->toBe(['board' => $boardIndex, 'cells' => $line]);
    expect($result->state->data['pending_roll'])->toBeNull();
    expect($rules->apply($result->state, new GameAction(PlayerMark::X, 'roll'))->state)->toBe($result->state);
})->with([
    'left horizontal' => [0, [0, 1, 2]],
    'right horizontal' => [1, [0, 1, 2]],
]);

test('recognizes every classic line independently on each board', function (array $line) {
    $rules = diceRules();
    foreach ([0, 1] as $boardIndex) {
        $boards = [array_fill(0, 9, ''), array_fill(0, 9, '')];
        $boards[$boardIndex][$line[0]] = 'o';
        $boards[$boardIndex][$line[1]] = 'o';
        $face = $boardIndex * 3 + $line[2] % 3 + 1;
        $state = dicePosition($boards, $face, $face, PlayerMark::O);

        $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['board' => $boardIndex, 'cell' => $line[2]]));

        expect($result->state->winner)->toBe(PlayerMark::O);
        expect($rules->winningLine($result->state))->toBe(['board' => $boardIndex, 'cells' => $line]);
    }
})->with([
    'top row' => [[0, 1, 2]], 'middle row' => [[3, 4, 5]], 'bottom row' => [[6, 7, 8]],
    'left column' => [[0, 3, 6]], 'middle column' => [[1, 4, 7]], 'right column' => [[2, 5, 8]],
    'descending diagonal' => [[0, 4, 8]], 'ascending diagonal' => [[2, 4, 6]],
]);

test('draws when the last empty cell on both boards is filled without a line', function () {
    $rules = diceRules(6);
    $drawBoard = str_split('xoxxoooxx');
    $boards = [$drawBoard, $drawBoard];
    $boards[1][8] = '';
    $state = dicePosition($boards, 6, 6);
    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['board' => 1, 'cell' => 8]));

    expect($result->state->status)->toBe(MatchStatus::Drawn);
    expect($result->state->winner)->toBeNull();
    expect($rules->winningLine($result->state))->toBeNull();
});
