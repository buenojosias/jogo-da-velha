<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Move4x4Rules;

function move4x4Initial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new Move4x4Rules)->initialState(
        new Modality('4x4-move'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

function move4x4Place(array $cells): MatchState
{
    $rules = new Move4x4Rules;
    $state = move4x4Initial();
    foreach ($cells as $cell) {
        $state = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]))->state;
    }

    return $state;
}

test('starts with sixteen empty cells and either chosen starter', function (PlayerMark $starter) {
    $state = move4x4Initial($starter);
    expect($state->data)->toBe(['board' => array_fill(0, 16, '')]);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->currentPlayer)->toBe($starter);
})->with([PlayerMark::X, PlayerMark::O]);

test('uses eight placements then allows a distant movement without mutating the source', function () {
    $rules = new Move4x4Rules;
    $initial = move4x4Initial();
    $state = move4x4Place([0, 5, 1, 6, 2, 8, 4, 9]);
    expect(count(array_filter($state->data['board'])))->toBe(8);
    expect($state->currentPlayer)->toBe(PlayerMark::X);
    expect($rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 15]))->reason)->toBe('placement_complete');

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 4, 'to' => 15]));
    expect($result->validation->valid)->toBeTrue();
    expect($result->state)->not->toBe($state);
    expect($result->state->data['board'][4])->toBe('');
    expect($result->state->data['board'][15])->toBe('x');
    expect($result->state->currentPlayer)->toBe(PlayerMark::O);
    expect($state->data['board'][4])->toBe('x');
    expect($initial->data['board'])->toBe(array_fill(0, 16, ''));
});

test('rejects invalid actions without changing the original state', function (bool $placed, PlayerMark $actor, string $type, array $payload, string $reason) {
    $rules = new Move4x4Rules;
    $state = $placed ? move4x4Place([0, 5, 1, 6, 2, 8, 4, 9]) : move4x4Initial();
    $result = $rules->apply($state, new GameAction($actor, $type, $payload));
    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
})->with([
    'wrong turn' => [false, PlayerMark::O, 'place', ['cell' => 0], 'not_your_turn'],
    'unknown action' => [false, PlayerMark::X, 'roll', [], 'unsupported_action'],
    'negative cell' => [false, PlayerMark::X, 'place', ['cell' => -1], 'invalid_cell'],
    'out of range' => [false, PlayerMark::X, 'place', ['cell' => 16], 'invalid_cell'],
    'string cell' => [false, PlayerMark::X, 'place', ['cell' => '2'], 'invalid_cell'],
    'occupied cell' => [true, PlayerMark::X, 'move', ['from' => 0, 'to' => 5], 'cell_occupied'],
    'early movement' => [false, PlayerMark::X, 'move', ['from' => 0, 'to' => 15], 'movement_not_started'],
    'ninth placement' => [true, PlayerMark::X, 'place', ['cell' => 15], 'placement_complete'],
    'empty source' => [true, PlayerMark::X, 'move', ['from' => 15, 'to' => 14], 'empty_source'],
    'opponent source' => [true, PlayerMark::X, 'move', ['from' => 5, 'to' => 14], 'not_your_piece'],
    'same cell' => [true, PlayerMark::X, 'move', ['from' => 0, 'to' => 0], 'same_cell'],
    'invalid destination' => [true, PlayerMark::X, 'move', ['from' => 0, 'to' => 16], 'invalid_cell'],
]);

test('enforces the individual four-piece limit', function () {
    $state = move4x4Place([0, 5, 1, 6, 2, 8, 4]);
    $forced = new MatchState($state->modality, $state->participants, MatchStatus::InProgress, PlayerMark::X, $state->data);
    $result = (new Move4x4Rules)->apply($forced, new GameAction(PlayerMark::X, 'place', ['cell' => 15]));
    expect($result->validation->reason)->toBe('piece_limit_reached');
    expect($result->state)->toBe($forced);
});

test('finds each of the ten four-cell winning lines', function (array $line) {
    $board = array_fill(0, 16, '');
    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }
    foreach (array_slice(array_values(array_diff(range(0, 15), $line)), 0, 3) as $cell) {
        $board[$cell] = 'o';
    }
    $state = new MatchState(new Modality('4x4-move'), move4x4Initial()->participants, MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);
    expect((new Move4x4Rules)->winningLine($state))->toBe($line);
})->with([
    'row 0' => [[0, 1, 2, 3]], 'row 1' => [[4, 5, 6, 7]], 'row 2' => [[8, 9, 10, 11]], 'row 3' => [[12, 13, 14, 15]],
    'column 0' => [[0, 4, 8, 12]], 'column 1' => [[1, 5, 9, 13]], 'column 2' => [[2, 6, 10, 14]], 'column 3' => [[3, 7, 11, 15]],
    'main diagonal' => [[0, 5, 10, 15]], 'reverse diagonal' => [[3, 6, 9, 12]],
]);

test('does not treat a wrapped row as a winning line', function () {
    $board = array_fill(0, 16, '');
    foreach ([2, 3, 4, 5] as $cell) {
        $board[$cell] = 'x';
    }
    foreach ([0, 1, 6] as $cell) {
        $board[$cell] = 'o';
    }
    $state = new MatchState(new Modality('4x4-move'), move4x4Initial()->participants, MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);
    expect((new Move4x4Rules)->winningLine($state))->toBeNull();
});

test('wins on placement and movement, with no further accepted action', function () {
    $rules = new Move4x4Rules;
    $placement = move4x4Place([0, 4, 1, 5, 2, 6, 3]);
    expect($placement->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($placement))->toBe([0, 1, 2, 3]);
    expect($rules->apply($placement, new GameAction(PlayerMark::O, 'place', ['cell' => 7]))->state)->toBe($placement);
    $moving = move4x4Place([0, 5, 1, 6, 2, 8, 4, 9]);
    $won = $rules->apply($moving, new GameAction(PlayerMark::X, 'move', ['from' => 4, 'to' => 3]))->state;
    expect($won->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($won))->toBe([0, 1, 2, 3]);
});

test('allows repeated movements without an automatic draw', function () {
    $rules = new Move4x4Rules;
    $state = move4x4Place([0, 5, 1, 6, 2, 8, 4, 9]);
    for ($cycle = 0; $cycle < 8; $cycle++) {
        foreach ([[4, 15], [5, 14], [15, 4], [14, 5]] as [$from, $to]) {
            $state = $rules->apply($state, new GameAction($state->currentPlayer, 'move', ['from' => $from, 'to' => $to]))->state;
        }
    }
    expect($state->status)->toBe(MatchStatus::InProgress);
});
