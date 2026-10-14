<?php

namespace App\Http;

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Rules\Classic4x3Rules;
use App\Games\Rules\Move4x3Rules;
use App\Games\Rules\Move4x4Rules;

class ExitMatchAction
{
    public function __construct(private SessionMatchStore $store) {}

    public function assertActive(string $modality, string $matchId): MatchState
    {
        $active = match ($modality) {
            '3-basic' => app(ClassicMatchManager::class)->isActive($matchId),
            '4x3-basic' => app(ClassicMatchManager::class)->isActive4x3($matchId),
            '3-move' => app(MovementMatchManager::class)->isActive($matchId),
            '4x3-move' => app(MovementMatchManager::class)->isActive4x3($matchId),
            '4x4-move' => app(MovementMatchManager::class)->isActive4x4($matchId),
            '3-rotation' => app(RotationMatchManager::class)->isActive3x3($matchId),
            '4x3-rotation' => app(RotationMatchManager::class)->isActive4x3($matchId),
            '4x3-block' => app(BlockMatchManager::class)->isActive($matchId),
            '4x3-hidden' => app(HiddenMatchManager::class)->isActive($matchId),
            '5x5-2-lines' => app(TwoLinesMatchManager::class)->isActive($matchId),
            '5-full' => app(FullMatchManager::class)->isActive($matchId),
            'dice' => app(DiceMatchManager::class)->isActive($matchId),
            'crazy' => app(CrazyMatchManager::class)->isActive($matchId),
            'ultimate' => app(UltimateMatchManager::class)->isActive($matchId),
            default => false,
        };

        abort_unless($active, 404);
        $state = $this->store->find($matchId);
        abort_unless($state?->modality->key === $modality, 404);

        return $state;
    }

    public function hasProgress(MatchState $state, string $matchId): bool
    {
        if ($state->status !== MatchStatus::InProgress || array_sum($this->store->score($matchId)) > 0) {
            return true;
        }

        return match ($state->modality->key) {
            'dice' => $state->data['pending_roll'] !== null
                || $state->data['last_roll'] !== null
                || $this->hasMarks($state->data['boards']),
            'crazy' => $this->hasMarks($state->data['stacks']),
            'ultimate' => $state->data['target_board'] !== null
                || $this->hasMarks($state->data['boards']),
            default => $this->hasMarks($state->data['board']),
        };
    }

    public function discard(string $modality, string $matchId): void
    {
        $this->assertActive($modality, $matchId);

        match ($modality) {
            '3-basic' => app(ClassicMatchManager::class)->createNewActive(),
            '4x3-basic' => app(ClassicMatchManager::class)->createNewActive4x3(app(Classic4x3Rules::class)),
            '3-move' => app(MovementMatchManager::class)->createNewActive(),
            '4x3-move' => app(MovementMatchManager::class)->createNewActive4x3(app(Move4x3Rules::class)),
            '4x4-move' => app(MovementMatchManager::class)->createNewActive4x4(app(Move4x4Rules::class)),
            '3-rotation' => app(RotationMatchManager::class)->createNewActive3x3(),
            '4x3-rotation' => app(RotationMatchManager::class)->createNewActive4x3(),
            '4x3-block' => app(BlockMatchManager::class)->createNewActive(),
            '4x3-hidden' => app(HiddenMatchManager::class)->createNewActive(),
            '5x5-2-lines' => app(TwoLinesMatchManager::class)->createNewActive(),
            '5-full' => app(FullMatchManager::class)->createNewActive(),
            'dice' => app(DiceMatchManager::class)->createNewActive(),
            'crazy' => app(CrazyMatchManager::class)->createNewActive(),
            'ultimate' => app(UltimateMatchManager::class)->createNewActive(),
        };
    }

    /** @param array<mixed> $items */
    private function hasMarks(array $items): bool
    {
        foreach ($items as $item) {
            if (is_array($item) ? $this->hasMarks($item) : $item !== '') {
                return true;
            }
        }

        return false;
    }
}
