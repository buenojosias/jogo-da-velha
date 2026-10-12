<?php

namespace App\Http;

use App\Games\ActionResult;
use App\Games\GameAction;
use App\Games\GameRules;
use App\Games\MatchState;
use App\Games\PlayerMark;

class ClassicRoundActions
{
    public function __construct(private SessionMatchStore $store) {}

    public function place(string $matchId, MatchState $state, GameRules $rules, mixed $cellIndex): ActionResult
    {
        $result = $rules->apply(
            $state,
            new GameAction($state->currentPlayer ?? PlayerMark::X, 'place', ['cell' => $cellIndex]),
        );

        if ($result->validation->valid) {
            $this->store->save($matchId, $result->state);
        }

        return $result;
    }

    public function restart(string $matchId, MatchState $state, GameRules $rules): void
    {
        $nextStarter = $this->store->startingPlayer($matchId) === PlayerMark::X
            ? PlayerMark::O
            : PlayerMark::X;

        $this->store->restart(
            $matchId,
            $rules->initialState($state->modality, $state->participants, $nextStarter),
        );
    }
}
