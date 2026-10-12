<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Games\Rules\Classic4x3Rules;
use App\Http\ClassicMatchManager;
use App\Http\ClassicRoundActions;
use App\Http\SessionMatchStore;
use Random\Engine\Mt19937;
use Random\Randomizer;

test('applies the selected classic engine and persists only accepted moves', function (string $ruleClass, array $winningMoves, int $boardSize) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $rules = app($ruleClass);
    $matchId = $ruleClass === Classic3x3Rules::class
        ? app(ClassicMatchManager::class)->create()
        : app(ClassicMatchManager::class)->create4x3($rules);
    $store = app(SessionMatchStore::class);
    $rounds = app(ClassicRoundActions::class);

    $firstMove = $rounds->place($matchId, $store->find($matchId), $rules, $winningMoves[0]);
    $rejectedMove = $rounds->place($matchId, $firstMove->state, $rules, $winningMoves[0]);

    expect($firstMove->validation->valid)->toBeTrue();
    expect($rejectedMove->validation->valid)->toBeFalse();
    expect($rejectedMove->state)->toBe($firstMove->state);
    expect($store->find($matchId)->data['board'][$winningMoves[0]])->toBe('x');
    expect($store->score($matchId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);

    foreach (array_slice($winningMoves, 1) as $cell) {
        $rounds->place($matchId, $store->find($matchId), $rules, $cell);
    }

    expect($store->find($matchId)->status)->toBe(MatchStatus::Completed);
    expect($store->find($matchId)->winner)->toBe(PlayerMark::X);
    expect($store->find($matchId)->data['board'])->toHaveCount($boardSize);
    expect($store->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
})->with([
    'three by three' => [Classic3x3Rules::class, [0, 3, 1, 4, 2], 9],
    'four by four' => [Classic4x3Rules::class, [0, 4, 1, 5, 2], 16],
]);

test('restarts each classic with the opposite starter without resetting its score', function (string $ruleClass, array $winningMoves, int $boardSize) {
    $this->app->instance(Randomizer::class, new Randomizer(new Mt19937(0)));
    $rules = app($ruleClass);
    $matchId = $ruleClass === Classic3x3Rules::class
        ? app(ClassicMatchManager::class)->create()
        : app(ClassicMatchManager::class)->create4x3($rules);
    $store = app(SessionMatchStore::class);
    $rounds = app(ClassicRoundActions::class);

    foreach ($winningMoves as $cell) {
        $rounds->place($matchId, $store->find($matchId), $rules, $cell);
    }

    $rounds->restart($matchId, $store->find($matchId), $rules);

    expect($store->startingPlayer($matchId))->toBe(PlayerMark::O);
    expect($store->find($matchId)->data['board'])->toBe(array_fill(0, $boardSize, ''));
    expect($store->find($matchId)->currentPlayer)->toBe(PlayerMark::O);
    expect($store->score($matchId))->toBe(['x' => 1, 'o' => 0, 'draws' => 0]);
})->with([
    'three by three' => [Classic3x3Rules::class, [0, 3, 1, 4, 2], 9],
    'four by four' => [Classic4x3Rules::class, [0, 4, 1, 5, 2], 16],
]);
