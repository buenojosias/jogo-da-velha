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

class Block4x3Rules implements GameRules
{
    /** @var list<array{int, int, int}> */
    private const array WINNING_LINES = [
        [0, 1, 2], [1, 2, 3], [4, 5, 6], [5, 6, 7],
        [8, 9, 10], [9, 10, 11], [12, 13, 14], [13, 14, 15],
        [0, 4, 8], [4, 8, 12], [1, 5, 9], [5, 9, 13],
        [2, 6, 10], [6, 10, 14], [3, 7, 11], [7, 11, 15],
        [0, 5, 10], [1, 6, 11], [4, 9, 14], [5, 10, 15],
        [2, 5, 8], [3, 6, 9], [6, 9, 12], [7, 10, 13],
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
        if ($action->type !== 'place') {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }

        $cell = $action->payload['cell'] ?? null;
        if (! is_int($cell) || $cell < 0 || $cell >= 16) {
            return ActionValidation::invalid('invalid_cell');
        }

        return $this->board($state)[$cell] === '' ? ActionValidation::valid() : ActionValidation::invalid('cell_occupied');
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);
        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }

        $board = $this->board($state);
        $board[$action->payload['cell']] = $action->player->value;

        if ($this->matchingWinningLine($board, $action->player) !== null) {
            return ActionResult::accepted(new MatchState($state->modality, $state->participants, MatchStatus::Completed, null, ['board' => $board], $action->player));
        }
        if (! in_array('', $board, true)) {
            return ActionResult::accepted(new MatchState($state->modality, $state->participants, MatchStatus::Drawn, null, ['board' => $board]));
        }

        return ActionResult::accepted(new MatchState(
            $state->modality, $state->participants, MatchStatus::InProgress,
            $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X, ['board' => $board],
        ));
    }

    /** @return array{int, int, int}|null */
    public function winningLine(MatchState $state): ?array
    {
        $this->assertModality($state->modality);
        if ($state->status !== MatchStatus::Completed || $state->winner === null) {
            return null;
        }

        return $this->matchingWinningLine($this->board($state), $state->winner);
    }

    /** @return list<int> */
    public function blockersForMove(MatchState $state, PlayerMark $player, int $cell): array
    {
        $this->assertModality($state->modality);
        if ($cell < 0 || $cell >= 16) {
            throw new InvalidArgumentException('Invalid blocking cell.');
        }

        $board = $this->board($state);
        $blockers = [];
        foreach (self::WINNING_LINES as $line) {
            if (in_array($cell, $line, true) && $this->lineBelongsTo($board, $line, $player)) {
                $blockers = array_merge($blockers, $this->blockers($board, $line, $player));
            }
        }

        return array_values(array_unique($blockers));
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== '4x3-block') {
            throw new InvalidArgumentException('Blocking rules require the 4x3-block modality.');
        }
    }

    /** @return list<string> */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;
        if (! is_array($board) || ! array_is_list($board) || count($board) !== 16) {
            throw new InvalidArgumentException('Invalid blocking board state.');
        }
        foreach ($board as $mark) {
            if (! in_array($mark, ['', 'x', 'o'], true)) {
                throw new InvalidArgumentException('Invalid blocking board state.');
            }
        }

        return $board;
    }

    /**
     * @param  list<string>  $board
     * @return array{int, int, int}|null
     */
    private function matchingWinningLine(array $board, PlayerMark $player): ?array
    {
        foreach (self::WINNING_LINES as $line) {
            if ($this->lineBelongsTo($board, $line, $player) && $this->blockers($board, $line, $player) === []) {
                return $line;
            }
        }

        return null;
    }

    /** @param  list<string>  $board
     * @param  array{int, int, int}  $line
     */
    private function lineBelongsTo(array $board, array $line, PlayerMark $player): bool
    {
        return $board[$line[0]] === $player->value && $board[$line[1]] === $player->value && $board[$line[2]] === $player->value;
    }

    /**
     * @param  list<string>  $board
     * @param  array{int, int, int}  $line
     * @return list<int>
     */
    private function blockers(array $board, array $line, PlayerMark $player): array
    {
        [$first, $second, $third] = $line;
        $row = intdiv($first, 4);
        $column = $first % 4;
        $rowStep = intdiv($second, 4) - $row;
        $columnStep = ($second % 4) - $column;
        $opponent = $player === PlayerMark::X ? PlayerMark::O->value : PlayerMark::X->value;
        $blockers = [];

        foreach ([[$row - $rowStep, $column - $columnStep], [intdiv($third, 4) + $rowStep, ($third % 4) + $columnStep]] as [$edgeRow, $edgeColumn]) {
            if ($edgeRow >= 0 && $edgeRow < 4 && $edgeColumn >= 0 && $edgeColumn < 4) {
                $index = $edgeRow * 4 + $edgeColumn;
                if ($board[$index] === $opponent) {
                    $blockers[] = $index;
                }
            }
        }

        return $blockers;
    }
}
