<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Rotation3x3Rules;
use App\Games\Rules\Rotation4x3Rules;
use App\Games\Rules\RotationRules;

function rotationRules(string $key): RotationRules
{
    return $key === '3-rotation' ? new Rotation3x3Rules : new Rotation4x3Rules;
}

function rotationInitial(string $key, PlayerMark $starter = PlayerMark::X): MatchState
{
    return rotationRules($key)->initialState(
        new Modality($key),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

function rotationPlaced(string $key): MatchState
{
    $rules = rotationRules($key);
    $state = rotationInitial($key);
    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    return $state;
}

test('starts with an empty ordered board and the chosen turn', function (string $key, int $size, PlayerMark $starter) {
    $rules = rotationRules($key);
    $state = rotationInitial($key, $starter);

    expect($state->data)->toBe(['board' => array_fill(0, $size, ''), 'order' => ['x' => [], 'o' => []]]);
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($rules->oldestPieces($state))->toBe(['x' => null, 'o' => null]);
})->with([
    '3x3 X' => ['3-rotation', 9, PlayerMark::X], '3x3 O' => ['3-rotation', 9, PlayerMark::O],
    '4x4 X' => ['4x3-rotation', 16, PlayerMark::X], '4x4 O' => ['4x3-rotation', 16, PlayerMark::O],
]);

test('rotates the correct oldest pieces in order over several turns', function (string $key) {
    $rules = rotationRules($key);
    $state = rotationPlaced($key);
    expect($state->data['order'])->toBe(['x' => [0, 2, 4], 'o' => [1, 3, 6]]);
    expect($rules->oldestPieces($state))->toBe(['x' => 0, 'o' => 1]);

    foreach ([5, 7, 8, 1] as $cell) {
        $before = $state;
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
        expect($state)->not->toBe($before);
        expect($before->data['order'])->toBe(match ($cell) {
            5 => ['x' => [0, 2, 4], 'o' => [1, 3, 6]],
            7 => ['x' => [2, 4, 5], 'o' => [1, 3, 6]],
            8 => ['x' => [2, 4, 5], 'o' => [3, 6, 7]],
            1 => ['x' => [4, 5, 8], 'o' => [3, 6, 7]],
        });
    }

    expect($state->data['order'])->toBe(['x' => [4, 5, 8], 'o' => [6, 7, 1]]);
    expect($state->data['board'][0])->toBe('');
    expect($state->data['board'][2])->toBe('');
    expect($state->data['board'][3])->toBe('');
    expect($state->data['board'][1])->toBe('o');
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($rules->oldestPieces($state))->toBe(['x' => 4, 'o' => 6]);
})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);

test('rejects invalid actions and preserves the identical state', function (string $key, bool $afterPlacement, string $type, array $payload, string $reason) {
    $rules = rotationRules($key);
    $state = $afterPlacement ? rotationPlaced($key) : rotationInitial($key);
    $result = $rules->apply($state, new GameAction(PlayerMark::X, $type, $payload));
    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
})->with([
    ['3-rotation', false, 'place', ['cell' => -1], 'invalid_cell'],
    ['3-rotation', false, 'place', ['cell' => 9], 'invalid_cell'],
    ['3-rotation', false, 'place', ['cell' => '2'], 'invalid_cell'],
    ['3-rotation', false, 'move', ['from' => 0, 'to' => 8], 'unsupported_action'],
    ['3-rotation', true, 'place', ['cell' => 0], 'cell_occupied'],
    ['4x3-rotation', false, 'place', ['cell' => 16], 'invalid_cell'],
    ['4x3-rotation', false, 'place', ['cell' => 2.0], 'invalid_cell'],
    ['4x3-rotation', true, 'place', ['cell' => 0], 'cell_occupied'],
]);

test('rejects playing out of turn and after victory', function (string $key) {
    $rules = rotationRules($key);
    $initial = rotationInitial($key);
    expect($rules->apply($initial, new GameAction(PlayerMark::O, 'place', ['cell' => 0]))->state)->toBe($initial);
    $state = $initial;
    foreach ([0, 3, 1, 4, 2] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }
    expect($state->winner)->toBe(PlayerMark::X);
    expect($rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 5]))->validation->reason)->toBe('game_finished');
})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);

test('wins during rotation and keeps the order in the terminal state', function (string $key, array $placements, int $destination, array $line, array $remaining) {
    $rules = rotationRules($key);
    $state = rotationInitial($key);
    foreach ($placements as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }
    $won = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => $destination]))->state;
    expect($won->status)->toBe(MatchStatus::Completed);
    expect($won->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($won))->toBe($line);
    expect($won->data['order']['x'])->toBe([...$remaining, $destination]);
})->with([
    '3x3' => ['3-rotation', [0, 1, 4, 2, 5, 6], 3, [3, 4, 5], [4, 5]],
    '4x4' => ['4x3-rotation', [0, 1, 4, 3, 5, 8], 6, [4, 5, 6], [4, 5]],
]);

test('reports every winning line using official state', function (string $key, int $size, array $line) {
    $rules = rotationRules($key);
    $board = array_fill(0, $size, '');
    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }
    $others = array_values(array_diff(range(0, $size - 1), $line));
    $board[$others[0]] = 'o';
    $board[$others[1]] = 'o';
    $initial = rotationInitial($key);
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::Completed, null,
        ['board' => $board, 'order' => ['x' => $line, 'o' => [$others[0], $others[1]]]], PlayerMark::X);
    expect($rules->winningLine($state))->toBe($line);
})->with(array_merge(
    array_map(fn (array $line): array => ['3-rotation', 9, $line], [
        [0, 1, 2], [3, 4, 5], [6, 7, 8], [0, 3, 6], [1, 4, 7], [2, 5, 8], [0, 4, 8], [2, 4, 6],
    ]),
    array_map(fn (array $line): array => ['4x3-rotation', 16, $line], [
        [0, 1, 2], [1, 2, 3], [4, 5, 6], [5, 6, 7], [8, 9, 10], [9, 10, 11], [12, 13, 14], [13, 14, 15],
        [0, 4, 8], [4, 8, 12], [1, 5, 9], [5, 9, 13], [2, 6, 10], [6, 10, 14], [3, 7, 11], [7, 11, 15],
        [0, 5, 10], [1, 6, 11], [4, 9, 14], [5, 10, 15], [2, 5, 8], [3, 6, 9], [6, 9, 12], [7, 10, 13],
    ]),
));

test('rejects corrupt order even when the board shape is valid', function (string $key) {
    $rules = rotationRules($key);
    $state = rotationPlaced($key);
    $corrupt = new MatchState($state->modality, $state->participants, $state->status, $state->currentPlayer,
        ['board' => $state->data['board'], 'order' => ['x' => [0, 0, 4], 'o' => [1, 3, 6]]]);
    expect(fn () => $rules->oldestPieces($corrupt))->toThrow(InvalidArgumentException::class);
})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);

test('does not treat a false four by four line crossing the board edge as a win', function (array $moves) {
    $rules = new Rotation4x3Rules;
    $state = rotationInitial('4x3-rotation');
    foreach ($moves as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($rules->winningLine($state))->toBeNull();
})->with(['horizontal edge' => [[2, 0, 3, 1, 4]], 'diagonal edge' => [[3, 0, 8, 1, 13]]]);
