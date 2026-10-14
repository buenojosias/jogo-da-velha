<?php

use App\Games\MatchState;
use App\Games\Rules\Block4x3Rules;
use App\Http\BlockMatchManager;
use App\Http\ClassicRoundActions;
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
        abort_unless(app(BlockMatchManager::class)->isActive($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }

        abort_if($state === null || $state->modality->key !== '4x3-block', 404);

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
        return app(Block4x3Rules::class)->winningLine($this->matchState) ?? [];
    }

    public function place(mixed $cellIndex): void
    {
        $state = $this->matchState;
        $rules = app(Block4x3Rules::class);
        $result = app(ClassicRoundActions::class)->place($this->matchId, $state, $rules, $cellIndex);

        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'game_finished' => 'A rodada terminou. Reinicie para jogar novamente.',
                'invalid_cell' => 'Escolha uma casa válida.',
                default => 'Não foi possível realizar esta jogada.',
            };

            return;
        }

        $this->rejectionMessage = null;
        $blockedCells = $result->state->isFinished() ? [] : $rules->blockersForMove($result->state, $state->currentPlayer, $cellIndex);
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('block-state-changed');

        if ($result->state->isFinished()) {
            $this->dispatch('block-round-finished');
        } elseif ($blockedCells !== []) {
            $this->dispatch('block-line', cells: $blockedCells);
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart($this->matchId, $this->matchState, app(Block4x3Rules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('block-state-changed');
    }

    public function newMatch(): void
    {
        $matches = app(BlockMatchManager::class);
        abort_unless($matches->isActive($this->matchId), 404);

        $this->matchId = $matches->createNewActive();
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->redirectRoute('4x3-block');
    }
};
?>

<div
    x-data="{
        blockedCells: [],
        blockedTimer: null,
        clearBlocked() {
            clearTimeout(this.blockedTimer);
            this.blockedCells = [];
            this.$el.querySelectorAll('[data-block-feedback=true]').forEach(cell => {
                cell.dataset.blockFeedback = 'false';
                cell.classList.remove('ring-4', 'ring-ds-warning');
            });
        },
        showBlocked(cells) {
            this.clearBlocked();
            this.blockedCells = cells;
            this.$nextTick(() => {
                cells.forEach(index => {
                    const cell = this.$el.querySelector('[data-testid=cell-' + index + ']');
                    if (!cell) return;
                    cell.dataset.blockFeedback = 'true';
                    cell.classList.add('ring-4', 'ring-ds-warning');
                });
            });
            this.blockedTimer = setTimeout(() => this.clearBlocked(), 1000);
        }
    }"
    x-on:block-state-changed.window="clearBlocked()"
    x-on:block-line.window="showBlocked($event.detail.cells)"
>
    <x-design-system.classic-match
    :match-id="$matchId"
        modality="Bloqueio 4×4"
        :state="$this->matchState"
        :columns="4"
        board-label="Tabuleiro Bloqueio 4 por 4"
        board-class="classic-4x3-board"
        :winning-cells="$this->winningCells"
        :score="$this->score"
        :rejection-message="$rejectionMessage"
        :public-entry="true"
        round-finished-event="block-round-finished"
    />
    <p hidden x-bind:hidden="blockedCells.length === 0" data-testid="blocked-feedback" role="status" class="pointer-events-none fixed bottom-4 left-1/2 z-10 -translate-x-1/2 rounded-ds-control border border-ds-warning bg-ds-warning-surface px-4 py-2 text-sm font-bold text-ds-warning shadow-ds-card">Linha bloqueada!</p>
</div>
