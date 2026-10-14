<?php

use App\Games\MatchState;
use App\Games\Rules\Classic4x3Rules;
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
        $this->matchState();
    }

    #[Computed]
    public function matchState(): MatchState
    {
        abort_if($this->publicEntry && ! app(ClassicMatchManager::class)->isActive4x3($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }

        abort_if($state === null || $state->modality->key !== '4x3-basic', 404);

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
        return app(Classic4x3Rules::class)->winningLine($this->matchState) ?? [];
    }

    public function place(mixed $cellIndex): void
    {
        $state = $this->matchState;
        $result = app(ClassicRoundActions::class)->place(
            $this->matchId,
            $state,
            app(Classic4x3Rules::class),
            $cellIndex,
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
            $this->dispatch('classic4x3-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart(
            $this->matchId,
            $this->matchState,
            app(Classic4x3Rules::class),
        );

        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
    }

    public function newMatch(): void
    {
        $matches = app(ClassicMatchManager::class);
        abort_unless($this->publicEntry && $matches->isActive4x3($this->matchId), 404);

        $this->matchId = $matches->createNewActive4x3(app(Classic4x3Rules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);

        $this->redirectRoute('4x3-basic');
    }
};
?>

<x-design-system.classic-match
    :match-id="$matchId"
    modality="Clássico 4×4 (3 peças)"
    :state="$this->matchState"
    :columns="4"
    board-label="Tabuleiro 4 por 4"
    board-class="classic-4x3-board"
    :winning-cells="$this->winningCells"
    :score="$this->score"
    :rejection-message="$rejectionMessage"
    :public-entry="$publicEntry"
    round-finished-event="classic4x3-round-finished"
/>
