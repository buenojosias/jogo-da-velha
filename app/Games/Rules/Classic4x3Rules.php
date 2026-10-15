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

class Classic4x3Rules implements GameRules
{
    public const int STATE_VERSION = 2;

    /**
     * @var list<array{int, int, int}>
     */
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

        return new MatchState(
            $modality,
            $participants,
            MatchStatus::InProgress,
            $startingPlayer,
            ['board' => array_fill(0, 16, ''), 'version' => self::STATE_VERSION, 'last_move' => null],
        );
    }

    public function validate(MatchState $state, GameAction $action): ActionValidation
    {
        $this->assertModality($state->modality);
        $this->assertState($state);

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

        $cellIndex = $action->payload['cell'] ?? null;

        if (! is_int($cellIndex) || $cellIndex < 0 || $cellIndex > 15) {
            return ActionValidation::invalid('invalid_cell');
        }

        if ($this->board($state)[$cellIndex] !== '') {
            return ActionValidation::invalid('cell_occupied');
        }

        if (! in_array($cellIndex, $this->availableCells($state), true)) {
            return ActionValidation::invalid('not_adjacent');
        }

        return ActionValidation::valid();
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);

        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }

        $board = $this->board($state);
        $board[$action->payload['cell']] = $action->player->value;
        $data = ['board' => $board, 'version' => self::STATE_VERSION, 'last_move' => $action->payload['cell']];

        if ($this->matchingWinningLine($board, $action->player) !== null) {
            return ActionResult::accepted(new MatchState(
                $state->modality,
                $state->participants,
                MatchStatus::Completed,
                null,
                $data,
                $action->player,
            ));
        }

        if (! in_array('', $board, true)) {
            return ActionResult::accepted(new MatchState(
                $state->modality,
                $state->participants,
                MatchStatus::Drawn,
                null,
                $data,
            ));
        }

        $nextPlayer = $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X;

        return ActionResult::accepted(new MatchState(
            $state->modality,
            $state->participants,
            MatchStatus::InProgress,
            $nextPlayer,
            $data,
        ));
    }

    /** @return list<int> */
    public function availableCells(MatchState $state): array
    {
        $this->assertState($state);

        if ($state->status !== MatchStatus::InProgress) {
            return [];
        }

        $board = $this->board($state);
        $emptyCells = array_keys($board, '', true);

        if ($this->moveMode($state) === 'first') {
            return $emptyCells;
        }

        $lastMove = $state->data['last_move'];
        $row = intdiv($lastMove, 4);
        $column = $lastMove % 4;
        $neighbors = [];

        for ($rowOffset = -1; $rowOffset <= 1; $rowOffset++) {
            for ($columnOffset = -1; $columnOffset <= 1; $columnOffset++) {
                $neighborRow = $row + $rowOffset;
                $neighborColumn = $column + $columnOffset;

                if (($rowOffset !== 0 || $columnOffset !== 0)
                    && $neighborRow >= 0 && $neighborRow < 4
                    && $neighborColumn >= 0 && $neighborColumn < 4) {
                    $neighbor = $neighborRow * 4 + $neighborColumn;
                    if ($board[$neighbor] === '') {
                        $neighbors[] = $neighbor;
                    }
                }
            }
        }

        return $neighbors === [] ? $emptyCells : $neighbors;
    }

    public function moveMode(MatchState $state): string
    {
        $this->assertState($state);

        if ($state->status !== MatchStatus::InProgress) {
            return 'finished';
        }

        return in_array($state->currentPlayer->value, $this->board($state), true) ? 'proximity' : 'first';
    }

    public function assertState(MatchState $state): void
    {
        $this->assertModality($state->modality);
        $board = $this->board($state);
        $lastMove = $state->data['last_move'] ?? null;

        if (($state->data['version'] ?? null) !== self::STATE_VERSION
            || ! array_key_exists('last_move', $state->data)
            || ($lastMove !== null && (! is_int($lastMove) || $lastMove < 0 || $lastMove > 15))
            || ($lastMove === null) !== (count(array_filter($board)) === 0)
            || ($lastMove !== null && $board[$lastMove] === '')
            || abs(count(array_keys($board, 'x', true)) - count(array_keys($board, 'o', true))) > 1) {
            throw new InvalidArgumentException('Invalid classic 4x3 proximity state.');
        }

        if ($state->status === MatchStatus::InProgress && $lastMove !== null
            && $board[$lastMove] === $state->currentPlayer->value) {
            throw new InvalidArgumentException('Invalid classic 4x3 last move.');
        }

        if (($state->status === MatchStatus::Completed
                && ($lastMove === null || $board[$lastMove] !== $state->winner->value
                    || $this->matchingWinningLine($board, $state->winner) === null))
            || ($state->status === MatchStatus::Drawn && in_array('', $board, true))
            || ($state->status === MatchStatus::InProgress && ! in_array('', $board, true))) {
            throw new InvalidArgumentException('Invalid classic 4x3 round outcome.');
        }
    }

    /**
     * @return array{int, int, int}|null
     */
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
        if ($modality->key !== '4x3-basic') {
            throw new InvalidArgumentException('Classic 4x3 rules require the 4x3-basic modality.');
        }
    }

    /**
     * @return list<string>
     */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;

        if (! is_array($board) || ! array_is_list($board) || count($board) !== 16) {
            throw new InvalidArgumentException('Invalid classic 4x3 board state.');
        }

        foreach ($board as $cell) {
            if (! in_array($cell, ['', PlayerMark::X->value, PlayerMark::O->value], true)) {
                throw new InvalidArgumentException('Invalid classic 4x3 board state.');
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
        foreach (self::WINNING_LINES as [$first, $second, $third]) {
            if ($board[$first] === $player->value
                && $board[$second] === $player->value
                && $board[$third] === $player->value) {
                return [$first, $second, $third];
            }
        }

        return null;
    }
}
