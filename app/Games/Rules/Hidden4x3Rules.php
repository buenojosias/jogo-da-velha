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

class Hidden4x3Rules implements GameRules
{
    private const array WINNING_LINES = [
        [0, 1, 2], [1, 2, 3], [4, 5, 6], [5, 6, 7],
        [8, 9, 10], [9, 10, 11], [12, 13, 14], [13, 14, 15],
        [0, 4, 8], [4, 8, 12], [1, 5, 9], [5, 9, 13],
        [2, 6, 10], [6, 10, 14], [3, 7, 11], [7, 11, 15],
        [0, 5, 10], [1, 6, 11], [4, 9, 14], [5, 10, 15],
        [2, 5, 8], [3, 6, 9],
        [6, 9, 12], [7, 10, 13],
    ];

    public function __construct(private Randomizer $randomizer) {}

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, [
            'board' => array_fill(0, 16, ''),
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
        if ($action->type !== 'choose') {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }
        $cell = $action->payload['cell'] ?? null;
        if (! is_int($cell) || $cell < 0 || $cell >= 16) {
            return ActionValidation::invalid('invalid_cell');
        }
        $board = $this->position($state)['board'];
        if ($board[$cell] === $action->player->value && ! in_array('', $board, true)) {
            return ActionValidation::invalid('no_empty_cell');
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
        $cell = $action->payload['cell'];
        $wasOpponent = $board[$cell] !== '' && $board[$cell] !== $mark;
        if ($board[$cell] === '') {
            if (count($order[$mark]) === 5) {
                $board[array_shift($order[$mark])] = '';
            }
            $board[$cell] = $mark;
            $order[$mark][] = $cell;
        } elseif (! $wasOpponent) {
            $emptyCells = array_keys($board, '', true);
            $destination = $emptyCells[$this->randomizer->getInt(0, count($emptyCells) - 1)];
            $board[$cell] = '';
            $board[$destination] = $mark;
            $order[$mark] = array_values(array_filter($order[$mark], fn (int $index): bool => $index !== $cell));
            $order[$mark][] = $destination;
        }

        $data = ['board' => $board, 'order' => $order];
        if (! $wasOpponent && $this->matchingLine($board, $action->player) !== null) {
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

        return $state->status === MatchStatus::Completed && $state->winner !== null
            ? $this->matchingLine($this->position($state)['board'], $state->winner) : null;
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== '4x3-hidden') {
            throw new InvalidArgumentException('Hidden rules require the 4x3-hidden modality.');
        }
    }

    /** @return array{board: list<string>, order: array{x: list<int>, o: list<int>}} */
    private function position(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;
        $order = $state->data['order'] ?? null;
        if (! is_array($board) || ! array_is_list($board) || count($board) !== 16
            || ! is_array($order) || array_keys($order) !== ['x', 'o']) {
            throw new InvalidArgumentException('Invalid hidden state.');
        }
        foreach ($board as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new InvalidArgumentException('Invalid hidden board.');
            }
        }
        foreach (['x', 'o'] as $mark) {
            $indices = $order[$mark];
            if (! is_array($indices) || ! array_is_list($indices) || count($indices) > 5
                || count($indices) !== count(array_keys($board, $mark, true))
                || count(array_unique($indices)) !== count($indices)) {
                throw new InvalidArgumentException('Invalid hidden order.');
            }
            foreach ($indices as $index) {
                if (! is_int($index) || ! array_key_exists($index, $board) || $board[$index] !== $mark) {
                    throw new InvalidArgumentException('Invalid hidden order.');
                }
            }
        }
        if (abs(count($order['x']) - count($order['o'])) > 1) {
            throw new InvalidArgumentException('Invalid hidden order.');
        }

        return ['board' => $board, 'order' => $order];
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
}
