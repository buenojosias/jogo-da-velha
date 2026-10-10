<?php

namespace App\Games;

enum MatchStatus: string
{
    case Awaiting = 'awaiting';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Drawn = 'drawn';

    public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Drawn;
    }
}
