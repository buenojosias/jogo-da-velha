<?php

namespace App\Http;

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Hidden4x3Rules;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UnexpectedValueException;

class SessionMatchStore
{
    private const string SESSION_PREFIX = 'experimental_matches.';

    private const array BOARD_SIZES = ['3-basic' => 9, '4x3-basic' => 16, '4x3-block' => 16, '3-move' => 9, '4x3-move' => 16, '4x4-move' => 16, '3-rotation' => 9, '4x3-rotation' => 16, '4x3-hidden' => 16];

    private const array INITIAL_SCORE = ['x' => 0, 'o' => 0, 'draws' => 0];

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
            'score' => self::INITIAL_SCORE,
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
        $stored = $this->record($matchId);
        $previousState = $this->decode($stored['state']);
        $this->assertSameModality($previousState, $state);
        $score = $this->scoreFromRecord($stored);

        if ($previousState->status === MatchStatus::InProgress) {
            if ($state->status === MatchStatus::Completed) {
                $score[$state->winner->value]++;
            } elseif ($state->status === MatchStatus::Drawn) {
                $score['draws']++;
            }
        }

        $stored['state'] = $this->encode($state);
        $stored['score'] = $score;
        $this->session->put($this->key($matchId), $stored);
    }

    public function restart(string $matchId, MatchState $state): void
    {
        $stored = $this->record($matchId);
        $this->assertSameModality($this->decode($stored['state']), $state);
        $score = $this->scoreFromRecord($stored);

        $this->session->put($this->key($matchId), [
            'starting_player' => $this->initialPlayer($state)->value,
            'state' => $this->encode($state),
            'score' => $score,
        ]);
    }

    /**
     * @return array{x: int, o: int, draws: int}
     */
    public function score(string $matchId): array
    {
        return $this->scoreFromRecord($this->record($matchId));
    }

    /**
     * @return array<string, mixed>
     */
    private function record(string $matchId): array
    {
        $stored = $this->session->get($this->key($matchId));

        if ($stored === null) {
            throw new InvalidArgumentException('The match does not belong to this session.');
        }

        if (! is_array($stored) || ! array_key_exists('state', $stored)) {
            throw new UnexpectedValueException('Invalid stored match record.');
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array{x: int, o: int, draws: int}
     */
    private function scoreFromRecord(array $stored): array
    {
        $score = array_key_exists('score', $stored) ? $stored['score'] : self::INITIAL_SCORE;

        if (! is_array($score) || count($score) !== 3) {
            throw new UnexpectedValueException('Invalid stored match score.');
        }

        foreach (array_keys(self::INITIAL_SCORE) as $key) {
            if (! isset($score[$key]) || ! is_int($score[$key]) || $score[$key] < 0) {
                throw new UnexpectedValueException('Invalid stored match score.');
            }
        }

        return ['x' => $score['x'], 'o' => $score['o'], 'draws' => $score['draws']];
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
        $this->assertBoard($state->modality->key, $state->data['board'] ?? null);
        $this->assertRotationOrder($state->modality->key, $state->data['board'], $state->data['order'] ?? null);
        $this->assertHiddenOrder($state->modality->key, $state->data['board'], $state->data['order'] ?? null);
        $this->assertHiddenOutcome($state);

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
            || ! is_string($stored['modality'] ?? null)
            || ! is_array($stored['participants'] ?? null)
            || ! is_array($stored['data'] ?? null)
            || ! array_key_exists('board', $stored['data'])) {
            throw new UnexpectedValueException('Invalid stored match state.');
        }

        $this->assertBoard($stored['modality'], $stored['data']['board']);
        $this->assertRotationOrder($stored['modality'], $stored['data']['board'], $stored['data']['order'] ?? null);
        $this->assertHiddenOrder($stored['modality'], $stored['data']['board'], $stored['data']['order'] ?? null);

        $status = is_string($stored['status'] ?? null) ? MatchStatus::tryFrom($stored['status']) : null;

        if ($status === null) {
            throw new UnexpectedValueException('Invalid stored match status.');
        }

        try {
            $state = new MatchState(
                new Modality($stored['modality']),
                new Participants(
                    $this->participant($stored['participants']['x'] ?? null),
                    $this->participant($stored['participants']['o'] ?? null),
                ),
                $status,
                $this->playerMark($stored['current_player'] ?? null),
                in_array($stored['modality'], ['3-rotation', '4x3-rotation', '4x3-hidden'], true)
                    ? ['board' => $stored['data']['board'], 'order' => $stored['data']['order']]
                    : ['board' => $stored['data']['board']],
                $this->playerMark($stored['winner'] ?? null),
            );
            $this->assertHiddenOutcome($state);

            return $state;
        } catch (InvalidArgumentException $exception) {
            throw new UnexpectedValueException('Invalid stored match state.', previous: $exception);
        }
    }

    private function assertSameModality(MatchState $previous, MatchState $next): void
    {
        if ($previous->modality->key !== $next->modality->key) {
            throw new InvalidArgumentException('A match cannot change modality.');
        }
    }

    private function assertBoard(string $modalityKey, mixed $board): void
    {
        $size = self::BOARD_SIZES[$modalityKey] ?? null;

        if ($size === null || ! is_array($board) || ! array_is_list($board) || count($board) !== $size) {
            throw new UnexpectedValueException('Invalid stored match state.');
        }

        foreach ($board as $cell) {
            if (! in_array($cell, ['', 'x', 'o'], true)) {
                throw new UnexpectedValueException('Invalid stored match board.');
            }
        }

        if ($modalityKey === '4x3-block'
            && abs(count(array_keys($board, 'x', true)) - count(array_keys($board, 'o', true))) > 1) {
            throw new UnexpectedValueException('Invalid stored blocking board.');
        }

        if (in_array($modalityKey, ['3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation'], true)) {
            $xCount = count(array_keys($board, 'x', true));
            $oCount = count(array_keys($board, 'o', true));
            $pieceLimit = $modalityKey === '4x4-move' ? 4 : 3;

            if ($xCount > $pieceLimit || $oCount > $pieceLimit || abs($xCount - $oCount) > 1) {
                throw new UnexpectedValueException('Invalid stored movement board.');
            }
        }
    }

    private function assertRotationOrder(string $modalityKey, array $board, mixed $order): void
    {
        if (! in_array($modalityKey, ['3-rotation', '4x3-rotation'], true)) {
            return;
        }

        if (! is_array($order) || array_keys($order) !== ['x', 'o']) {
            throw new UnexpectedValueException('Invalid stored rotation order.');
        }

        foreach (['x', 'o'] as $mark) {
            $indices = $order[$mark];
            if (! is_array($indices) || ! array_is_list($indices) || count($indices) > 3
                || count($indices) !== count(array_keys($board, $mark, true))
                || count(array_unique($indices)) !== count($indices)) {
                throw new UnexpectedValueException('Invalid stored rotation order.');
            }

            foreach ($indices as $index) {
                if (! is_int($index) || ! array_key_exists($index, $board) || $board[$index] !== $mark) {
                    throw new UnexpectedValueException('Invalid stored rotation order.');
                }
            }
        }
    }

    private function assertHiddenOrder(string $modalityKey, array $board, mixed $order): void
    {
        if ($modalityKey !== '4x3-hidden') {
            return;
        }
        if (! is_array($order) || array_keys($order) !== ['x', 'o']) {
            throw new UnexpectedValueException('Invalid stored hidden order.');
        }
        foreach (['x', 'o'] as $mark) {
            $indices = $order[$mark];
            if (! is_array($indices) || ! array_is_list($indices) || count($indices) > 5
                || count($indices) !== count(array_keys($board, $mark, true))
                || count(array_unique($indices)) !== count($indices)) {
                throw new UnexpectedValueException('Invalid stored hidden order.');
            }
            foreach ($indices as $index) {
                if (! is_int($index) || ! array_key_exists($index, $board) || $board[$index] !== $mark) {
                    throw new UnexpectedValueException('Invalid stored hidden order.');
                }
            }
        }
        if (abs(count($order['x']) - count($order['o'])) > 1) {
            throw new UnexpectedValueException('Invalid stored hidden order.');
        }
    }

    private function assertHiddenOutcome(MatchState $state): void
    {
        if ($state->modality->key !== '4x3-hidden') {
            return;
        }
        if ($state->status === MatchStatus::Awaiting || $state->status === MatchStatus::Drawn
            || ($state->status === MatchStatus::Completed
                && app(Hidden4x3Rules::class)->winningLine($state) === null)) {
            throw new UnexpectedValueException('Invalid stored hidden result.');
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
