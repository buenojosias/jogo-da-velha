<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\Modality;
use App\Games\PlayerMark;
use App\Games\Rules\Move3x3Rules;
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
        abort_if($this->publicEntry && ! app(MovementMatchManager::class)->isActive($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }

        abort_if($state === null || $state->modality->key !== '3-move', 404);

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
        return app(Move3x3Rules::class)->winningLine($this->matchState) ?? [];
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
        $result = app(Move3x3Rules::class)->apply($state, new GameAction($player, $type, $payload));

        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'placement_complete' => 'Agora mova uma das suas peças.',
                'movement_not_started' => 'Coloque suas três peças antes de mover.',
                'empty_source', 'not_your_piece' => 'Selecione uma peça sua.',
                'invalid_cell', 'same_cell' => 'Escolha uma casa de destino válida.',
                'game_finished' => 'A rodada terminou. Reinicie para jogar novamente.',
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
            app(Move3x3Rules::class)->initialState(new Modality('3-move'), $state->participants, $nextStarter),
        );

        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('movement-state-changed');
    }

    public function newMatch(): void
    {
        $matches = app(MovementMatchManager::class);
        abort_unless($this->publicEntry && $matches->isActive($this->matchId), 404);

        $this->matchId = $matches->createNewActive();
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);

        $this->redirectRoute('3-move');
    }
};
?>

@php($state = $this->matchState)
@php($score = $this->score)
@php($board = $state->data['board'])
@php($movementPhase = count(array_filter($board)) === 6)
@php($unavailableCells = $state->isFinished() ? array_keys($board) : ($movementPhase ? array_keys(array_filter($board, fn (string $mark): bool => $mark !== '' && $mark !== $state->currentPlayer->value)) : array_keys(array_filter($board))))

<div
    class="experimental-match mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-4 px-4 py-4 text-ds-text sm:px-6 sm:py-6"
    x-data="{
        selectedCell: null,
        pending: false,
        resultOrigin: null,
        handleCell(index) {
            if (this.pending) return;
            const board = this.$refs.movementBoard;
            if (!board || board.dataset.finished === 'true') return;
            const button = board.querySelector('[data-testid=cell-' + index + ']');
            if (!button) return;
            const mark = button.dataset.mark;
            if (board.dataset.phase === 'place') {
                if (mark !== '') return;
                this.pending = true;
                this.$wire.place(index).finally(() => { this.pending = false; });
                return;
            }
            if (mark === board.dataset.currentMark) {
                this.selectedCell = this.selectedCell === index ? null : index;
                return;
            }
            if (mark !== '' || this.selectedCell === null) return;
            this.pending = true;
            this.$wire.move(this.selectedCell, index).finally(() => { this.pending = false; });
        },
        reconcileSelection() {
            const board = this.$refs.movementBoard;
            if (!board || board.dataset.phase !== 'move' || board.dataset.finished === 'true') {
                this.selectedCell = null;
                return;
            }
            const button = board.querySelector('[data-testid=cell-' + this.selectedCell + ']');
            if (!button || button.dataset.mark !== board.dataset.currentMark) this.selectedCell = null;
        },
        openRoundResult() {
            this.selectedCell = null;
            this.$nextTick(() => {
                const dialog = this.$refs.resultDialog;
                if (!dialog || dialog.open) return;
                this.resultOrigin = document.activeElement;
                dialog.showModal();
            });
        },
        closeRoundResult() { this.$refs.resultDialog?.close(); },
        restoreRoundResultFocus() {
            const origin = this.resultOrigin;
            const canReturn = origin instanceof HTMLElement && origin.isConnected && origin.tabIndex >= 0 && !origin.matches(':disabled');
            requestAnimationFrame(() => (canReturn ? origin : this.$refs.restartButton)?.focus());
        }
    }"
    x-on:movement-state-changed.window="selectedCell = null"
    x-on:movement-selection-reconcile.window="$nextTick(() => reconcileSelection())"
    x-on:movement-round-finished.window="openRoundResult()"
>
    <x-design-system.match-header modality="Movimento 3×3" title="Jogo da Velha" description="Partida local: coloque três peças e depois mova para uma casa vazia." data-testid="match-header">
        <x-slot:actions><x-design-system.theme-selector data-testid="theme-selector" /></x-slot:actions>
    </x-design-system.match-header>

    <section aria-label="Partida local" class="experimental-match-layout grid items-start gap-4 md:grid-cols-[minmax(0,1fr)_15rem] md:gap-5 xl:grid-cols-[minmax(0,1fr)_17rem]">
        <div data-testid="game-region" class="experimental-game-region min-w-0 rounded-ds-card border border-t-4 border-ds-border border-t-ds-primary bg-ds-surface p-3 shadow-ds-elevated sm:p-5">
            <x-design-system.match-status :tone="$state->status === MatchStatus::Completed ? 'win' : 'turn'" data-testid="match-status">
                @if ($state->status === MatchStatus::Completed)
                    Jogador {{ strtoupper($state->winner->value) }} venceu!
                @else
                    Vez do jogador {{ strtoupper($state->currentPlayer->value) }}
                @endif
            </x-design-system.match-status>
            @if (! $state->isFinished())
                <p data-testid="phase-instruction" class="mt-2 text-center text-sm font-bold text-ds-text-secondary">{{ $movementPhase ? 'Selecione uma peça e depois uma casa vazia' : 'Coloque uma peça' }}</p>
            @endif
            <x-design-system.game-board
                :cells="$board" :columns="3" label="Tabuleiro Movimento 3 por 3"
                :highlighted-cells="$this->winningCells" :unavailable-cells="$unavailableCells"
                highlight-label="casa vencedora" interaction="movement"
                x-ref="movementBoard" data-phase="{{ $movementPhase ? 'move' : 'place' }}"
                data-current-mark="{{ $state->currentPlayer?->value }}"
                data-finished="{{ $state->isFinished() ? 'true' : 'false' }}"
                data-testid="movement-board"
            />
            @if ($rejectionMessage)
                <p data-testid="rejection-message" role="alert" class="mt-3 rounded-ds-control border border-ds-warning bg-ds-warning-surface p-3 text-sm text-ds-warning">{{ $rejectionMessage }}</p>
            @endif
            <p wire:loading role="status" class="mt-3 text-sm text-ds-text-secondary">Processando jogada…</p>
        </div>

        <div class="experimental-match-sidebar flex min-w-0 flex-col gap-3">
            <div class="experimental-participants grid grid-cols-2 gap-2.5 md:grid-cols-1" aria-label="Participantes">
                <x-design-system.participant-card label="Jogador X" mark="X" :wins="$score['x']" :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::X" :finished="$state->isFinished()" :winner-mark="$state->winner?->value" data-testid="participant-x" />
                <x-design-system.participant-card label="Jogador O" mark="O" :wins="$score['o']" :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::O" :finished="$state->isFinished()" :winner-mark="$state->winner?->value" data-testid="participant-o" />
            </div>
            <p data-testid="score-draws" class="rounded-ds-control border border-ds-border bg-ds-surface-muted px-2 py-1 text-center text-xs font-bold text-ds-text-secondary">Empates: <span class="font-black text-ds-text">{{ $score['draws'] }}</span></p>
            <x-design-system.match-actions data-testid="match-actions">
                <button type="button" x-ref="restartButton" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Reiniciar partida</button>
                @if ($publicEntry)
                    <button type="button" x-on:click="$refs.newMatchDialog.showModal()" data-testid="new-match-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">
                        Nova partida
                    </button>
                @endif
            </x-design-system.match-actions>
            <p data-testid="move-count" class="text-center text-xs font-semibold text-ds-text-muted">Peças no tabuleiro: {{ count(array_filter($board)) }}</p>
        </div>
    </section>

    @if ($publicEntry)
        <dialog x-ref="newMatchDialog" data-testid="new-match-dialog" aria-modal="true" aria-labelledby="new-match-title" aria-describedby="new-match-description" class="ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-4 text-ds-text shadow-ds-elevated sm:p-5">
            <h2 id="new-match-title" class="text-xl font-black">Criar nova partida?</h2>
            <p id="new-match-description" class="mt-2 text-sm leading-relaxed text-ds-text-secondary">O tabuleiro e o placar atuais serão deixados para trás. A próxima rodada mantém o placar; uma nova partida começa do zero.</p>
            <div class="mt-4 grid grid-cols-2 gap-2.5">
                <button type="button" x-on:click="$refs.newMatchDialog.close()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Cancelar</button>
                <button type="button" wire:click="newMatch" wire:loading.attr="disabled" wire:target="newMatch" data-testid="confirm-new-match" class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Criar nova partida</button>
            </div>
        </dialog>
    @endif

    @if ($state->isFinished())
        <x-design-system.result-dialog
            :title="'Jogador '.strtoupper($state->winner->value).' venceu!'"
            eyebrow="Vitória da rodada" description="Três em linha! O placar foi atualizado."
            :winner-mark="$state->winner->value" :score-x="$score['x']" :score-o="$score['o']" :draws="$score['draws']"
            x-ref="resultDialog" x-on:close="restoreRoundResultFocus()" data-testid="result-dialog"
        >
            <button type="button" wire:click="restart" wire:loading.attr="disabled" x-on:click="closeRoundResult()" autofocus class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Próxima rodada</button>
            <button type="button" x-on:click="closeRoundResult()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Fechar</button>
        </x-design-system.result-dialog>
    @endif
</div>
