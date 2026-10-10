<?php

namespace App\Games;

enum ParticipantKind: string
{
    case Guest = 'guest';
    case User = 'user';
    case Bot = 'bot';
}
