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
use Random\Randomizer;

class DiceRules implements GameRules
{
    /** @var list<array{int, int, int}> */
    private const array WINNING_LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6],
    ];

    public function __construct(private Randomizer $randomizer) {}

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, [
            'boards' => [array_fill(0, 9, ''), array_fill(0, 9, '')],
            'pending_roll' => null,
            'last_roll' => null,
        ]);
    }

    public function validate(MatchState $state, GameAction $action): ActionValidation
    {
        $this->assertModality($state->modality);
        if ($state->status === MatchStatus::Awaiting) {
            return ActionValidation::invalid('game_not_started');
        }
        if ($state->status !== MatchStatus::InProgress) {
            return ActionValidation::invalid('game_finished');
        }
        if (! in_array($action->type, ['roll', 'place'], true)) {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }

        $position = $this->position($state);
        if ($action->type === 'roll') {
            return $position['pending_roll'] === null
                ? ActionValidation::valid()
                : ActionValidation::invalid('roll_pending');
        }
        if ($position['pending_roll'] === null) {
            return ActionValidation::invalid('roll_required');
        }
        $board = $action->payload['board'] ?? null;
        $cell = $action->payload['cell'] ?? null;
        if (! is_int($board) || $board < 0 || $board > 1) {
            return ActionValidation::invalid('invalid_board');
        }
        if (! is_int($cell) || $cell < 0 || $cell > 8) {
            return ActionValidation::invalid('invalid_cell');
        }
        $roll = $position['pending_roll'];
        if ($board !== intdiv($roll - 1, 3) || $cell % 3 !== ($roll - 1) % 3) {
            return ActionValidation::invalid('wrong_column');
        }

        return $position['boards'][$board][$cell] === ''
            ? ActionValidation::valid()
            : ActionValidation::invalid('cell_occupied');
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);
        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }

        $position = $this->position($state);
        if ($action->type === 'roll') {
            $roll = $this->randomizer->getInt(1, 6);
            $board = intdiv($roll - 1, 3);
            $column = ($roll - 1) % 3;
            $columnHasSpace = false;
            for ($row = 0; $row < 3; $row++) {
                if ($position['boards'][$board][$row * 3 + $column] === '') {
                    $columnHasSpace = true;
                    break;
                }
            }
            $position['last_roll'] = $roll;
            $position['pending_roll'] = $columnHasSpace ? $roll : null;

            return ActionResult::accepted(new MatchState(
                $state->modality,
                $state->participants,
                MatchStatus::InProgress,
                $columnHasSpace ? $action->player : $this->opponent($action->player),
                $position,
            ));
        }

        $board = $action->payload['board'];
        $cell = $action->payload['cell'];
        $position['boards'][$board][$cell] = $action->player->value;
        $position['pending_roll'] = null;

        if ($this->matchingLine($position['boards'][$board], $action->player) !== null) {
            return ActionResult::accepted(new MatchState(
                $state->modality, $state->participants, MatchStatus::Completed, null, $position, $action->player,
            ));
        }
        if (! in_array('', $position['boards'][0], true) && ! in_array('', $position['boards'][1], true)) {
            return ActionResult::accepted(new MatchState(
                $state->modality, $state->participants, MatchStatus::Drawn, null, $position,
            ));
        }

        return ActionResult::accepted(new MatchState(
            $state->modality, $state->participants, MatchStatus::InProgress, $this->opponent($action->player), $position,
        ));
    }

    /** @return array{board: int, cells: array{int, int, int}}|null */
    public function winningLine(MatchState $state): ?array
    {
        $this->assertModality($state->modality);
        if ($state->status !== MatchStatus::Completed || $state->winner === null) {
            return null;
        }

        return $this->lineForPlayer($state, $state->winner);
    }

    /** @return array{board: int, cells: array{int, int, int}}|null */
    public function lineForPlayer(MatchState $state, PlayerMark $player): ?array
    {
        $this->assertModality($state->modality);
        $boards = $this->position($state)['boards'];
        foreach ($boards as $boardIndex => $board) {
            $line = $this->matchingLine($board, $player);
            if ($line !== null) {
                return ['board' => $boardIndex, 'cells' => $line];
            }
        }

        return null;
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== 'dice') {
            throw new InvalidArgumentException('Dice rules require the dice modality.');
        }
    }

    /** @return array{boards: array{list<string>, list<string>}, pending_roll: ?int, last_roll: ?int} */
    private function position(MatchState $state): array
    {
        $boards = $state->data['boards'] ?? null;
        $pendingRoll = $state->data['pending_roll'] ?? null;
        $lastRoll = $state->data['last_roll'] ?? null;
        if (! is_array($boards) || ! array_is_list($boards) || count($boards) !== 2
            || ($pendingRoll !== null && (! is_int($pendingRoll) || $pendingRoll < 1 || $pendingRoll > 6))
            || ($lastRoll !== null && (! is_int($lastRoll) || $lastRoll < 1 || $lastRoll > 6))
            || ($pendingRoll !== null && $pendingRoll !== $lastRoll)) {
            throw new InvalidArgumentException('Invalid dice state.');
        }
        foreach ($boards as $board) {
            if (! is_array($board) || ! array_is_list($board) || count($board) !== 9) {
                throw new InvalidArgumentException('Invalid dice board.');
            }
            foreach ($board as $cell) {
                if (! in_array($cell, ['', 'x', 'o'], true)) {
                    throw new InvalidArgumentException('Invalid dice board.');
                }
            }
        }

        return ['boards' => $boards, 'pending_roll' => $pendingRoll, 'last_roll' => $lastRoll];
    }

    /** @param list<string> $board
     * @return array{int, int, int}|null
     */
    private function matchingLine(array $board, PlayerMark $player): ?array
    {
        foreach (self::WINNING_LINES as [$first, $second, $third]) {
            if ($board[$first] === $player->value && $board[$second] === $player->value && $board[$third] === $player->value) {
                return [$first, $second, $third];
            }
        }

        return null;
    }

    private function opponent(PlayerMark $player): PlayerMark
    {
        return $player === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
    }
}
