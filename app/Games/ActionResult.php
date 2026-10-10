<?php

namespace App\Games;

readonly class ActionResult
{
    private function __construct(
        public MatchState $state,
        public ActionValidation $validation,
    ) {}

    public static function accepted(MatchState $state): self
    {
        return new self($state, ActionValidation::valid());
    }

    public static function rejected(MatchState $unchangedState, string $reason): self
    {
        return new self($unchangedState, ActionValidation::invalid($reason));
    }
}
