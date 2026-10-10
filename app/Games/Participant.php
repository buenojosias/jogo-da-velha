<?php

namespace App\Games;

use InvalidArgumentException;

readonly class Participant
{
    public function __construct(
        public ParticipantKind $kind,
        public string $id,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A participant identifier is required.');
        }
    }
}
