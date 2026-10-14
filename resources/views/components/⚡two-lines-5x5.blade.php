<?php

use App\Games\MatchState;
use App\Games\Rules\TwoLines5x5Rules;
use App\Http\ClassicRoundActions;
use App\Http\SessionMatchStore;
use App\Http\TwoLinesMatchManager;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $matchId;

    public ?string $rejectionMessage = null;

    public function mount(string $matchId): void
    {
        $this->matchId = $matchId;
        $this->matchState();
    }

    #[Computed]
    public function matchState(): MatchState
    {
        abort_unless(app(TwoLinesMatchManager::class)->isActive($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== '5x5-2-lines', 404);

        return $state;
    }

    /** @return array{x: int, o: int, draws: int} */
    #[Computed]
    public function score(): array
    {
        return app(SessionMatchStore::class)->score($this->matchId);
    }

    /** @return list<int> */
    #[Computed]
    public function winningCells(): array
    {
        return app(TwoLines5x5Rules::class)->winningCells($this->matchState);
    }

    public function place(mixed $cellIndex): void
    {
        $result = app(ClassicRoundActions::class)->place(
            $this->matchId, $this->matchState, app(TwoLines5x5Rules::class), $cellIndex,
        );
        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'game_finished' => 'A rodada terminou. Inicie uma nova rodada para jogar novamente.',
                'invalid_cell' => 'Escolha uma casa válida.',
                default => 'Não foi possível realizar esta jogada.',
            };

            return;
        }

        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        if ($result->state->isFinished()) {
            $this->dispatch('two-lines-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart($this->matchId, $this->matchState, app(TwoLines5x5Rules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
    }

    public function newMatch(): void
    {
        $this->matchState;
        $this->matchId = app(TwoLinesMatchManager::class)->createNewActive();
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->redirectRoute('5x5-2-lines');
    }
};
?>

<x-design-system.classic-match
    :match-id="$matchId"
    modality="Duas Linhas 5×5"
    :state="$this->matchState"
    :columns="5"
    board-label="Tabuleiro Duas Linhas 5 por 5"
    board-class="two-lines-5x5-board"
    :winning-cells="$this->winningCells"
    :score="$this->score"
    :rejection-message="$rejectionMessage"
    :public-entry="true"
    round-finished-event="two-lines-round-finished"
    instruction="Forme duas linhas independentes de três peças."
    result-description="Duas linhas completas! O placar foi atualizado."
    draw-description="Ninguém completou duas linhas nesta rodada."
/>
