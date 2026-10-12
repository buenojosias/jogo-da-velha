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

class TwoLines5x5Rules implements GameRules
{
    /** @var array<string, array{int, int}> */
    private const array DIRECTIONS = [
        'horizontal' => [0, 1],
        'vertical' => [1, 0],
        'descending' => [1, 1],
        'ascending' => [1, -1],
    ];

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, [
            'board' => array_fill(0, 25, ''),
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
        if (! is_int($cell) || $cell < 0 || $cell >= 25) {
            return ActionValidation::invalid('invalid_cell');
        }

        return $this->board($state)[$cell] === ''
            ? ActionValidation::valid()
            : ActionValidation::invalid('cell_occupied');
    }

    public function apply(MatchState $state, GameAction $action): ActionResult
    {
        $validation = $this->validate($state, $action);
        if (! $validation->valid) {
            return ActionResult::rejected($state, $validation->reason);
        }

        $board = $this->board($state);
        $board[$action->payload['cell']] = $action->player->value;
        $data = ['board' => $board];
        if ($this->analyze($board, $action->player)['count'] >= 2) {
            return ActionResult::accepted(new MatchState(
                $state->modality, $state->participants, MatchStatus::Completed, null, $data, $action->player,
            ));
        }
        if (! in_array('', $board, true)) {
            return ActionResult::accepted(new MatchState(
                $state->modality, $state->participants, MatchStatus::Drawn, null, $data,
            ));
        }

        return ActionResult::accepted(new MatchState(
            $state->modality, $state->participants, MatchStatus::InProgress,
            $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X, $data,
        ));
    }

    public function lineCount(MatchState $state, PlayerMark $player): int
    {
        $this->assertModality($state->modality);

        return $this->analyze($this->board($state), $player)['count'];
    }

    /** @return list<int> */
    public function winningCells(MatchState $state): array
    {
        $this->assertModality($state->modality);
        if ($state->status !== MatchStatus::Completed || $state->winner === null) {
            return [];
        }

        return $this->analyze($this->board($state), $state->winner)['cells'];
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== '5x5-2-lines') {
            throw new InvalidArgumentException('Two-lines rules require the 5x5-2-lines modality.');
        }
    }

    /** @return list<string> */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;
        if (! is_array($board) || ! array_is_list($board) || count($board) !== 25) {
            throw new InvalidArgumentException('Invalid two-lines board.');
        }
        foreach ($board as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new InvalidArgumentException('Invalid two-lines board.');
            }
        }

        return $board;
    }

    /**
     * @param  list<string>  $board
     * @return array{count: int, cells: list<int>}
     */
    private function analyze(array $board, PlayerMark $player): array
    {
        $lineCount = 0;
        $winningCells = [];

        foreach (self::DIRECTIONS as [$rowStep, $columnStep]) {
            $windows = [];
            for ($row = 0; $row < 5; $row++) {
                for ($column = 0; $column < 5; $column++) {
                    $endRow = $row + 2 * $rowStep;
                    $endColumn = $column + 2 * $columnStep;
                    if ($endRow < 0 || $endRow >= 5 || $endColumn < 0 || $endColumn >= 5) {
                        continue;
                    }
                    $window = [];
                    for ($offset = 0; $offset < 3; $offset++) {
                        $window[] = ($row + $offset * $rowStep) * 5 + $column + $offset * $columnStep;
                    }
                    if ($board[$window[0]] === $player->value
                        && $board[$window[1]] === $player->value
                        && $board[$window[2]] === $player->value) {
                        $windows[] = $window;
                        $winningCells = array_merge($winningCells, $window);
                    }
                }
            }

            while ($windows !== []) {
                $lineCount++;
                $connected = [array_pop($windows)];
                for ($index = 0; $index < count($connected); $index++) {
                    foreach ($windows as $candidateIndex => $candidate) {
                        if (array_intersect($connected[$index], $candidate) !== []) {
                            $connected[] = $candidate;
                            unset($windows[$candidateIndex]);
                        }
                    }
                }
            }
        }

        $winningCells = array_values(array_unique($winningCells));
        sort($winningCells);

        return ['count' => $lineCount, 'cells' => $winningCells];
    }
}
