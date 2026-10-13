<?php

namespace App\Games\Rules;

use App\Games\ActionResult;
use App\Games\ActionValidation;
use App\Games\GameAction;
use App\Games\GameRules;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participants;
use App\Games\PlayerMark;
use InvalidArgumentException;

class CrazyRules implements GameRules
{
    private const array LINES = [[0, 1, 2], [3, 4, 5], [6, 7, 8], [0, 3, 6], [1, 4, 7], [2, 5, 8], [0, 4, 8], [2, 4, 6]];

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, ['stacks' => array_fill(0, 9, [])]);
    }

    public function validate(MatchState $state, GameAction $action): ActionValidation
    {
        $stacks = $this->stacks($state);
        if ($state->status !== MatchStatus::InProgress) {
            return ActionValidation::invalid('game_finished');
        }
        if ($action->type !== 'place') {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }
        $piece = $action->payload['piece'] ?? null;
        $cell = $action->payload['cell'] ?? null;
        if (! is_string($piece) || ! preg_match('/\A[xo]-[1-3]-[1-3]\z/D', $piece)) {
            return ActionValidation::invalid('invalid_piece');
        }
        if ($piece[0] !== $action->player->value) {
            return ActionValidation::invalid('not_your_piece');
        }
        if ($this->isUsed($stacks, $piece)) {
            return ActionValidation::invalid('piece_used');
        }
        if (! is_int($cell) || $cell < 0 || $cell > 8) {
            return ActionValidation::invalid('invalid_cell');
        }
        $top = $this->top($stacks[$cell]);
        if ($top !== null && $this->size($piece) <= $this->size($top)) {
            return ActionValidation::invalid('piece_not_larger');
        }

        return ActionValidation::valid();
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);
        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }
        $stacks = $state->data['stacks'];
        $stacks[$action->payload['cell']][] = $action->payload['piece'];
        $line = $this->definitiveLine($stacks, $action->player);
        $used = array_sum(array_map('count', $stacks));
        $status = $line !== null ? MatchStatus::Completed : ($used === 18 ? MatchStatus::Drawn : MatchStatus::InProgress);

        return ActionResult::accepted(new MatchState(
            $state->modality, $state->participants, $status,
            $status === MatchStatus::InProgress ? ($action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X) : null,
            ['stacks' => $stacks], $line !== null ? $action->player : null,
        ));
    }

    /** @return array{int, int, int}|null */
    public function winningLine(MatchState $state): ?array
    {
        return $state->winner === null ? null : $this->definitiveLine($this->stacks($state), $state->winner);
    }

    /** @return list<list<string>> */
    public function stacks(MatchState $state): array
    {
        $this->assertModality($state->modality);
        $stacks = $state->data['stacks'] ?? null;
        if (! is_array($stacks) || ! array_is_list($stacks) || count($stacks) !== 9) {
            throw new InvalidArgumentException('Invalid crazy stacks.');
        }
        $used = [];
        foreach ($stacks as $stack) {
            if (! is_array($stack) || ! array_is_list($stack) || count($stack) > 3) {
                throw new InvalidArgumentException('Invalid crazy stack.');
            }
            $lastSize = 0;
            foreach ($stack as $piece) {
                if (! is_string($piece) || ! preg_match('/\A[xo]-[1-3]-[1-3]\z/D', $piece)
                    || isset($used[$piece]) || $this->size($piece) <= $lastSize) {
                    throw new InvalidArgumentException('Invalid crazy piece.');
                }
                $used[$piece] = true;
                $lastSize = $this->size($piece);
            }
        }

        return $stacks;
    }

    /** @return list<string> */
    public function availablePieces(MatchState $state, PlayerMark $player): array
    {
        $stacks = $this->stacks($state);
        $available = [];
        for ($size = 1; $size <= 3; $size++) {
            for ($copy = 1; $copy <= 3; $copy++) {
                $piece = $player->value.'-'.$size.'-'.$copy;
                if (! $this->isUsed($stacks, $piece)) {
                    $available[] = $piece;
                }
            }
        }

        return $available;
    }

    /** @param list<list<string>> $stacks
     * @return array{int, int, int}|null
     */
    public function definitiveLine(array $stacks, PlayerMark $player): ?array
    {
        $opponent = $player === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
        $available = [];
        for ($size = 1; $size <= 3; $size++) {
            for ($copy = 1; $copy <= 3; $copy++) {
                $piece = $opponent->value.'-'.$size.'-'.$copy;
                if (! $this->isUsed($stacks, $piece)) {
                    $available[] = $piece;
                }
            }
        }
        foreach (self::LINES as $line) {
            $tops = array_map(fn (int $index): ?string => $this->top($stacks[$index]), $line);
            if (in_array(null, $tops, true) || count(array_filter($tops, fn (string $top): bool => $top[0] === $player->value)) !== 3) {
                continue;
            }
            $coverable = false;
            foreach ($tops as $top) {
                foreach ($available as $piece) {
                    if ($this->size($piece) > $this->size($top)) {
                        $coverable = true;
                        break 2;
                    }
                }
            }
            if (! $coverable) {
                return $line;
            }
        }

        return null;
    }

    /** @param list<list<string>> $stacks */
    private function isUsed(array $stacks, string $piece): bool
    {
        foreach ($stacks as $stack) {
            if (in_array($piece, $stack, true)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $stack */
    private function top(array $stack): ?string
    {
        return $stack === [] ? null : $stack[array_key_last($stack)];
    }

    private function size(string $piece): int
    {
        return (int) $piece[2];
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== 'crazy') {
            throw new InvalidArgumentException('Crazy rules require the crazy modality.');
        }
    }
}
