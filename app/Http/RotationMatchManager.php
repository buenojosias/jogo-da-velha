<?php

namespace App\Http;

use App\Games\GameRules;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Rotation3x3Rules;
use App\Games\Rules\Rotation4x3Rules;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;
use Random\Randomizer;
use UnexpectedValueException;

class RotationMatchManager
{
    private const string ACTIVE_3X3_KEY = 'rotation3x3.active_match_id';

    private const string ACTIVE_4X3_KEY = 'rotation4x3.active_match_id';

    public function __construct(
        private Session $session,
        private SessionMatchStore $store,
        private Rotation3x3Rules $rules3x3,
        private Rotation4x3Rules $rules4x3,
        private Randomizer $randomizer,
    ) {}

    public function resumeOrCreate3x3(): string
    {
        return $this->validActiveId(self::ACTIVE_3X3_KEY, '3-rotation') ?? $this->createNewActive3x3();
    }

    public function resumeOrCreate4x3(): string
    {
        return $this->validActiveId(self::ACTIVE_4X3_KEY, '4x3-rotation') ?? $this->createNewActive4x3();
    }

    public function createNewActive3x3(): string
    {
        return $this->createActive(self::ACTIVE_3X3_KEY, '3-rotation', $this->rules3x3);
    }

    public function createNewActive4x3(): string
    {
        return $this->createActive(self::ACTIVE_4X3_KEY, '4x3-rotation', $this->rules4x3);
    }

    public function isActive3x3(string $matchId): bool
    {
        return $this->validActiveId(self::ACTIVE_3X3_KEY, '3-rotation') === $matchId;
    }

    public function isActive4x3(string $matchId): bool
    {
        return $this->validActiveId(self::ACTIVE_4X3_KEY, '4x3-rotation') === $matchId;
    }

    private function createActive(string $key, string $modality, GameRules $rules): string
    {
        $matchId = $this->store->create($rules->initialState(
            new Modality($modality),
            new Participants(
                new Participant(ParticipantKind::Guest, 'local-x'),
                new Participant(ParticipantKind::Guest, 'local-o'),
            ),
            $this->randomizer->getInt(0, 1) === 0 ? PlayerMark::X : PlayerMark::O,
        ));
        $this->session->put($key, $matchId);

        return $matchId;
    }

    private function validActiveId(string $key, string $modality): ?string
    {
        $matchId = $this->session->get($key);
        if (! is_string($matchId)) {
            return null;
        }

        try {
            $state = $this->store->find($matchId);
            if ($state === null || $state->modality->key !== $modality) {
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
