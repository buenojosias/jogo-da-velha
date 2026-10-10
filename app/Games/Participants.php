<?php

namespace App\Games;

use InvalidArgumentException;

readonly class Participants
{
    public function __construct(
        public Participant $x,
        public Participant $o,
    ) {
        if ($x->kind === $o->kind && $x->id === $o->id) {
            throw new InvalidArgumentException('The two participants must be distinct.');
        }
    }

    public function for(PlayerMark $mark): Participant
    {
        return match ($mark) {
            PlayerMark::X => $this->x,
            PlayerMark::O => $this->o,
        };
    }
}
