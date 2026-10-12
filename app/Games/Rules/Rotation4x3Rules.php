<?php

namespace App\Games\Rules;

class Rotation4x3Rules extends RotationRules
{
    private const array WINNING_LINES = [
        [0, 1, 2], [1, 2, 3], [4, 5, 6], [5, 6, 7],
        [8, 9, 10], [9, 10, 11], [12, 13, 14], [13, 14, 15],
        [0, 4, 8], [4, 8, 12], [1, 5, 9], [5, 9, 13],
        [2, 6, 10], [6, 10, 14], [3, 7, 11], [7, 11, 15],
        [0, 5, 10], [1, 6, 11], [4, 9, 14], [5, 10, 15],
        [2, 5, 8], [3, 6, 9], [6, 9, 12], [7, 10, 13],
    ];

    protected function modalityKey(): string
    {
        return '4x3-rotation';
    }

    protected function boardSize(): int
    {
        return 16;
    }

    protected function winningLines(): array
    {
        return self::WINNING_LINES;
    }
}
