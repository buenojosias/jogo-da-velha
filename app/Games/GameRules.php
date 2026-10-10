<?php

namespace App\Games;

interface GameRules
{
    public function initialState(Modality $modality, Participants $participants, PlayerMark $startingPlayer): MatchState;

    public function validate(MatchState $state, GameAction $action): ActionValidation;

    /**
     * A rejected action must return the original state through ActionResult::rejected().
     */
    public function apply(MatchState $state, GameAction $action): ActionResult;
}
