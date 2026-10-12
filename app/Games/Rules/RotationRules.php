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

abstract class RotationRules implements GameRules
{
    abstract protected function modalityKey(): string;

    abstract protected function boardSize(): int;

    /** @return list<array{int, int, int}> */
    abstract protected function winningLines(): array;

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, [
            'board' => array_fill(0, $this->boardSize(), ''),
            'order' => ['x' => [], 'o' => []],
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
        if ($action->type !== 'place') {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }

        $cell = $action->payload['cell'] ?? null;
        if (! is_int($cell) || $cell < 0 || $cell >= $this->boardSize()) {
            return ActionValidation::invalid('invalid_cell');
        }

        if ($this->position($state)['board'][$cell] !== '') {
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

        ['board' => $board, 'order' => $order] = $this->position($state);
        $mark = $action->player->value;
        if (count($order[$mark]) === 3) {
            $board[array_shift($order[$mark])] = '';
        }

        $cell = $action->payload['cell'];
        $board[$cell] = $mark;
        $order[$mark][] = $cell;
        $data = ['board' => $board, 'order' => $order];

        if ($this->matchingLine($board, $action->player) !== null) {
            return ActionResult::accepted(new MatchState(
                $state->modality, $state->participants, MatchStatus::Completed, null, $data, $action->player,
            ));
        }

        return ActionResult::accepted(new MatchState(
            $state->modality, $state->participants, MatchStatus::InProgress,
            $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X, $data,
        ));
    }

    /** @return array{int, int, int}|null */
    public function winningLine(MatchState $state): ?array
    {
        $this->assertModality($state->modality);
        $board = $this->position($state)['board'];

        return $state->status === MatchStatus::Completed && $state->winner !== null
            ? $this->matchingLine($board, $state->winner)
            : null;
    }

    /** @return array{x: ?int, o: ?int} */
    public function oldestPieces(MatchState $state): array
    {
        $this->assertModality($state->modality);
        $order = $this->position($state)['order'];

        return [
            'x' => count($order['x']) === 3 ? $order['x'][0] : null,
            'o' => count($order['o']) === 3 ? $order['o'][0] : null,
        ];
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== $this->modalityKey()) {
            throw new InvalidArgumentException('Invalid rotation modality.');
        }
    }

    /** @return array{board: list<string>, order: array{x: list<int>, o: list<int>}} */
    private function position(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;
        $order = $state->data['order'] ?? null;
        if (! is_array($board) || ! array_is_list($board) || count($board) !== $this->boardSize()
            || ! is_array($order) || array_keys($order) !== ['x', 'o']) {
            throw new InvalidArgumentException('Invalid rotation state.');
        }
        foreach ($board as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new InvalidArgumentException('Invalid rotation board.');
            }
        }
        foreach (['x', 'o'] as $mark) {
            $indices = $order[$mark];
            if (! is_array($indices) || ! array_is_list($indices) || count($indices) > 3
                || count($indices) !== count(array_keys($board, $mark, true))
                || count(array_unique($indices)) !== count($indices)) {
                throw new InvalidArgumentException('Invalid rotation order.');
            }
            foreach ($indices as $index) {
                if (! is_int($index) || $index < 0 || $index >= $this->boardSize() || $board[$index] !== $mark) {
                    throw new InvalidArgumentException('Invalid rotation order.');
                }
            }
        }
        if (abs(count($order['x']) - count($order['o'])) > 1) {
            throw new InvalidArgumentException('Invalid rotation order.');
        }

        return ['board' => $board, 'order' => $order];
    }

    /** @param list<string> $board
     * @return array{int, int, int}|null
     */
    private function matchingLine(array $board, PlayerMark $player): ?array
    {
        foreach ($this->winningLines() as [$first, $second, $third]) {
            if ($board[$first] === $player->value && $board[$second] === $player->value && $board[$third] === $player->value) {
                return [$first, $second, $third];
            }
        }

        return null;
    }
}
