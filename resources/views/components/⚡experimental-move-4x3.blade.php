<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\PlayerMark;
use App\Games\Rules\Move4x3Rules;
use App\Http\MovementMatchManager;
use App\Http\SessionMatchStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $matchId;

    #[Locked]
    public bool $publicEntry = false;

    public ?string $rejectionMessage = null;

    public function mount(string $matchId, bool $publicEntry = false): void
    {
        $this->matchId = $matchId;
        $this->publicEntry = $publicEntry;
        $this->matchState();
    }

    #[Computed]
    public function matchState(): MatchState
    {
        abort_if($this->publicEntry && ! app(MovementMatchManager::class)->isActive4x3($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }

        abort_if($state === null || $state->modality->key !== '4x3-move', 404);

        return $state;
    }

    /**
     * @return array{x: int, o: int, draws: int}
     */
    #[Computed]
    public function score(): array
    {
        return app(SessionMatchStore::class)->score($this->matchId);
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function winningCells(): array
    {
        return app(Move4x3Rules::class)->winningLine($this->matchState) ?? [];
    }

    public function place(mixed $cellIndex): void
    {
        $this->applyAction('place', ['cell' => $cellIndex]);
    }

    public function move(mixed $from, mixed $to): void
    {
        $this->applyAction('move', ['from' => $from, 'to' => $to]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyAction(string $type, array $payload): void
    {
        $state = $this->matchState;

        $player = $state->currentPlayer ?? app(SessionMatchStore::class)->startingPlayer($this->matchId);
        $result = app(Move4x3Rules::class)->apply($state, new GameAction($player, $type, $payload));

        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'placement_complete' => 'Agora mova uma das suas peças.',
                'movement_not_started' => 'Coloque suas três peças antes de mover.',
                'empty_source', 'not_your_piece' => 'Selecione uma peça sua.',
                'invalid_cell', 'same_cell' => 'Escolha uma casa de destino válida.',
                'game_finished' => 'A rodada terminou. Inicie uma nova rodada para jogar novamente.',
                default => 'Não foi possível realizar esta jogada.',
            };
            $this->dispatch('movement-selection-reconcile');

            return;
        }

        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('movement-state-changed');

        if ($result->state->isFinished()) {
            $this->dispatch('movement-round-finished');
        }
    }

    public function restart(): void
    {
        $state = $this->matchState;
        $starter = app(SessionMatchStore::class)->startingPlayer($this->matchId);
        $nextStarter = $starter === PlayerMark::X ? PlayerMark::O : PlayerMark::X;

        app(SessionMatchStore::class)->restart(
            $this->matchId,
            app(Move4x3Rules::class)->initialState(new Modality('4x3-move'), $state->participants, $nextStarter),
        );

        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('movement-state-changed');
    }

    public function newMatch(): void
    {
        $matches = app(MovementMatchManager::class);
        abort_unless($this->publicEntry && $matches->isActive4x3($this->matchId), 404);

        $this->matchId = $matches->createNewActive4x3(app(Move4x3Rules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);

        $this->redirectRoute('4x3-move');
    }
};
?>

<x-design-system.movement-match
    :match-id="$matchId"
    :state="$this->matchState" :score="$this->score" :winning-cells="$this->winningCells"
    :rejection-message="$rejectionMessage" :public-entry="$publicEntry"
    modality="Movimento 4×4" description="Partida local: coloque três peças e depois mova para uma casa vazia."
    win-description="Três em linha! O placar foi atualizado." :piece-limit="3"
/>
