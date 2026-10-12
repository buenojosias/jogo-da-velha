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

class Full5x5Rules implements GameRules
{
    /** @var list<array{int, int}> */
    private const array DIRECTIONS = [[0, 1], [1, 0], [1, 1], [1, -1]];

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

        if (! in_array('', $board, true)) {
            $points = $this->scoresForBoard($board);
            $winner = $points['x'] === $points['o']
                ? null
                : ($points['x'] > $points['o'] ? PlayerMark::X : PlayerMark::O);

            return ActionResult::accepted(new MatchState(
                $state->modality,
                $state->participants,
                $winner === null ? MatchStatus::Drawn : MatchStatus::Completed,
                null,
                $data,
                $winner,
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

    /** @return array{x: int, o: int} */
    public function scores(MatchState $state): array
    {
        $this->assertModality($state->modality);

        return $this->scoresForBoard($this->board($state));
    }

    /** @return list<list<int>> */
    public function scoringSequences(MatchState $state, PlayerMark $player): array
    {
        $this->assertModality($state->modality);

        return $this->sequencesForBoard($this->board($state), $player);
    }

    /** @return array{status: MatchStatus, winner: ?PlayerMark, points: array{x: int, o: int}}|null */
    public function finalResult(MatchState $state): ?array
    {
        if (! $state->isFinished()) {
            return null;
        }

        return ['status' => $state->status, 'winner' => $state->winner, 'points' => $this->scores($state)];
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== '5-full') {
            throw new InvalidArgumentException('Full-board rules require the 5-full modality.');
        }
    }

    /** @return list<string> */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;
        if (! is_array($board) || ! array_is_list($board) || count($board) !== 25) {
            throw new InvalidArgumentException('Invalid full-board state.');
        }
        foreach ($board as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new InvalidArgumentException('Invalid full-board state.');
            }
        }

        return $board;
    }

    /** @param list<string> $board
     * @return array{x: int, o: int}
     */
    private function scoresForBoard(array $board): array
    {
        $scores = ['x' => 0, 'o' => 0];
        foreach (PlayerMark::cases() as $player) {
            foreach ($this->sequencesForBoard($board, $player) as $sequence) {
                $scores[$player->value] += match (count($sequence)) {
                    3 => 3,
                    4 => 6,
                    default => 10,
                };
            }
        }

        return $scores;
    }

    /** @param list<string> $board
     * @return list<list<int>>
     */
    private function sequencesForBoard(array $board, PlayerMark $player): array
    {
        $sequences = [];
        for ($row = 0; $row < 5; $row++) {
            for ($column = 0; $column < 5; $column++) {
                if ($board[$row * 5 + $column] !== $player->value) {
                    continue;
                }
                foreach (self::DIRECTIONS as [$rowStep, $columnStep]) {
                    $previousRow = $row - $rowStep;
                    $previousColumn = $column - $columnStep;
                    if ($this->inside($previousRow, $previousColumn)
                        && $board[$previousRow * 5 + $previousColumn] === $player->value) {
                        continue;
                    }

                    $cells = [];
                    $nextRow = $row;
                    $nextColumn = $column;
                    while ($this->inside($nextRow, $nextColumn)
                        && $board[$nextRow * 5 + $nextColumn] === $player->value) {
                        $cells[] = $nextRow * 5 + $nextColumn;
                        $nextRow += $rowStep;
                        $nextColumn += $columnStep;
                    }
                    if (count($cells) >= 3) {
                        $sequences[] = $cells;
                    }
                }
            }
        }

        return $sequences;
    }

    private function inside(int $row, int $column): bool
    {
        return $row >= 0 && $row < 5 && $column >= 0 && $column < 5;
    }
}
