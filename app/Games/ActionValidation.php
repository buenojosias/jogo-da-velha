<?php

namespace App\Games;

use InvalidArgumentException;

readonly class ActionValidation
{
    private function __construct(
        public bool $valid,
        public ?string $reason,
    ) {}

    public static function valid(): self
    {
        return new self(true, null);
    }

    public static function invalid(string $reason): self
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return new self(false, $reason);
    }
}
