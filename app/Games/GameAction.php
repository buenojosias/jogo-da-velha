<?php

namespace App\Games;

use InvalidArgumentException;

readonly class GameAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public PlayerMark $player,
        public string $type,
        public array $payload = [],
    ) {
        if (trim($type) === '') {
            throw new InvalidArgumentException('An action type is required.');
        }
    }
}
