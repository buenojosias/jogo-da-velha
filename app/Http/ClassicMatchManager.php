<?php

namespace App\Http;

use App\Games\GameRules;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Games\Rules\Classic4x3Rules;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;
use Random\Randomizer;
use UnexpectedValueException;

class ClassicMatchManager
{
    private const string ACTIVE_MATCH_KEY = 'classic.active_match_id';

    public function __construct(
        private Session $session,
        private SessionMatchStore $store,
        private Classic3x3Rules $rules,
        private Randomizer $randomizer,
    ) {}

    public function resumeOrCreate(): string
    {
        return $this->validActiveId() ?? $this->createNewActive();
    }

    public function createNewActive(): string
    {
        $matchId = $this->create();
        $this->session->put(self::ACTIVE_MATCH_KEY, $matchId);

        return $matchId;
    }

    public function create(): string
    {
        return $this->createWithRules(new Modality('3-basic'), $this->rules);
    }

    public function create4x3(Classic4x3Rules $rules): string
    {
        return $this->createWithRules(new Modality('4x3-basic'), $rules);
    }

    private function createWithRules(Modality $modality, GameRules $rules): string
    {
        $state = $rules->initialState(
            $modality,
            new Participants(
                new Participant(ParticipantKind::Guest, 'local-x'),
                new Participant(ParticipantKind::Guest, 'local-o'),
            ),
            $this->randomizer->getInt(0, 1) === 0 ? PlayerMark::X : PlayerMark::O,
        );

        return $this->store->create($state);
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

            if ($state === null || $state->modality->key !== '3-basic') {
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
