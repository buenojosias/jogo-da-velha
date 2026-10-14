<?php

use App\Games\MatchState;
use App\Games\Rules\Full5x5Rules;
use App\Http\ClassicRoundActions;
use App\Http\FullMatchManager;
use App\Http\SessionMatchStore;
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
        abort_unless(app(FullMatchManager::class)->isActive($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== '5-full', 404);

        return $state;
    }

    /** @return array{x: int, o: int, draws: int} */
    #[Computed]
    public function score(): array
    {
        return app(SessionMatchStore::class)->score($this->matchId);
    }

    /** @return array{x: int, o: int} */
    #[Computed]
    public function roundPoints(): array
    {
        return app(Full5x5Rules::class)->scores($this->matchState);
    }

    public function place(mixed $cellIndex): void
    {
        $result = app(ClassicRoundActions::class)->place(
            $this->matchId, $this->matchState, app(Full5x5Rules::class), $cellIndex,
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
        unset($this->matchState, $this->score, $this->roundPoints);
        if ($result->state->isFinished()) {
            $this->dispatch('full-board-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart($this->matchId, $this->matchState, app(Full5x5Rules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->roundPoints);
    }

    public function newMatch(): void
    {
        $this->matchState;
        $this->matchId = app(FullMatchManager::class)->createNewActive();
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->roundPoints);
        $this->redirectRoute('5-full');
    }
};
?>

<x-design-system.classic-match
    :match-id="$matchId"
    modality="Pontuação 5×5"
    :state="$this->matchState"
    :columns="5"
    board-label="Tabuleiro Pontuação 5 por 5"
    board-class="two-lines-5x5-board"
    :winning-cells="[]"
    :score="$this->score"
    :round-points="$this->roundPoints"
    :rejection-message="$rejectionMessage"
    :public-entry="true"
    round-finished-event="full-board-round-finished"
    instruction="Forme sequências para pontuar. A rodada termina quando todas as 25 casas estiverem ocupadas."
    result-description="O tabuleiro foi preenchido. Veja os pontos finais e o placar da partida."
    draw-description="Pontos iguais ao completar o tabuleiro."
/>
