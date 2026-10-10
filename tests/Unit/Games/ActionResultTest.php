<?php

use App\Games\ActionResult;
use App\Games\ActionValidation;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;

test('an accepted action returns the resulting state', function () {
    $state = new MatchState(
        new Modality('classic-3x3'),
        new Participants(
            new Participant(ParticipantKind::Guest, 'visitor-x'),
            new Participant(ParticipantKind::Guest, 'visitor-o'),
        ),
        MatchStatus::InProgress,
        PlayerMark::O,
        ['board' => ['x', '', '']],
    );

    $result = ActionResult::accepted($state);

    expect($result->validation->valid)->toBeTrue();
    expect($result->validation->reason)->toBeNull();
    expect($result->state)->toBe($state);
});

test('a rejected action returns the exact original state with a reason', function () {
    $state = new MatchState(
        new Modality('classic-3x3'),
        new Participants(
            new Participant(ParticipantKind::Guest, 'visitor-x'),
            new Participant(ParticipantKind::Guest, 'visitor-o'),
        ),
        MatchStatus::InProgress,
        PlayerMark::X,
        ['board' => ['o', '', '']],
    );

    $result = ActionResult::rejected($state, 'cell_occupied');

    expect($result->validation->valid)->toBeFalse();
    expect($result->validation->reason)->toBe('cell_occupied');
    expect($result->state)->toBe($state);
    expect($result->state->data)->toBe(['board' => ['o', '', '']]);
});

test('an invalid validation requires a reason', function () {
    expect(fn () => ActionValidation::invalid(' '))->toThrow(InvalidArgumentException::class);
});
