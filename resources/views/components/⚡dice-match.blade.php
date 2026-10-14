<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\DiceRules;
use App\Http\ClassicRoundActions;
use App\Http\DiceMatchManager;
use App\Http\SessionMatchStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $matchId;

    public ?string $rejectionMessage = null;

    public ?string $rollNotice = null;

    public function mount(string $matchId): void
    {
        $this->matchId = $matchId;
        $this->matchState();
    }

    #[Computed]
    public function matchState(): MatchState
    {
        abort_unless(app(DiceMatchManager::class)->isActive($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== 'dice', 404);

        return $state;
    }

    /** @return array{x: int, o: int, draws: int} */
    #[Computed]
    public function score(): array
    {
        return app(SessionMatchStore::class)->score($this->matchId);
    }

    /** @return array{board: int, cells: array{int, int, int}}|null */
    #[Computed]
    public function winningLine(): ?array
    {
        return app(DiceRules::class)->winningLine($this->matchState);
    }

    public function roll(): void
    {
        $state = $this->matchState;
        $result = app(DiceRules::class)->apply($state, new GameAction($state->currentPlayer ?? PlayerMark::X, 'roll'));
        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'roll_pending' => 'Escolha uma casa na coluna sorteada antes de rolar novamente.',
                'game_finished' => 'A rodada terminou. Inicie a próxima rodada.',
                default => 'Não foi possível rolar o dado.',
            };

            return;
        }

        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        $roll = $result->state->data['last_roll'];
        $this->rollNotice = $result->state->data['pending_roll'] === null
            ? 'Coluna '.$roll.' cheia! Jogador '.strtoupper($state->currentPlayer->value).' perdeu a vez.'
            : null;
        unset($this->matchState, $this->score, $this->winningLine);
    }

    public function place(mixed $boardIndex, mixed $cellIndex): void
    {
        $state = $this->matchState;
        $result = app(DiceRules::class)->apply($state, new GameAction($state->currentPlayer ?? PlayerMark::X,
            'place', ['board' => $boardIndex, 'cell' => $cellIndex]));
        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'roll_required' => 'Role o dado antes de escolher uma casa.',
                'wrong_column' => 'Escolha uma casa na coluna indicada pelo dado.',
                'cell_occupied' => 'Esta casa já está ocupada.',
                'invalid_board', 'invalid_cell' => 'Escolha uma casa válida.',
                'game_finished' => 'A rodada terminou. Inicie a próxima rodada.',
                default => 'Não foi possível realizar esta jogada.',
            };

            return;
        }

        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        $this->rollNotice = null;
        unset($this->matchState, $this->score, $this->winningLine);
        if ($result->state->isFinished()) {
            $this->dispatch('dice-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart($this->matchId, $this->matchState, app(DiceRules::class));
        $this->rejectionMessage = null;
        $this->rollNotice = null;
        unset($this->matchState, $this->score, $this->winningLine);
    }

    public function newMatch(): void
    {
        $this->matchState;
        $this->matchId = app(DiceMatchManager::class)->createNewActive();
        $this->rejectionMessage = null;
        $this->rollNotice = null;
        unset($this->matchState, $this->score, $this->winningLine);
        $this->redirectRoute('dice');
    }
};
?>

@php
    $state = $this->matchState;
    $score = $this->score;
    $winningLine = $this->winningLine;
    $pendingRoll = $state->data['pending_roll'];
    $lastRoll = $state->data['last_roll'];
    $allowedBoard = $pendingRoll === null ? null : intdiv($pendingRoll - 1, 3);
    $allowedColumn = $pendingRoll === null ? null : ($pendingRoll - 1) % 3;
    $diceFaces = ['⚀', '⚁', '⚂', '⚃', '⚄', '⚅'];
@endphp

<div
    class="dice-game experimental-match mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-4 px-4 py-4 text-ds-text sm:px-6 sm:py-6"
    x-data="{
        resultOrigin: null,
        openRoundResult() {
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
    x-on:dice-round-finished.window="openRoundResult()"
>
    <x-design-system.match-header
        modality="Dados da Velha"
        title="Jogo da Velha"
        description="Partida local: Jogador X e Jogador O no mesmo dispositivo."
        data-testid="match-header"
    >
        <x-slot:actions>
            <x-design-system.theme-selector data-testid="theme-selector" />
        </x-slot:actions>
    </x-design-system.match-header>

    <section aria-label="Partida local" class="experimental-match-layout grid items-start gap-4 md:grid-cols-[minmax(0,1fr)_15rem] md:gap-5 xl:grid-cols-[minmax(0,1fr)_17rem]">
        <div data-testid="game-region" class="experimental-game-region min-w-0 rounded-ds-card border border-t-4 border-ds-border border-t-ds-primary bg-ds-surface p-3 shadow-ds-elevated sm:p-5">
            <x-design-system.match-status
                :tone="$state->status === MatchStatus::Completed ? 'win' : ($state->status === MatchStatus::Drawn ? 'draw' : 'turn')"
                data-testid="match-status"
            >
                @if ($state->status === MatchStatus::Completed)
                    Jogador {{ strtoupper($state->winner->value) }} venceu!
                @elseif ($state->status === MatchStatus::Drawn)
                    Empate nos dois tabuleiros!
                @else
                    Vez do jogador {{ strtoupper($state->currentPlayer->value) }}
                @endif
            </x-design-system.match-status>

            <div class="mx-auto mt-3 flex max-w-[30rem] items-center justify-center gap-3 rounded-ds-control border border-ds-border bg-ds-surface-muted p-2.5">
                <span data-testid="dice-face" data-face="{{ $lastRoll ?? '' }}" role="img" aria-label="{{ $lastRoll === null ? 'Dado ainda não rolado' : 'Dado: face '.$lastRoll }}" class="flex size-14 shrink-0 items-center justify-center rounded-ds-control border-2 border-ds-primary bg-ds-elevated text-4xl text-ds-primary shadow-ds-card" aria-live="polite">{{ $lastRoll === null ? '🎲' : $diceFaces[$lastRoll - 1] }}</span>
                <button type="button" wire:click="roll" wire:loading.attr="disabled" @disabled($pendingRoll !== null || $state->isFinished()) data-testid="roll-button" class="min-h-12 rounded-ds-control bg-ds-primary px-4 py-2 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed disabled:opacity-60">Rolar dado</button>
            </div>

            @if (! $state->isFinished())
                <p data-testid="dice-instruction" class="mt-2 text-center text-sm font-semibold text-ds-text-secondary">
                    @if ($pendingRoll === null)
                        Role o dado para descobrir o tabuleiro e a coluna da jogada.
                    @else
                        Dado {{ $pendingRoll }}: escolha uma casa vazia na coluna {{ $allowedColumn + 1 }} do tabuleiro {{ $allowedBoard === 0 ? 'esquerdo' : 'direito' }}.
                    @endif
                </p>
            @endif

            @if ($rollNotice)
                <p data-testid="dice-roll-notice" role="status" class="mt-2 rounded-ds-control border border-ds-warning bg-ds-warning-surface px-3 py-2 text-center text-sm font-bold text-ds-warning">{{ $rollNotice }}</p>
            @endif

            <div class="mt-3 grid grid-cols-1 items-start gap-3 min-[38rem]:grid-cols-2">
                @foreach (['esquerdo', 'direito'] as $boardIndex => $side)
                    <section class="min-w-0 rounded-ds-card border border-ds-border bg-ds-surface-muted p-2.5 sm:p-3" aria-label="Tabuleiro {{ $side }}">
                        <h2 class="text-center text-sm font-extrabold text-ds-text">Tabuleiro {{ ucfirst($side) }}</h2>
                        <div class="mx-auto max-w-[18rem]">
                            <x-design-system.game-board
                                :cells="$state->data['boards'][$boardIndex]"
                                :columns="3"
                                label="Tabuleiro {{ $side }} 3 por 3"
                                :highlighted-cells="$winningLine !== null && $winningLine['board'] === $boardIndex ? $winningLine['cells'] : []"
                                :unavailable-cells="array_keys(array_filter($state->data['boards'][$boardIndex]))"
                                :interaction="'dice'"
                                :board-index="$boardIndex"
                                :allowed-column="$allowedBoard === $boardIndex ? $allowedColumn : null"
                                data-testid="dice-board-{{ $side }}"
                            />
                        </div>
                    </section>
                @endforeach
            </div>

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
                <button type="button" x-ref="restartButton" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Nova rodada</button>
                <button type="button" x-on:click="$refs.newMatchDialog.showModal()" data-testid="new-match-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Reiniciar partida</button>
                <livewire:exit-match :match-id="$matchId" modality="dice" />
            </x-design-system.match-actions>
        </div>
    </section>

    <dialog x-ref="newMatchDialog" data-testid="new-match-dialog" aria-modal="true" aria-labelledby="new-match-title" aria-describedby="new-match-description" class="ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-4 text-ds-text shadow-ds-elevated sm:p-5">
        <h2 id="new-match-title" class="text-xl font-black">Reiniciar partida?</h2>
        <p id="new-match-description" class="mt-2 text-sm leading-relaxed text-ds-text-secondary">O tabuleiro e o placar serão zerados. Você começará uma nova partida. Deseja continuar?</p>
        <div class="mt-4 grid grid-cols-2 gap-2.5">
            <button type="button" x-on:click="$refs.newMatchDialog.close()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Cancelar</button>
            <button type="button" wire:click="newMatch" wire:loading.attr="disabled" wire:target="newMatch" data-testid="confirm-new-match" class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Reiniciar partida</button>
        </div>
    </dialog>

    @if ($state->isFinished())
        <x-design-system.result-dialog
            :title="$state->status === MatchStatus::Drawn ? 'Rodada empatada!' : 'Jogador '.strtoupper($state->winner->value).' venceu!'"
            :eyebrow="$state->status === MatchStatus::Drawn ? 'Rodada empatada' : 'Vitória na rodada'"
            :description="$state->status === MatchStatus::Drawn ? 'Os dois tabuleiros ficaram completos sem vencedor.' : 'Três em linha! O placar foi atualizado.'"
            :winner-mark="$state->winner?->value"
            :score-x="$score['x']"
            :score-o="$score['o']"
            :draws="$score['draws']"
            x-ref="resultDialog"
            x-on:close="restoreRoundResultFocus()"
            data-testid="result-dialog"
        >
            <button type="button" wire:click="restart" wire:loading.attr="disabled" x-on:click="closeRoundResult()" autofocus class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Próxima rodada</button>
            <button type="button" x-on:click="closeRoundResult()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Fechar</button>
        </x-design-system.result-dialog>
    @endif
</div>
