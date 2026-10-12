<?php

namespace App\Games\Rules;

class Rotation3x3Rules extends RotationRules
{
    private const array WINNING_LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6],
    ];

    protected function modalityKey(): string
    {
        return '3-rotation';
    }

    protected function boardSize(): int
    {
        return 9;
    }

    protected function winningLines(): array
    {
        return self::WINNING_LINES;
    }
}
