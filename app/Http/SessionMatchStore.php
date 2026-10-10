<?php

namespace App\Http;

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UnexpectedValueException;

class SessionMatchStore
{
    private const string SESSION_PREFIX = 'experimental_matches.';

    public function __construct(private Session $session) {}

    public function create(MatchState $state): string
    {
        $startingPlayer = $this->initialPlayer($state);

        do {
            $matchId = Str::random(40);
        } while ($this->session->has($this->key($matchId)));

        $this->session->put($this->key($matchId), [
            'starting_player' => $startingPlayer->value,
            'state' => $this->encode($state),
        ]);

        return $matchId;
    }

    public function find(string $matchId): ?MatchState
    {
        $stored = $this->session->get($this->key($matchId));

        if ($stored === null) {
            return null;
        }

        if (! is_array($stored) || ! array_key_exists('state', $stored)) {
            throw new UnexpectedValueException('Invalid stored match record.');
        }

        return $this->decode($stored['state']);
    }

    public function startingPlayer(string $matchId): PlayerMark
    {
        $stored = $this->session->get($this->key($matchId));
        $mark = is_array($stored) ? $this->playerMark($stored['starting_player'] ?? null) : null;

        if ($mark === null) {
            throw new InvalidArgumentException('The match does not belong to this session.');
        }

        return $mark;
    }

    public function save(string $matchId, MatchState $state): void
    {
        if (! $this->session->has($this->key($matchId))) {
            throw new InvalidArgumentException('The match does not belong to this session.');
        }

        $this->session->put($this->key($matchId).'.state', $this->encode($state));
    }

    public function restart(string $matchId, MatchState $state): void
    {
        if (! $this->session->has($this->key($matchId))) {
            throw new InvalidArgumentException('The match does not belong to this session.');
        }

        $this->session->put($this->key($matchId), [
            'starting_player' => $this->initialPlayer($state)->value,
            'state' => $this->encode($state),
        ]);
    }

    private function initialPlayer(MatchState $state): PlayerMark
    {
        if ($state->status !== MatchStatus::InProgress || $state->currentPlayer === null) {
            throw new InvalidArgumentException('A new match requires an initial player.');
        }

        return $state->currentPlayer;
    }

    private function key(string $matchId): string
    {
        if (! preg_match('/\A[A-Za-z0-9]{40}\z/D', $matchId)) {
            throw new InvalidArgumentException('Invalid match identifier.');
        }

        return self::SESSION_PREFIX.$matchId;
    }

    /**
     * @return array<string, mixed>
     */
    private function encode(MatchState $state): array
    {
        return [
            'modality' => $state->modality->key,
            'participants' => [
                'x' => ['kind' => $state->participants->x->kind->value, 'id' => $state->participants->x->id],
                'o' => ['kind' => $state->participants->o->kind->value, 'id' => $state->participants->o->id],
            ],
            'status' => $state->status->value,
            'current_player' => $state->currentPlayer?->value,
            'winner' => $state->winner?->value,
            'data' => $state->data,
        ];
    }

    private function decode(mixed $stored): MatchState
    {
        if (! is_array($stored)
            || ($stored['modality'] ?? null) !== '3-basic'
            || ! is_array($stored['participants'] ?? null)
            || ! is_array($stored['data'] ?? null)
            || ! isset($stored['data']['board'])
            || ! is_array($stored['data']['board'])
            || ! array_is_list($stored['data']['board'])
            || count($stored['data']['board']) !== 9) {
            throw new UnexpectedValueException('Invalid stored match state.');
        }

        foreach ($stored['data']['board'] as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new UnexpectedValueException('Invalid stored match board.');
            }
        }

        $status = is_string($stored['status'] ?? null) ? MatchStatus::tryFrom($stored['status']) : null;

        if ($status === null) {
            throw new UnexpectedValueException('Invalid stored match status.');
        }

        try {
            return new MatchState(
                new Modality('3-basic'),
                new Participants(
                    $this->participant($stored['participants']['x'] ?? null),
                    $this->participant($stored['participants']['o'] ?? null),
                ),
                $status,
                $this->playerMark($stored['current_player'] ?? null),
                ['board' => $stored['data']['board']],
                $this->playerMark($stored['winner'] ?? null),
            );
        } catch (InvalidArgumentException $exception) {
            throw new UnexpectedValueException('Invalid stored match state.', previous: $exception);
        }
    }

    private function participant(mixed $stored): Participant
    {
        if (! is_array($stored)
            || ! is_string($stored['kind'] ?? null)
            || ! is_string($stored['id'] ?? null)
            || ($kind = ParticipantKind::tryFrom($stored['kind'])) === null) {
            throw new UnexpectedValueException('Invalid stored participant.');
        }

        return new Participant($kind, $stored['id']);
    }

    private function playerMark(mixed $value): ?PlayerMark
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) || ($mark = PlayerMark::tryFrom($value)) === null) {
            throw new UnexpectedValueException('Invalid stored player mark.');
        }

        return $mark;
    }
}
