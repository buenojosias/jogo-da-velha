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

class Move3x3Rules implements GameRules
{
    private const int UNRESTRICTED_MOVES_PER_PLAYER = 3;

    /**
     * @var list<array{int, int, int}>
     */
    private const array WINNING_LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6],
    ];

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState(
            $modality,
            $participants,
            MatchStatus::InProgress,
            $startingPlayer,
            [
                'board' => array_fill(0, 9, ''),
                'pieces' => ['x' => [], 'o' => []],
                'move_count' => ['x' => 0, 'o' => 0],
                'last_moved_piece' => ['x' => null, 'o' => null],
            ],
        );
    }

    public function assertState(MatchState $state): void
    {
        $this->assertModality($state->modality);
        $data = $state->data;

        if (array_keys($data) !== ['board', 'pieces', 'move_count', 'last_moved_piece']) {
            throw new InvalidArgumentException('Invalid movement 3x3 state.');
        }

        $board = $this->board($state);
        $expectedBoard = array_fill(0, 9, '');

        foreach (['x', 'o'] as $mark) {
            if (! is_array($data['pieces'] ?? null) || array_keys($data['pieces']) !== ['x', 'o']
                || ! is_array($data['move_count'] ?? null) || array_keys($data['move_count']) !== ['x', 'o']
                || ! is_array($data['last_moved_piece'] ?? null) || array_keys($data['last_moved_piece']) !== ['x', 'o']) {
                throw new InvalidArgumentException('Invalid movement 3x3 state.');
            }

            $pieces = $data['pieces'][$mark];
            $count = $data['move_count'][$mark];
            $last = $data['last_moved_piece'][$mark];

            if (! is_array($pieces) || ! array_is_list($pieces) || count($pieces) > 3
                || ! is_int($count) || $count < 0
                || ($count === 0 && $last !== null)
                || ($count > 0 && (! is_int($last) || ! array_key_exists($last, $pieces)))) {
                throw new InvalidArgumentException('Invalid movement 3x3 state.');
            }

            foreach ($pieces as $position) {
                if (! $this->validIndex($position) || $expectedBoard[$position] !== '') {
                    throw new InvalidArgumentException('Invalid movement 3x3 piece position.');
                }

                $expectedBoard[$position] = $mark;
            }
        }

        if ($expectedBoard !== $board
            || ! in_array($state->status, [MatchStatus::InProgress, MatchStatus::Completed], true)
            || ($state->status === MatchStatus::Completed
                && $this->matchingWinningLine($board, $state->winner) === null)
            || ($state->status === MatchStatus::InProgress
                && ($this->matchingWinningLine($board, PlayerMark::X) !== null
                    || $this->matchingWinningLine($board, PlayerMark::O) !== null))
            || (count(array_filter($board)) < 6 && ($data['move_count']['x'] !== 0 || $data['move_count']['o'] !== 0))
            || abs($data['move_count']['x'] - $data['move_count']['o']) > 1
            || ($state->status === MatchStatus::InProgress
                && $data['move_count']['x'] !== $data['move_count']['o']
                && $data['move_count'][$state->currentPlayer->value] > $data['move_count'][$this->otherPlayer($state->currentPlayer)->value])) {
            throw new InvalidArgumentException('Invalid movement 3x3 state.');
        }
    }

    public function blockedCell(MatchState $state): ?int
    {
        $this->assertState($state);

        if ($state->isFinished() || count(array_filter($state->data['board'])) < 6) {
            return null;
        }

        $mark = $state->currentPlayer->value;

        return $state->data['move_count'][$mark] >= self::UNRESTRICTED_MOVES_PER_PLAYER
            ? $state->data['pieces'][$mark][$state->data['last_moved_piece'][$mark]]
            : null;
    }

    public function validate(MatchState $state, GameAction $action): ActionValidation
    {
        $this->assertState($state);

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
            if ($placedCount === 6) {
                return ActionValidation::invalid('placement_complete');
            }

            if (count(array_keys($board, $action->player->value, true)) === 3) {
                return ActionValidation::invalid('piece_limit_reached');
            }

            $cellIndex = $action->payload['cell'] ?? null;

            if (! $this->validIndex($cellIndex)) {
                return ActionValidation::invalid('invalid_cell');
            }

            if ($board[$cellIndex] !== '') {
                return ActionValidation::invalid('cell_occupied');
            }

            return ActionValidation::valid();
        }

        if ($placedCount < 6) {
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

        $mark = $action->player->value;
        $pieceId = array_search($from, $state->data['pieces'][$mark], true);

        if ($state->data['move_count'][$mark] >= self::UNRESTRICTED_MOVES_PER_PLAYER
            && $pieceId === $state->data['last_moved_piece'][$mark]) {
            return ActionValidation::invalid('same_piece_twice');
        }

        if ($board[$to] !== '') {
            return ActionValidation::invalid('cell_occupied');
        }

        return ActionValidation::valid();
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);

        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }

        $data = $state->data;
        $board = $data['board'];

        if ($action->type === 'place') {
            $board[$action->payload['cell']] = $action->player->value;
            $data['pieces'][$action->player->value][] = $action->payload['cell'];
        } else {
            $from = $action->payload['from'];
            $mark = $action->player->value;
            $pieceId = array_search($from, $data['pieces'][$mark], true);
            $board[$from] = '';
            $board[$action->payload['to']] = $action->player->value;
            $data['pieces'][$mark][$pieceId] = $action->payload['to'];
            $data['move_count'][$mark]++;
            $data['last_moved_piece'][$mark] = $pieceId;
        }

        $data['board'] = $board;

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

        return ActionResult::accepted(new MatchState(
            $state->modality,
            $state->participants,
            MatchStatus::InProgress,
            $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X,
            $data,
        ));
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
        if ($modality->key !== '3-move') {
            throw new InvalidArgumentException('Movement 3x3 rules require the 3-move modality.');
        }
    }

    /**
     * @return list<string>
     */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;

        if (! is_array($board) || ! array_is_list($board) || count($board) !== 9) {
            throw new InvalidArgumentException('Invalid movement 3x3 board state.');
        }

        foreach ($board as $cell) {
            if (! in_array($cell, ['', PlayerMark::X->value, PlayerMark::O->value], true)) {
                throw new InvalidArgumentException('Invalid movement 3x3 board state.');
            }
        }

        $xCount = count(array_keys($board, PlayerMark::X->value, true));
        $oCount = count(array_keys($board, PlayerMark::O->value, true));

        if ($xCount > 3 || $oCount > 3 || abs($xCount - $oCount) > 1) {
            throw new InvalidArgumentException('Invalid movement 3x3 board state.');
        }

        return $board;
    }

    private function validIndex(mixed $index): bool
    {
        return is_int($index) && $index >= 0 && $index <= 8;
    }

    private function otherPlayer(PlayerMark $player): PlayerMark
    {
        return $player === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
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
