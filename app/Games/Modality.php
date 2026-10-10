<?php

namespace App\Games;

use InvalidArgumentException;

readonly class Modality
{
    /**
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(
        public string $key,
        public array $configuration = [],
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('A modality key is required.');
        }
    }
}
