<?php

namespace App\Games;

use InvalidArgumentException;

readonly class MatchState
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public Modality $modality,
        public Participants $participants,
        public MatchStatus $status,
        public ?PlayerMark $currentPlayer,
        public array $data = [],
        public ?PlayerMark $winner = null,
    ) {
        if (($status->isFinished() && $currentPlayer !== null)
            || (! $status->isFinished() && $currentPlayer === null)) {
            throw new InvalidArgumentException('Only an unfinished match has a next player.');
        }

        if (($status === MatchStatus::Completed) !== ($winner !== null)) {
            throw new InvalidArgumentException('Only a completed match has a winner.');
        }
    }

    public function isFinished(): bool
    {
        return $this->status->isFinished();
    }
}
