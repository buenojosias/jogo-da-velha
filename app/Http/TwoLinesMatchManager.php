<?php

namespace App\Http;

use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\TwoLines5x5Rules;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;
use Random\Randomizer;
use UnexpectedValueException;

class TwoLinesMatchManager
{
    private const string ACTIVE_MATCH_KEY = 'twoLines5x5.active_match_id';

    public function __construct(
        private Session $session,
        private SessionMatchStore $store,
        private TwoLines5x5Rules $rules,
        private Randomizer $randomizer,
    ) {}

    public function resumeOrCreate(): string
    {
        return $this->validActiveId() ?? $this->createNewActive();
    }

    public function createNewActive(): string
    {
        $matchId = $this->store->create($this->rules->initialState(
            new Modality('5x5-2-lines'),
            new Participants(
                new Participant(ParticipantKind::Guest, 'local-x'),
                new Participant(ParticipantKind::Guest, 'local-o'),
            ),
            $this->randomizer->getInt(0, 1) === 0 ? PlayerMark::X : PlayerMark::O,
        ));
        $this->session->put(self::ACTIVE_MATCH_KEY, $matchId);

        return $matchId;
    }

    public function isActive(string $matchId): bool
    {
        return $this->validActiveId() === $matchId;
    }

    private function validActiveId(): ?string
    {
        $matchId = $this->session->get(self::ACTIVE_MATCH_KEY);
        if (! is_string($matchId)) {
            return null;
        }

        try {
            $state = $this->store->find($matchId);
            if ($state === null || $state->modality->key !== '5x5-2-lines') {
                return null;
            }
            $this->store->startingPlayer($matchId);
            $this->store->score($matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            return null;
        }

        return $matchId;
    }
}
