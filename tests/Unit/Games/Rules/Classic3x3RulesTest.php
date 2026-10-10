<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;

test('starts with nine empty cells and the chosen player', function (PlayerMark $startingPlayer) {
    $modality = new Modality('3-basic');
    $participants = classicParticipants();

    $state = (new Classic3x3Rules)->initialState($modality, $participants, $startingPlayer);

    expect($state->modality)->toBe($modality);
    expect($state->participants)->toBe($participants);
    expect($state->status)->toBe(MatchStatus::InProgress);
    expect($state->currentPlayer)->toBe($startingPlayer);
    expect($state->winner)->toBeNull();
    expect($state->data)->toBe(['board' => array_fill(0, 9, '')]);
})->with([
    'X starts' => [PlayerMark::X],
    'O starts' => [PlayerMark::O],
]);

test('rejects a modality other than 3-basic', function () {
    $rules = new Classic3x3Rules;

    expect(fn () => $rules->initialState(new Modality('3-move'), classicParticipants(), PlayerMark::X))
        ->toThrow(InvalidArgumentException::class);
});

test('places the selected mark and alternates turns without changing the original state', function (PlayerMark $startingPlayer, PlayerMark $nextPlayer) {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), $startingPlayer);

    $first = $rules->apply($initial, new GameAction($startingPlayer, 'place', ['cell' => 0]));
    $second = $rules->apply($first->state, new GameAction($nextPlayer, 'place', ['cell' => 4]));

    expect($first->validation->valid)->toBeTrue();
    expect($first->state)->not->toBe($initial);
    expect($first->state->data['board'])->toBe([$startingPlayer->value, '', '', '', '', '', '', '', '']);
    expect($first->state->currentPlayer)->toBe($nextPlayer);
    expect($second->validation->valid)->toBeTrue();
    expect($second->state->data['board'][4])->toBe($nextPlayer->value);
    expect($second->state->currentPlayer)->toBe($startingPlayer);
    expect($initial->data['board'])->toBe(array_fill(0, 9, ''));
    expect($first->state->data['board'][4])->toBe('');
})->with([
    'X then O' => [PlayerMark::X, PlayerMark::O],
    'O then X' => [PlayerMark::O, PlayerMark::X],
]);

test('rejects a move by the player outside their turn and preserves the state', function () {
    $rules = new Classic3x3Rules;
    $state = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($result->validation->valid)->toBeFalse();
    expect($result->validation->reason)->toBe('not_your_turn');
    expect($result->state)->toBe($state);
    expect($state->data['board'])->toBe(array_fill(0, 9, ''));
});

test('rejects an occupied cell and preserves the state', function () {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);
    $state = $rules->apply($initial, new GameAction(PlayerMark::X, 'place', ['cell' => 0]))->state;

    $result = $rules->apply($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($result->validation->reason)->toBe('cell_occupied');
    expect($result->state)->toBe($state);
    expect($state->data['board'][0])->toBe('x');
    expect($state->currentPlayer)->toBe(PlayerMark::O);
});

test('rejects an invalid cell index and preserves the state', function (array $payload) {
    $rules = new Classic3x3Rules;
    $state = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', $payload));

    expect($result->validation->reason)->toBe('invalid_cell');
    expect($result->state)->toBe($state);
    expect($state->data['board'])->toBe(array_fill(0, 9, ''));
})->with([
    'negative' => [['cell' => -1]],
    'past the board' => [['cell' => 9]],
    'numeric string' => [['cell' => '4']],
    'floating point' => [['cell' => 4.0]],
    'boolean' => [['cell' => true]],
    'null' => [['cell' => null]],
    'missing' => [[]],
]);

test('rejects an unsupported action and preserves the state', function () {
    $rules = new Classic3x3Rules;
    $state = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'move', ['from' => 0, 'to' => 1]));

    expect($result->validation->reason)->toBe('unsupported_action');
    expect($result->state)->toBe($state);
});

test('reports the first rejection in the documented precedence', function () {
    $rules = new Classic3x3Rules;
    $state = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $unsupported = $rules->validate($state, new GameAction(PlayerMark::O, 'move', ['cell' => -1]));
    $wrongTurn = $rules->validate($state, new GameAction(PlayerMark::O, 'place', ['cell' => -1]));

    expect($unsupported->reason)->toBe('unsupported_action');
    expect($wrongTurn->reason)->toBe('not_your_turn');
});

test('does not start an action while the match is awaiting', function () {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::Awaiting, PlayerMark::X, $initial->data);

    $result = $rules->apply($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]));

    expect($result->validation->reason)->toBe('game_not_started');
    expect($result->state)->toBe($state);
});

test('recognizes a winning line for either player', function (PlayerMark $winner, array $moves) {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), $winner);

    $state = playClassicMoves($rules, $initial, $moves);

    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe($winner);
    expect($state->currentPlayer)->toBeNull();
    expect($state->isFinished())->toBeTrue();
})->with([
    'X horizontal' => [PlayerMark::X, [0, 3, 1, 4, 2]],
    'O horizontal' => [PlayerMark::O, [0, 3, 1, 4, 2]],
    'X vertical' => [PlayerMark::X, [0, 1, 3, 2, 6]],
    'O vertical' => [PlayerMark::O, [0, 1, 3, 2, 6]],
    'X descending diagonal' => [PlayerMark::X, [0, 1, 4, 2, 8]],
    'O descending diagonal' => [PlayerMark::O, [0, 1, 4, 2, 8]],
    'X ascending diagonal' => [PlayerMark::X, [2, 0, 4, 1, 6]],
    'O ascending diagonal' => [PlayerMark::O, [2, 0, 4, 1, 6]],
]);

test('declares a draw when the ninth move fills the board without a winner', function () {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $state = playClassicMoves($rules, $initial, [0, 1, 2, 4, 3, 5, 7, 6, 8]);

    expect($state->data['board'])->toBe(['x', 'o', 'x', 'x', 'o', 'o', 'o', 'x', 'x']);
    expect($state->status)->toBe(MatchStatus::Drawn);
    expect($state->winner)->toBeNull();
    expect($state->currentPlayer)->toBeNull();
});

test('prioritizes a winning line completed on the ninth move over a draw', function () {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $state = playClassicMoves($rules, $initial, [0, 3, 1, 5, 4, 7, 6, 8, 2]);

    expect($state->status)->toBe(MatchStatus::Completed);
    expect($state->winner)->toBe(PlayerMark::X);
    expect($state->currentPlayer)->toBeNull();
    expect($state->data['board'])->not->toContain('');
});

test('rejects further moves after a win or draw', function (array $moves) {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);
    $finished = playClassicMoves($rules, $initial, $moves);

    $result = $rules->apply($finished, new GameAction(PlayerMark::O, 'move', ['cell' => -1]));

    expect($result->validation->reason)->toBe('game_finished');
    expect($result->state)->toBe($finished);
})->with([
    'victory' => [[0, 3, 1, 4, 2]],
    'draw' => [[0, 1, 2, 4, 3, 5, 7, 6, 8]],
]);

test('validates without modifying the match or retaining hidden state', function () {
    $rules = new Classic3x3Rules;
    $state = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);

    $first = $rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]));
    $second = $rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0]));
    $rejected = $rules->validate($state, new GameAction(PlayerMark::O, 'place', ['cell' => 0]));

    expect($first->valid)->toBeTrue();
    expect($second->valid)->toBeTrue();
    expect($rejected->reason)->toBe('not_your_turn');
    expect($state->data['board'])->toBe(array_fill(0, 9, ''));
    expect($state->currentPlayer)->toBe(PlayerMark::X);
});

test('rejects an invalid board state instead of using malformed cells', function () {
    $rules = new Classic3x3Rules;
    $initial = $rules->initialState(new Modality('3-basic'), classicParticipants(), PlayerMark::X);
    $state = new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, PlayerMark::X, ['board' => ['', 'x']]);

    expect(fn () => $rules->validate($state, new GameAction(PlayerMark::X, 'place', ['cell' => 0])))
        ->toThrow(InvalidArgumentException::class);
});

function classicParticipants(): Participants
{
    return new Participants(
        new Participant(ParticipantKind::Guest, 'visitor-x'),
        new Participant(ParticipantKind::Guest, 'visitor-o'),
    );
}

/**
 * @param  list<int>  $moves
 */
function playClassicMoves(Classic3x3Rules $rules, MatchState $state, array $moves): MatchState
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
