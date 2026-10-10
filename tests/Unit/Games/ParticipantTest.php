<?php

use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;

test('participants identify guests, users, and bots without a user model', function () {
    $guest = new Participant(ParticipantKind::Guest, 'visitor-17');
    $bot = new Participant(ParticipantKind::Bot, 'bot-easy');
    $user = new Participant(ParticipantKind::User, '42');
    $participants = new Participants($guest, $bot);

    expect($participants->for(PlayerMark::X))->toBe($guest);
    expect($participants->for(PlayerMark::O))->toBe($bot);
    expect($user->kind)->toBe(ParticipantKind::User);
    expect($user->id)->toBe('42');
});

test('the same participant cannot occupy both marks', function () {
    $participant = new Participant(ParticipantKind::Guest, 'visitor-17');

    expect(fn () => new Participants($participant, $participant))->toThrow(InvalidArgumentException::class);
});

test('participant identifiers must not be blank', function () {
    expect(fn () => new Participant(ParticipantKind::Guest, ' '))->toThrow(InvalidArgumentException::class);
});
