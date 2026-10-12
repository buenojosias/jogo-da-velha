<?php

namespace App\Http;

use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Move3x3Rules;
use Random\Randomizer;

class MovementMatchManager
{
    public function __construct(
        private SessionMatchStore $store,
        private Move3x3Rules $rules,
        private Randomizer $randomizer,
    ) {}

    public function create(): string
    {
        return $this->store->create($this->rules->initialState(
            new Modality('3-move'),
            new Participants(
                new Participant(ParticipantKind::Guest, 'local-x'),
                new Participant(ParticipantKind::Guest, 'local-o'),
            ),
            $this->randomizer->getInt(0, 1) === 0 ? PlayerMark::X : PlayerMark::O,
        ));
    }
}
