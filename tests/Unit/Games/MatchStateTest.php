<?php

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;

test('a new match identifies both participants and the first player', function () {
    $modality = new Modality('ultimate', ['sub_boards' => 9]);
    $participants = new Participants(
        new Participant(ParticipantKind::Guest, 'visitor-x'),
        new Participant(ParticipantKind::Bot, 'bot-o'),
    );

    $state = new MatchState($modality, $participants, MatchStatus::Awaiting, PlayerMark::O, ['boards' => []]);

    expect($state->modality)->toBe($modality);
    expect($state->participants)->toBe($participants);
    expect($state->currentPlayer)->toBe(PlayerMark::O);
    expect($state->status)->toBe(MatchStatus::Awaiting);
    expect($state->data)->toBe(['boards' => []]);
    expect($state->isFinished())->toBeFalse();
});

test('each match status exposes its terminal state', function (MatchStatus $status, ?PlayerMark $nextPlayer, ?PlayerMark $winner, bool $finished) {
    $state = new MatchState(
        new Modality('classic-3x3'),
        new Participants(
            new Participant(ParticipantKind::Guest, 'visitor-x'),
            new Participant(ParticipantKind::Guest, 'visitor-o'),
        ),
        $status,
        $nextPlayer,
        winner: $winner,
    );

    expect($state->isFinished())->toBe($finished);
    expect($state->winner)->toBe($winner);
})->with([
    'awaiting' => [MatchStatus::Awaiting, PlayerMark::X, null, false],
    'in progress' => [MatchStatus::InProgress, PlayerMark::O, null, false],
    'completed' => [MatchStatus::Completed, null, PlayerMark::X, true],
    'drawn' => [MatchStatus::Drawn, null, null, true],
]);

test('a terminal match cannot have a next player', function () {
    $modality = new Modality('classic-3x3');
    $participants = new Participants(
        new Participant(ParticipantKind::Guest, 'visitor-x'),
        new Participant(ParticipantKind::Guest, 'visitor-o'),
    );

    expect(fn () => new MatchState($modality, $participants, MatchStatus::Drawn, PlayerMark::X))
        ->toThrow(InvalidArgumentException::class);
});

test('a completed match requires a winner and a draw forbids one', function () {
    $modality = new Modality('classic-3x3');
    $participants = new Participants(
        new Participant(ParticipantKind::Guest, 'visitor-x'),
        new Participant(ParticipantKind::Guest, 'visitor-o'),
    );

    expect(fn () => new MatchState($modality, $participants, MatchStatus::Completed, null))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => new MatchState($modality, $participants, MatchStatus::Drawn, null, winner: PlayerMark::O))
        ->toThrow(InvalidArgumentException::class);
});

test('an active match requires a next player and cannot have a winner', function () {
    $modality = new Modality('classic-3x3');
    $participants = new Participants(
        new Participant(ParticipantKind::Guest, 'visitor-x'),
        new Participant(ParticipantKind::Guest, 'visitor-o'),
    );

    expect(fn () => new MatchState($modality, $participants, MatchStatus::InProgress, null))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => new MatchState($modality, $participants, MatchStatus::InProgress, PlayerMark::X, winner: PlayerMark::O))
        ->toThrow(InvalidArgumentException::class);
});
