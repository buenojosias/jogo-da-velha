<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Move4x3Rules;

function move4Initial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new Move4x3Rules)->initialState(
        new Modality('4x3-move'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

function move4Placed(): MatchState
{
    $rules = new Move4x3Rules;
    $state = move4Initial();

    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    return $state;
}

test('starts with sixteen empty cells and the chosen starter', function (PlayerMark $starter) {
    $state = move4Initial($starter);
    expect($state->data)->toBe(['board' => array_fill(0, 16, '')]);
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect((new Move4x3Rules)->winningLine($state))->toBeNull();
})->with([PlayerMark::X, PlayerMark::O]);

test('rejects another modality', function () {
    $state = move4Initial();
    expect(fn () => (new Move4x3Rules)->initialState(new Modality('3-move'), $state->participants, PlayerMark::X))
        ->toThrow(InvalidArgumentException::class);
});

test('transitions to movement after six placements and preserves the previous state', function () {
    $rules = new Move4x3Rules;
    $initial = move4Initial();
    $state = move4Placed();
    expect($state->data['board'])->toBe(['x', 'o', 'x', 'o', 'x', '', 'o', '', '', '', '', '', '', '', '', '']);
    expect($state->currentPlayer)->toBe(PlayerMark::X);
    expect($rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 15]))->reason)->toBe('placement_complete');
    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 2, 'to' => 15]));
    expect($result->validation->valid)->toBeTrue();
    expect($result->state)->not->toBe($state);
    expect($result->state->data['board'][2])->toBe('');
    expect($result->state->data['board'][15])->toBe('x');
    expect($result->state->currentPlayer)->toBe(PlayerMark::O);
    expect($state->data['board'][2])->toBe('x');
    expect($initial->data['board'])->toBe(array_fill(0, 16, ''));
});

test('rejects invalid actions without mutation', function (bool $placed, string $type, array $payload, string $reason) {
    $rules = new Move4x3Rules;
    $state = $placed ? move4Placed() : move4Initial();
    $result = $rules->apply($state, new GameAction(PlayerMark::X, $type, $payload));
    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
})->with([
    'invalid placement' => [false, 'place', ['cell' => 16], 'invalid_cell'],
    'negative placement' => [false, 'place', ['cell' => -1], 'invalid_cell'],
    'string placement' => [false, 'place', ['cell' => '2'], 'invalid_cell'],
    'unknown action' => [false, 'roll', [], 'unsupported_action'],
    'premature move' => [false, 'move', ['from' => 0, 'to' => 15], 'movement_not_started'],
    'extra placement' => [true, 'place', ['cell' => 15], 'placement_complete'],
    'empty source' => [true, 'move', ['from' => 15, 'to' => 8], 'empty_source'],
    'opponent source' => [true, 'move', ['from' => 1, 'to' => 8], 'not_your_piece'],
    'occupied destination' => [true, 'move', ['from' => 0, 'to' => 1], 'cell_occupied'],
    'same cell' => [true, 'move', ['from' => 0, 'to' => 0], 'same_cell'],
    'invalid destination' => [true, 'move', ['from' => 0, 'to' => 16], 'invalid_cell'],
]);

test('rejects a wrong turn and a fourth individual piece', function () {
    $rules = new Move4x3Rules;
    $initial = move4Initial();
    expect($rules->apply($initial, new GameAction(PlayerMark::O, 'place', ['cell' => 0]))->validation->reason)->toBe('not_your_turn');
    $state = $initial;
    foreach ([0, 1, 2, 3, 4] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }
    $forcedX = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X, $state->data);
    $result = $rules->apply($forcedX, new GameAction(PlayerMark::X, 'place', ['cell' => 15]));
    expect($result->validation->reason)->toBe('piece_limit_reached');
    expect($result->state)->toBe($forcedX);
});

test('finds each of the twenty-four bounded winning lines', function (array $line) {
    $rules = new Move4x3Rules;
    $board = array_fill(0, 16, '');
    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }
    $others = array_values(array_diff(range(0, 15), $line));
    $board[$others[0]] = 'o';
    $board[$others[1]] = 'o';
    $state = new MatchState(new Modality('4x3-move'), move4Initial()->participants, MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);
    expect($rules->winningLine($state))->toBe($line);
    expect($state->data['board'])->toBe($board);
})->with([
    'h1a' => [[0, 1, 2]], 'h1b' => [[1, 2, 3]], 'h2a' => [[4, 5, 6]], 'h2b' => [[5, 6, 7]],
    'h3a' => [[8, 9, 10]], 'h3b' => [[9, 10, 11]], 'h4a' => [[12, 13, 14]], 'h4b' => [[13, 14, 15]],
    'v1a' => [[0, 4, 8]], 'v1b' => [[4, 8, 12]], 'v2a' => [[1, 5, 9]], 'v2b' => [[5, 9, 13]],
    'v3a' => [[2, 6, 10]], 'v3b' => [[6, 10, 14]], 'v4a' => [[3, 7, 11]], 'v4b' => [[7, 11, 15]],
    'd1' => [[0, 5, 10]], 'd2' => [[1, 6, 11]], 'd3' => [[4, 9, 14]], 'd4' => [[5, 10, 15]],
    'd5' => [[2, 5, 8]], 'd6' => [[3, 6, 9]], 'd7' => [[6, 9, 12]], 'd8' => [[7, 10, 13]],
]);

test('ignores false lines crossing row boundaries', function (array $moves) {
    $rules = new Move4x3Rules;
    $state = move4Initial();
    foreach ($moves as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->winner)->toBeNull();
})->with(['horizontal' => [[2, 0, 3, 1, 4]], 'diagonal' => [[3, 0, 8, 1, 13]]]);

test('wins during placement and movement and cannot play afterward', function () {
    $rules = new Move4x3Rules;
    $state = move4Initial();
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }
    expect($state->status)->toBe(MatchStatus::Completed);
    expect($rules->winningLine($state))->toBe([0, 1, 2]);
    expect($rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 6]))->state)->toBe($state);
    $moving = move4Placed();
    expect($moving->status)->toBe(MatchStatus::InProgress);
    $won = $rules->apply($moving, new GameAction(PlayerMark::X, 'move', ['from' => 2, 'to' => 8]))->state;
    expect($won->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($won))->toBe([0, 4, 8]);
});

test('allows repeated movements without an automatic draw', function () {
    $rules = new Move4x3Rules;
    $state = move4Placed();
    for ($cycle = 0; $cycle < 10; $cycle++) {
        foreach ([[0, 15], [1, 14], [15, 0], [14, 1]] as [$from, $to]) {
            $result = $rules->apply($state, new GameAction($state->currentPlayer, 'move', ['from' => $from, 'to' => $to]));
            expect($result->validation->valid)->toBeTrue();
            $state = $result->state;
        }
    }
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->data['board'])->toBe(move4Placed()->data['board']);
});
