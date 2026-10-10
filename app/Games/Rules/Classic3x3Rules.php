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

class Classic3x3Rules implements GameRules
{
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
            ['board' => array_fill(0, 9, '')],
        );
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

        $cellIndex = $action->payload['cell'] ?? null;

        if (! is_int($cellIndex) || $cellIndex < 0 || $cellIndex > 8) {
            return ActionValidation::invalid('invalid_cell');
        }

        if ($this->board($state)[$cellIndex] !== '') {
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

        $board = $this->board($state);
        $board[$action->payload['cell']] = $action->player->value;

        if ($this->hasWinningLine($board, $action->player)) {
            return ActionResult::accepted(new MatchState(
                $state->modality,
                $state->participants,
                MatchStatus::Completed,
                null,
                ['board' => $board],
                $action->player,
            ));
        }

        if (! in_array('', $board, true)) {
            return ActionResult::accepted(new MatchState(
                $state->modality,
                $state->participants,
                MatchStatus::Drawn,
                null,
                ['board' => $board],
            ));
        }

        $nextPlayer = $action->player === PlayerMark::X ? PlayerMark::O : PlayerMark::X;

        return ActionResult::accepted(new MatchState(
            $state->modality,
            $state->participants,
            MatchStatus::InProgress,
            $nextPlayer,
            ['board' => $board],
        ));
    }

    private function assertModality(Modality $modality): void
    {
        if ($modality->key !== '3-basic') {
            throw new InvalidArgumentException('Classic 3x3 rules require the 3-basic modality.');
        }
    }

    /**
     * @return list<string>
     */
    private function board(MatchState $state): array
    {
        $board = $state->data['board'] ?? null;

        if (! is_array($board) || ! array_is_list($board) || count($board) !== 9) {
            throw new InvalidArgumentException('Invalid classic 3x3 board state.');
        }

        foreach ($board as $cell) {
            if (! in_array($cell, ['', PlayerMark::X->value, PlayerMark::O->value], true)) {
                throw new InvalidArgumentException('Invalid classic 3x3 board state.');
            }
        }

        return $board;
    }

    /**
     * @param  list<string>  $board
     */
    private function hasWinningLine(array $board, PlayerMark $player): bool
    {
        foreach (self::WINNING_LINES as [$first, $second, $third]) {
            if ($board[$first] === $player->value
                && $board[$second] === $player->value
                && $board[$third] === $player->value) {
                return true;
            }
        }

        return false;
    }
}
