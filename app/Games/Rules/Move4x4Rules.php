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

class Move4x4Rules implements GameRules
{
    /** @var list<array{int, int, int, int}> */
    private const array WINNING_LINES = [
        [0, 1, 2, 3], [4, 5, 6, 7], [8, 9, 10, 11], [12, 13, 14, 15],
        [0, 4, 8, 12], [1, 5, 9, 13], [2, 6, 10, 14], [3, 7, 11, 15],
        [0, 5, 10, 15], [3, 6, 9, 12],
    ];

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, ['board' => array_fill(0, 16, '')]);
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
        if (! in_array($action->type, ['place', 'move'], true)) {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }

        $board = $this->board($state);
        $placedCount = count(array_filter($board));

        if ($action->type === 'place') {
            if ($placedCount === 8) {
                return ActionValidation::invalid('placement_complete');
            }
            if (count(array_keys($board, $action->player->value, true)) === 4) {
                return ActionValidation::invalid('piece_limit_reached');
            }
            $cell = $action->payload['cell'] ?? null;
            if (! $this->validIndex($cell)) {
                return ActionValidation::invalid('invalid_cell');
            }

            return $board[$cell] === '' ? ActionValidation::valid() : ActionValidation::invalid('cell_occupied');
        }

        if ($placedCount < 8) {
            return ActionValidation::invalid('movement_not_started');
        }
        $from = $action->payload['from'] ?? null;
        $to = $action->payload['to'] ?? null;
        if (! $this->validIndex($from) || ! $this->validIndex($to)) {
            return ActionValidation::invalid('invalid_cell');
        }
        if ($from === $to) {
            return ActionValidation::invalid('same_cell');
        }
        if ($board[$from] === '') {
            return ActionValidation::invalid('empty_source');
        }
        if ($board[$from] !== $action->player->value) {
            return ActionValidation::invalid('not_your_piece');
        }

        return $board[$to] === '' ? ActionValidation::valid() : ActionValidation::invalid('cell_occupied');
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);
        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }

        $board = $this->board($state);
        if ($action->type === 'place') {
            $board[$action->payload['cell']] = $action->player->value;
        } else {
            $board[$action->payload['from']] = '';
            $board[$action->payload['to']] = $action->player->value;
        }

        if ($this->matchingWinningLine($board, $action->player) !== null) {
            return ActionResult::accepted(new MatchState($state->modality, $state->participants, MatchStatus::Completed, null, ['board' => $board], $action->player));
        }

        return ActionResult::accepted(new MatchState(
            $state->modality, $state->participants, MatchStatus::InProgress,
            $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X, ['board' => $board],
        ));
    }

    /** @return array{int, int, int, int}|null */
    public function winningLine(MatchState $state): ?array
    {
        $this->assertModality($state->modality);
        if ($state->status !== MatchStatus::Completed || $state->winner === null) {
            return null;
        }

        return $this->matchingWinningLine($this->board($state), $state->winner);
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== '4x4-move') {
            throw new InvalidArgumentException('Movement 4x4 rules require the 4x4-move modality.');
        }
    }

    /** @return list<string> */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;
        if (! is_array($board) || ! array_is_list($board) || count($board) !== 16) {
            throw new InvalidArgumentException('Invalid movement 4x4 board state.');
        }
        foreach ($board as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new InvalidArgumentException('Invalid movement 4x4 board state.');
            }
        }
        $xCount = count(array_keys($board, 'x', true));
        $oCount = count(array_keys($board, 'o', true));
        if ($xCount > 4 || $oCount > 4 || abs($xCount - $oCount) > 1) {
            throw new InvalidArgumentException('Invalid movement 4x4 board state.');
        }

        return $board;
    }

    private function validIndex(mixed $index): bool
    {
        return is_int($index) && $index >= 0 && $index < 16;
    }

    /**
     * @param  list<string>  $board
     * @return array{int, int, int, int}|null
     */
    private function matchingWinningLine(array $board, PlayerMark $player): ?array
    {
        foreach (self::WINNING_LINES as $line) {
            if (count(array_filter($line, fn (int $cell): bool => $board[$cell] === $player->value)) === 4) {
                return $line;
            }
        }

        return null;
    }
}
