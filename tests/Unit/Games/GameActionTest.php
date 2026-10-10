<?php

use App\Games\GameAction;
use App\Games\PlayerMark;

test('an action retains its player, type, and modality-specific payload', function (string $type, array $payload) {
    $action = new GameAction(PlayerMark::O, $type, $payload);

    expect($action->player)->toBe(PlayerMark::O);
    expect($action->type)->toBe($type);
    expect($action->payload)->toBe($payload);
})->with([
    'placement' => ['place', ['cell' => 4]],
    'movement' => ['move', ['from' => 4, 'to' => 8]],
    'dice roll' => ['roll', []],
    'piece selection' => ['select_piece', ['size' => 'medium']],
]);

test('an action requires a nonblank type', function () {
    expect(fn () => new GameAction(PlayerMark::X, ' '))->toThrow(InvalidArgumentException::class);
});
