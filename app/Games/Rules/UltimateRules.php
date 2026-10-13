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

class UltimateRules implements GameRules
{
    /** @var list<array{int, int, int}> */
    private const array LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6],
    ];

    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState
    {
        $this->assertModality($modality);

        return new MatchState($modality, $participants, MatchStatus::InProgress, $startingPlayer, [
            'boards' => array_fill(0, 9, array_fill(0, 9, '')),
            'target_board' => null,
        ]);
    }

    public function validate(MatchState $state, GameAction $action): ActionValidation
    {
        $boards = $this->boards($state);
        $target = $this->targetBoard($state);

        if ($state->status !== MatchStatus::InProgress) {
            return ActionValidation::invalid('game_finished');
        }
        if ($action->type !== 'place') {
            return ActionValidation::invalid('unsupported_action');
        }
        if ($action->player !== $state->currentPlayer) {
            return ActionValidation::invalid('not_your_turn');
        }
        $board = $action->payload['board'] ?? null;
        $cell = $action->payload['cell'] ?? null;
        if (! is_int($board) || $board < 0 || $board > 8) {
            return ActionValidation::invalid('invalid_board');
        }
        if (! is_int($cell) || $cell < 0 || $cell > 8) {
            return ActionValidation::invalid('invalid_cell');
        }
        if ($this->resultFromCells($boards[$board]) !== null) {
            return ActionValidation::invalid('board_closed');
        }
        if ($target !== null && $board !== $target) {
            return ActionValidation::invalid('wrong_board');
        }
        if ($boards[$board][$cell] !== '') {
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

        $boards = $state->data['boards'];
        $boards[$action->payload['board']][$action->payload['cell']] = $action->player->value;
        $globalLine = $this->globalLineFromBoards($boards);
        $allClosed = count(array_filter($boards, fn (array $board): bool => $this->resultFromCells($board) !== null)) === 9;
        $status = $globalLine !== null ? MatchStatus::Completed : ($allClosed ? MatchStatus::Drawn : MatchStatus::InProgress);
        $target = $status === MatchStatus::InProgress && $this->resultFromCells($boards[$action->payload['cell']]) === null
            ? $action->payload['cell'] : null;

        return ActionResult::accepted(new MatchState(
            $state->modality,
            $state->participants,
            $status,
            $status === MatchStatus::InProgress ? ($action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X) : null,
            ['boards' => $boards, 'target_board' => $target],
            $globalLine !== null ? $action->player : null,
        ));
    }

    /** @return list<list<string>> */
    public function boards(MatchState $state): array
    {
        $this->assertModality($state->modality);
        $boards = $state->data['boards'] ?? null;
        if (! is_array($boards) || ! array_is_list($boards) || count($boards) !== 9) {
            throw new InvalidArgumentException('Invalid ultimate boards.');
        }
        foreach ($boards as $board) {
            if (! is_array($board) || ! array_is_list($board) || count($board) !== 9) {
                throw new InvalidArgumentException('Invalid ultimate sub-board.');
            }
            foreach ($board as $cell) {
                if (! in_array($cell, ['', 'x', 'o'], true)) {
                    throw new InvalidArgumentException('Invalid ultimate cell.');
                }
            }
        }

        return $boards;
    }

    public function targetBoard(MatchState $state): ?int
    {
        $boards = $this->boards($state);
        if (! array_key_exists('target_board', $state->data)) {
            throw new InvalidArgumentException('Missing ultimate target.');
        }
        $target = $state->data['target_board'];
        if ($target !== null && (! is_int($target) || $target < 0 || $target > 8 || $this->resultFromCells($boards[$target]) !== null)) {
            throw new InvalidArgumentException('Invalid ultimate target.');
        }

        return $target;
    }

    /** @return list<int> */
    public function availableBoards(MatchState $state): array
    {
        if ($state->isFinished()) {
            return [];
        }
        $boards = $this->boards($state);
        $target = $this->targetBoard($state);
        if ($target !== null) {
            return [$target];
        }

        return array_keys(array_filter($boards, fn (array $board): bool => $this->resultFromCells($board) === null));
    }

    public function subBoardResult(MatchState $state, int $boardIndex): ?string
    {
        $boards = $this->boards($state);
        if ($boardIndex < 0 || $boardIndex > 8) {
            throw new InvalidArgumentException('Invalid ultimate board index.');
        }

        return $this->resultFromCells($boards[$boardIndex]);
    }

    /** @return array{int, int, int}|null */
    public function localWinningLine(MatchState $state, int $boardIndex): ?array
    {
        $boards = $this->boards($state);
        if ($boardIndex < 0 || $boardIndex > 8) {
            throw new InvalidArgumentException('Invalid ultimate board index.');
        }

        return $this->lineFromCells($boards[$boardIndex]);
    }

    /** @return array{int, int, int}|null */
    public function localWinningLineForPlayer(MatchState $state, int $boardIndex, PlayerMark $player): ?array
    {
        $boards = $this->boards($state);
        if ($boardIndex < 0 || $boardIndex > 8) {
            throw new InvalidArgumentException('Invalid ultimate board index.');
        }

        return $this->lineForMark($boards[$boardIndex], $player->value);
    }

    /** @return array{int, int, int}|null */
    public function globalWinningLine(MatchState $state): ?array
    {
        return $this->globalLineFromBoards($this->boards($state));
    }

    /** @return array{int, int, int}|null */
    public function globalWinningLineForPlayer(MatchState $state, PlayerMark $player): ?array
    {
        $main = array_map(function (array $board): string {
            $result = $this->resultFromCells($board);

            return in_array($result, ['x', 'o'], true) ? $result : '';
        }, $this->boards($state));

        return $this->lineForMark($main, $player->value);
    }

    public function globalWinner(MatchState $state): ?PlayerMark
    {
        $line = $this->globalWinningLine($state);
        if ($line === null) {
            return null;
        }

        return PlayerMark::from($this->subBoardResult($state, $line[0]));
    }

    /** @param list<list<string>> $boards
     * @return array{int, int, int}|null
     */
    private function globalLineFromBoards(array $boards): ?array
    {
        $main = array_map(function (array $board): string {
            $result = $this->resultFromCells($board);

            return in_array($result, ['x', 'o'], true) ? $result : '';
        }, $boards);

        return $this->lineFromCells($main);
    }

    /** @param list<string> $cells
     * @return array{int, int, int}|null
     */
    private function lineFromCells(array $cells): ?array
    {
        foreach (self::LINES as $line) {
            if ($cells[$line[0]] !== '' && $cells[$line[0]] === $cells[$line[1]] && $cells[$line[0]] === $cells[$line[2]]) {
                return $line;
            }
        }

        return null;
    }

    /** @param list<string> $cells
     * @return array{int, int, int}|null
     */
    private function lineForMark(array $cells, string $mark): ?array
    {
        foreach (self::LINES as $line) {
            if ($cells[$line[0]] === $mark && $cells[$line[1]] === $mark && $cells[$line[2]] === $mark) {
                return $line;
            }
        }

        return null;
    }

    /** @param list<string> $cells */
    private function resultFromCells(array $cells): ?string
    {
        $line = $this->lineFromCells($cells);
        if ($line !== null) {
            return $cells[$line[0]];
        }

        return in_array('', $cells, true) ? null : 'draw';
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== 'ultimate') {
            throw new InvalidArgumentException('Ultimate rules require the ultimate modality.');
        }
    }
}
