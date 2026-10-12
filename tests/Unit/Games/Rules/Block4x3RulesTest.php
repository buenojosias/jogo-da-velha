<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Block4x3Rules;

function blockInitial(PlayerMark $starter = PlayerMark::X): MatchState
{
    return (new Block4x3Rules)->initialState(
        new Modality('4x3-block'),
        new Participants(new Participant(ParticipantKind::Guest, 'local-x'), new Participant(ParticipantKind::Guest, 'local-o')),
        $starter,
    );
}

function blockState(array $x, array $o, PlayerMark $turn = PlayerMark::X): MatchState
{
    $board = array_fill(0, 16, '');
    foreach ($x as $cell) {
        $board[$cell] = 'x';
    }
    foreach ($o as $cell) {
        $board[$cell] = 'o';
    }

    return new MatchState(new Modality('4x3-block'), blockInitial()->participants, MatchStatus::InProgress, $turn, ['board' => $board]);
}

test('starts with sixteen empty cells and the selected starter', function (PlayerMark $starter) {
    $state = blockInitial($starter);
    expect($state->data['board'])->toBe(array_fill(0, 16, ''));
    expect($state->currentPlayer)->toBe($starter);
    expect($state->status)->toBe(MatchStatus::InProgress);
})->with([PlayerMark::X, PlayerMark::O]);

test('rejects invalid moves without mutating the original state', function (PlayerMark $player, string $type, array $payload, string $reason) {
    $state = blockInitial();
    $result = (new Block4x3Rules)->apply($state, new GameAction($player, $type, $payload));
    expect($result->validation->reason)->toBe($reason);
    expect($result->state)->toBe($state);
})->with([
    'wrong turn' => [PlayerMark::O, 'place', ['cell' => 0], 'not_your_turn'],
    'unknown action' => [PlayerMark::X, 'move', ['from' => 0, 'to' => 1], 'unsupported_action'],
    'negative cell' => [PlayerMark::X, 'place', ['cell' => -1], 'invalid_cell'],
    'out of range' => [PlayerMark::X, 'place', ['cell' => 16], 'invalid_cell'],
    'string cell' => [PlayerMark::X, 'place', ['cell' => '0'], 'invalid_cell'],
]);

test('places pieces, alternates turns, and rejects occupied cells', function () {
    $rules = new Block4x3Rules;
    $initial = blockInitial();
    $afterX = $rules->apply($initial, new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state;
    $rejected = $rules->apply($afterX, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));
    expect($initial->data['board'][0])->toBe('');
    expect($afterX->data['board'][0])->toBe('x');
    expect($afterX->currentPlayer)->toBe(PlayerMark::O);
    expect($rejected->validation->reason)->toBe('cell_occupied');
    expect($rejected->state)->toBe($afterX);
});

test('recognizes each of the twenty-four geometric triplets when unblocked', function (array $line) {
    $board = array_fill(0, 16, '');
    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }
    $state = new MatchState(new Modality('4x3-block'), blockInitial()->participants, MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);
    expect((new Block4x3Rules)->winningLine($state))->toBe($line);
})->with([
    'h0a' => [[0, 1, 2]], 'h0b' => [[1, 2, 3]], 'h1a' => [[4, 5, 6]], 'h1b' => [[5, 6, 7]],
    'h2a' => [[8, 9, 10]], 'h2b' => [[9, 10, 11]], 'h3a' => [[12, 13, 14]], 'h3b' => [[13, 14, 15]],
    'v0a' => [[0, 4, 8]], 'v0b' => [[4, 8, 12]], 'v1a' => [[1, 5, 9]], 'v1b' => [[5, 9, 13]],
    'v2a' => [[2, 6, 10]], 'v2b' => [[6, 10, 14]], 'v3a' => [[3, 7, 11]], 'v3b' => [[7, 11, 15]],
    'd0' => [[0, 5, 10]], 'd1' => [[1, 6, 11]], 'd2' => [[4, 9, 14]], 'd3' => [[5, 10, 15]],
    'd4' => [[2, 5, 8]], 'd5' => [[3, 6, 9]], 'd6' => [[6, 9, 12]], 'd7' => [[7, 10, 13]],
]);

test('does not cross row boundaries or falsely block descending diagonals', function (array $line, int $falseBlocker) {
    $board = array_fill(0, 16, '');
    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }
    $board[$falseBlocker] = 'o';
    $state = new MatchState(new Modality('4x3-block'), blockInitial()->participants, MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);
    expect((new Block4x3Rules)->winningLine($state))->toBe($line);
})->with([
    'upper descending diagonal' => [[3, 6, 9], 0],
    'lower descending diagonal' => [[7, 10, 13], 4],
    'right edge descending diagonal' => [[2, 5, 8], 11],
    'left edge horizontal' => [[4, 5, 6], 3],
    'right edge horizontal' => [[5, 6, 7], 8],
]);

test('does not recognize a false triplet crossing a row boundary', function () {
    $board = array_fill(0, 16, '');
    foreach ([2, 3, 4] as $cell) {
        $board[$cell] = 'x';
    }
    $state = new MatchState(new Modality('4x3-block'), blockInitial()->participants, MatchStatus::Completed, null, ['board' => $board], PlayerMark::X);

    expect((new Block4x3Rules)->winningLine($state))->toBeNull();
});

test('accepts a placement but does not award a blocked horizontal victory', function () {
    $rules = new Block4x3Rules;
    $before = blockState([0, 1], [3, 4]);
    $result = $rules->apply($before, new GameAction(PlayerMark::X, 'place', ['cell' => 2]));
    expect($result->validation->valid)->toBeTrue();
    expect($result->state->status)->toBe(MatchStatus::InProgress);
    expect($result->state->data['board'][2])->toBe('x');
    expect($rules->blockersForMove($result->state, PlayerMark::X, 2))->toBe([3]);
});

test('recognizes a second unblocked line made by the same placement', function () {
    $rules = new Block4x3Rules;
    $before = blockState([0, 1, 6, 10], [3, 4, 5, 8]);
    $result = $rules->apply($before, new GameAction(PlayerMark::X, 'place', ['cell' => 2]));
    expect($result->state->winner)->toBe(PlayerMark::X);
    expect($rules->winningLine($result->state))->toBe([2, 6, 10]);
    expect($rules->blockersForMove($result->state, PlayerMark::X, 2))->toBe([3]);
});

test('identifies blockers on both valid ends but ignores off-board ends', function (array $line, array $blockers) {
    $board = array_fill(0, 16, '');
    foreach ($line as $cell) {
        $board[$cell] = 'x';
    }
    foreach ($blockers as $cell) {
        $board[$cell] = 'o';
    }
    $state = new MatchState(new Modality('4x3-block'), blockInitial()->participants, MatchStatus::InProgress, PlayerMark::X, ['board' => $board]);
    expect((new Block4x3Rules)->blockersForMove($state, PlayerMark::X, $line[1]))->toBe($blockers);
})->with([
    'horizontal before' => [[1, 2, 3], [0]],
    'horizontal after' => [[0, 1, 2], [3]],
    'vertical before' => [[4, 8, 12], [0]],
    'vertical after' => [[0, 4, 8], [12]],
    'diagonal before' => [[5, 10, 15], [0]],
    'diagonal after' => [[0, 5, 10], [15]],
    'descending before' => [[6, 9, 12], [3]],
    'descending after' => [[3, 6, 9], [12]],
]);

test('ends in a draw when the last valid cell has no unblocked line', function () {
    $rules = new Block4x3Rules;
    $state = blockInitial();
    foreach ([2, 5, 12, 7, 0, 8, 10, 9, 13, 3, 1, 14, 11, 15, 4, 6] as $cell) {
        $result = $rules->apply($state, new GameAction($state->currentPlayer, 'place', ['cell' => $cell]));
        expect($result->validation->valid)->toBeTrue();
        $state = $result->state;
    }
    expect($state->status)->toBe(MatchStatus::Drawn);
    expect($state->winner)->toBeNull();
    expect($rules->winningLine($state))->toBeNull();
    expect($rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state)->toBe($state);
});
