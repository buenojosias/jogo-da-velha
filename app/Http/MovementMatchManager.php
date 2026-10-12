<?php

namespace App\Http;

use App\Games\GameRules;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Move3x3Rules;
use App\Games\Rules\Move4x3Rules;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;
use Random\Randomizer;
use UnexpectedValueException;

class MovementMatchManager
{
    private const string ACTIVE_MATCH_KEY = 'movement3x3.active_match_id';

    private const string ACTIVE_4X3_MATCH_KEY = 'movement4x3.active_match_id';

    public function __construct(
        private Session $session,
        private SessionMatchStore $store,
        private Move3x3Rules $rules,
        private Randomizer $randomizer,
    ) {}

    public function resumeOrCreate(): string
    {
        return $this->validActiveId(self::ACTIVE_MATCH_KEY, '3-move') ?? $this->createNewActive();
    }

    public function createNewActive(): string
    {
        $matchId = $this->create();
        $this->session->put(self::ACTIVE_MATCH_KEY, $matchId);

        return $matchId;
    }

    public function isActive(string $matchId): bool
    {
        return $this->validActiveId(self::ACTIVE_MATCH_KEY, '3-move') === $matchId;
    }

    public function create(): string
    {
        return $this->createWithRules(new Modality('3-move'), $this->rules);
    }

    public function resumeOrCreate4x3(Move4x3Rules $rules): string
    {
        return $this->validActiveId(self::ACTIVE_4X3_MATCH_KEY, '4x3-move') ?? $this->createNewActive4x3($rules);
    }

    public function createNewActive4x3(Move4x3Rules $rules): string
    {
        $matchId = $this->create4x3($rules);
        $this->session->put(self::ACTIVE_4X3_MATCH_KEY, $matchId);

        return $matchId;
    }

    public function isActive4x3(string $matchId): bool
    {
        return $this->validActiveId(self::ACTIVE_4X3_MATCH_KEY, '4x3-move') === $matchId;
    }

    public function create4x3(Move4x3Rules $rules): string
    {
        return $this->createWithRules(new Modality('4x3-move'), $rules);
    }

    private function createWithRules(Modality $modality, GameRules $rules): string
    {
        return $this->store->create($rules->initialState(
            $modality,
            new Participants(
                new Participant(ParticipantKind::Guest, 'local-x'),
                new Participant(ParticipantKind::Guest, 'local-o'),
            ),
            $this->randomizer->getInt(0, 1) === 0 ? PlayerMark::X : PlayerMark::O,
        ));
    }

    private function validActiveId(string $sessionKey, string $modalityKey): ?string
    {
        $matchId = $this->session->get($sessionKey);

        if (! is_string($matchId)) {
            return null;
        }

        try {
            $state = $this->store->find($matchId);

            if ($state === null || $state->modality->key !== $modalityKey) {
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
