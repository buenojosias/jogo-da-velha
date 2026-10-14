<?php

use App\Games\MatchState;
use App\Games\Rules\Classic3x3Rules;
use App\Http\ClassicMatchManager;
use App\Http\ClassicRoundActions;
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

        $state = app(SessionMatchStore::class)->find($matchId);
        abort_if($state === null || $state->modality->key !== '3-basic', 404);
        abort_if($publicEntry && ! app(ClassicMatchManager::class)->isActive($matchId), 404);
    }

    #[Computed]
    public function matchState(): MatchState
    {
        abort_if($this->publicEntry && ! app(ClassicMatchManager::class)->isActive($this->matchId), 404);

        $state = app(SessionMatchStore::class)->find($this->matchId);
        abort_if($state === null || $state->modality->key !== '3-basic', 404);

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
        return app(Classic3x3Rules::class)->winningLine($this->matchState) ?? [];
    }

    public function place(mixed $cellIndex): void
    {
        $state = $this->matchState;
        $result = app(ClassicRoundActions::class)->place(
            $this->matchId,
            $state,
            app(Classic3x3Rules::class),
            $cellIndex,
        );

        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'game_finished' => 'A partida terminou. Reinicie para jogar novamente.',
                'invalid_cell' => 'Escolha uma casa válida.',
                default => 'Não foi possível realizar esta jogada.',
            };

            return;
        }

        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);

        if ($result->state->isFinished()) {
            $this->dispatch('classic-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart(
            $this->matchId,
            $this->matchState,
            app(Classic3x3Rules::class),
        );

        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
    }

    public function newMatch(): void
    {
        $matches = app(ClassicMatchManager::class);
        abort_unless($this->publicEntry && $matches->isActive($this->matchId), 404);

        $this->matchId = $matches->createNewActive();
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);

        $this->redirectRoute('3-basic');
    }
};
?>

<x-design-system.classic-match
    :match-id="$matchId"
    modality="Clássico 3×3"
    :state="$this->matchState"
    :columns="3"
    board-label="Tabuleiro 3 por 3"
    :winning-cells="$this->winningCells"
    :score="$this->score"
    :rejection-message="$rejectionMessage"
    :public-entry="$publicEntry"
    round-finished-event="classic-round-finished"
/>
