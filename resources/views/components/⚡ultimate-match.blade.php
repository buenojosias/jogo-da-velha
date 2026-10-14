<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\UltimateRules;
use App\Http\ClassicRoundActions;
use App\Http\SessionMatchStore;
use App\Http\UltimateMatchManager;
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
        abort_unless(app(UltimateMatchManager::class)->isActive($this->matchId), 404);
        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== 'ultimate', 404);

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
    public function availableBoards(): array
    {
        return app(UltimateRules::class)->availableBoards($this->matchState);
    }

    /** @return array{int, int, int}|null */
    #[Computed]
    public function globalWinningLine(): ?array
    {
        return app(UltimateRules::class)->globalWinningLine($this->matchState);
    }

    public function place(mixed $board, mixed $cell): void
    {
        $state = $this->matchState;
        $result = app(UltimateRules::class)->apply($state, new GameAction(
            $state->currentPlayer ?? PlayerMark::X,
            'place',
            ['board' => $board, 'cell' => $cell],
        ));
        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'wrong_board' => 'Jogue no subtabuleiro obrigatório indicado.',
                'board_closed' => 'Este subtabuleiro já foi encerrado.',
                'cell_occupied' => 'Esta casa já está ocupada.',
                'game_finished' => 'A rodada terminou. Inicie a próxima rodada.',
                default => 'Escolha um subtabuleiro e uma casa válidos.',
            };

            return;
        }
        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->availableBoards, $this->globalWinningLine);
        $nextAvailable = app(UltimateRules::class)->availableBoards($result->state);
        $this->dispatch('ultimate-state-updated', focus: $result->state->data['target_board'] ?? ($nextAvailable[0] ?? $board));
        if ($result->state->isFinished()) {
            $this->dispatch('ultimate-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart($this->matchId, $this->matchState, app(UltimateRules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->availableBoards, $this->globalWinningLine);
        $this->dispatch('ultimate-state-updated', focus: 0);
    }

    public function newMatch(): void
    {
        $this->matchState;
        $this->matchId = app(UltimateMatchManager::class)->createNewActive();
        $this->redirectRoute('ultimate');
    }
};
?>

@php
    $state = $this->matchState;
    $rules = app(UltimateRules::class);
    $boards = $rules->boards($state);
    $targetBoard = $rules->targetBoard($state);
    $availableBoards = $this->availableBoards;
    $globalWinningLine = $this->globalWinningLine;
    $score = $this->score;
    $initialFocus = $targetBoard ?? ($availableBoards[0] ?? 0);
@endphp

<div class="experimental-match mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-4 px-4 py-4 text-ds-text sm:px-6 sm:py-6"
    x-data="{
        focusedBoard: {{ $initialFocus }},
        resultOrigin: null,
        openResult() { this.$nextTick(() => { const dialog = this.$refs.resultDialog; if (!dialog || dialog.open) return; this.resultOrigin = document.activeElement; dialog.showModal(); }); },
        closeResult() { this.$refs.resultDialog?.close(); },
        restoreFocus() { const origin = this.resultOrigin; requestAnimationFrame(() => (origin instanceof HTMLElement && origin.isConnected && origin.tabIndex >= 0 && !origin.matches(':disabled') ? origin : this.$refs.restartButton)?.focus()); },
        focusPlayableBoard(boardIndex) {
            this.$nextTick(() => {
                const region = this.$refs.focusRegion;
                const board = region?.querySelector(`[data-testid='ultimate-focus-board-${boardIndex}']`);
                const firstCell = board?.querySelector('button:not(:disabled)');
                if (!firstCell || this.$refs.resultDialog?.open) return;
                firstCell.focus({ preventScroll: true });
                const bounds = region.getBoundingClientRect();
                if (bounds.top < 0 || bounds.bottom > window.innerHeight) {
                    region.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
                }
            });
        },
        selectBoard(boardIndex, available) {
            this.focusedBoard = boardIndex;
            if (available) this.focusPlayableBoard(boardIndex);
        },
        updateBoard(boardIndex) {
            this.focusedBoard = boardIndex;
            this.focusPlayableBoard(boardIndex);
        },
        returnToOverview() {
            const overview = this.$refs.overview?.querySelector(`[data-testid='ultimate-sub-board-${this.focusedBoard}']`);
            if (!overview) return;
            overview.focus({ preventScroll: true });
            overview.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'nearest' });
        }
    }"
    x-on:ultimate-state-updated.window="updateBoard($event.detail.focus)"
    x-on:ultimate-round-finished.window="openResult()"
>
    <x-design-system.match-header modality="Ultimate" title="Jogo da Velha" description="Conquiste três subtabuleiros em linha." data-testid="match-header">
        <x-slot:actions><x-design-system.theme-selector data-testid="theme-selector" /></x-slot:actions>
    </x-design-system.match-header>

    <section aria-label="Partida local" class="experimental-match-layout grid items-start gap-4 md:grid-cols-[minmax(0,1fr)_17rem] md:gap-5">
        <div data-testid="game-region" class="experimental-game-region min-w-0 rounded-ds-card border border-t-4 border-ds-border border-t-ds-primary bg-ds-surface p-3 shadow-ds-elevated sm:p-5">
            <x-design-system.match-status :tone="$state->status === MatchStatus::Completed ? 'win' : ($state->status === MatchStatus::Drawn ? 'draw' : 'turn')" data-testid="match-status">
                @if ($state->status === MatchStatus::Completed)
                    Jogador {{ strtoupper($state->winner->value) }} venceu!
                @elseif ($state->status === MatchStatus::Drawn)
                    Rodada empatada!
                @else
                    Vez do jogador {{ strtoupper($state->currentPlayer->value) }}
                @endif
            </x-design-system.match-status>

            <p data-testid="target-board-status" class="mt-2 text-center text-sm font-bold text-ds-text-secondary" role="status">
                @if ($state->isFinished())
                    Rodada encerrada.
                @elseif ($targetBoard === null)
                    Escolha qualquer subtabuleiro disponível.
                @else
                    Subtabuleiro obrigatório: {{ $targetBoard + 1 }}.
                @endif
            </p>

            <div class="mt-3 grid min-w-0 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,17rem)]">
                <section aria-label="Visão geral dos nove subtabuleiros" class="min-w-0">
                    <h2 class="text-center text-sm font-extrabold">Visão geral</h2>
                    <div data-testid="ultimate-overview" x-ref="overview" class="mx-auto mt-2 grid w-full max-w-[35rem] grid-cols-3 gap-1.5 sm:gap-2">
                        @foreach ($boards as $boardIndex => $board)
                            @php
                                $result = $rules->subBoardResult($state, $boardIndex);
                                $available = in_array($boardIndex, $availableBoards, true);
                                $isWinningBoard = in_array($boardIndex, $globalWinningLine ?? [], true);
                                $resultLabel = $result === 'draw' ? 'empatado' : ($result === null ? 'em andamento' : 'conquistado por '.strtoupper($result));
                            @endphp
                            <button type="button" wire:key="ultimate-overview-{{ $boardIndex }}" data-testid="ultimate-sub-board-{{ $boardIndex }}"
                                data-result="{{ $result ?? '' }}" data-available="{{ $available ? 'true' : 'false' }}" data-target="{{ $targetBoard === $boardIndex ? 'true' : 'false' }}"
                                aria-label="Subtabuleiro {{ $boardIndex + 1 }}, {{ $resultLabel }}{{ $available ? ', disponível para jogar' : '' }}"
                                x-on:click="selectBoard({{ $boardIndex }}, {{ $available ? 'true' : 'false' }})"
                                x-bind:aria-pressed="focusedBoard === {{ $boardIndex }}"
                                x-bind:class="focusedBoard === {{ $boardIndex }} ? 'ring-2 ring-ds-focus' : ''"
                                class="relative min-w-0 rounded-ds-control border-2 p-1 text-ds-text focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus sm:p-1.5 {{ $isWinningBoard ? 'border-ds-success bg-ds-hover' : ($available ? 'border-ds-primary bg-ds-surface' : 'border-ds-border bg-ds-surface-muted') }}">
                                <span class="grid aspect-square grid-cols-3 grid-rows-3 gap-0.5" aria-hidden="true">
                                    @foreach ($board as $cellIndex => $mark)
                                        <span class="flex aspect-square min-h-0 min-w-0 items-center justify-center rounded-sm border border-ds-border bg-ds-elevated text-[0.65rem] font-black leading-none sm:text-sm {{ $mark === 'x' ? 'text-ds-x' : ($mark === 'o' ? 'text-ds-o' : '') }}">{{ strtoupper($mark) }}</span>
                                    @endforeach
                                </span>
                                <span class="mt-0.5 block truncate text-center text-[0.65rem] font-extrabold sm:text-xs">{{ $boardIndex + 1 }} · {{ $result === 'draw' ? 'Empate' : ($result ? 'Venceu '.strtoupper($result) : ($available ? 'Livre' : 'Aguarda')) }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section aria-label="Subtabuleiro ampliado" data-testid="ultimate-focus-region" x-ref="focusRegion" x-on:keydown.escape.stop="returnToOverview()" aria-keyshortcuts="Escape" class="min-w-0 rounded-ds-card border border-ds-border bg-ds-surface-muted p-2.5 sm:p-3">
                    <h2 class="text-center text-sm font-extrabold">Jogue no subtabuleiro ampliado</h2>
                    @foreach ($boards as $boardIndex => $board)
                        @php $available = in_array($boardIndex, $availableBoards, true); $localLine = $rules->localWinningLine($state, $boardIndex); @endphp
                        <div wire:key="ultimate-focus-{{ $boardIndex }}" x-show="focusedBoard === {{ $boardIndex }}" x-cloak data-testid="ultimate-focus-board-{{ $boardIndex }}" class="mt-2">
                            <p class="mb-2 text-center text-xs font-bold text-ds-text-secondary">Subtabuleiro {{ $boardIndex + 1 }}{{ $available ? ' · disponível' : ' · indisponível' }}</p>
                            <div role="group" aria-label="Casas do subtabuleiro {{ $boardIndex + 1 }}" class="mx-auto grid w-full max-w-[20rem] grid-cols-3 gap-1.5">
                                @foreach ($board as $cellIndex => $mark)
                                    <button type="button" wire:key="ultimate-cell-{{ $boardIndex }}-{{ $cellIndex }}" data-testid="ultimate-cell-{{ $boardIndex }}-{{ $cellIndex }}" data-mark="{{ $mark }}"
                                        aria-label="Subtabuleiro {{ $boardIndex + 1 }}, casa {{ $cellIndex + 1 }}{{ $mark ? ', '.strtoupper($mark) : ', vazia' }}"
                                        wire:click="place({{ $boardIndex }}, {{ $cellIndex }})" wire:loading.attr="disabled"
                                        @disabled(! $available || $mark !== '')
                                        class="aspect-square min-h-12 rounded-ds-control border-2 bg-ds-elevated text-3xl font-black shadow-ds-card enabled:hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed {{ in_array($cellIndex, $localLine ?? [], true) ? 'border-ds-success ring-2 ring-ds-success' : 'border-ds-board-border' }} {{ $mark === 'x' ? 'text-ds-x' : 'text-ds-o' }}">{{ strtoupper($mark) }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p class="mt-2 text-center text-xs text-ds-text-secondary">Escolha na visão geral. Pressione Escape para voltar a ela.</p>
                </section>
            </div>

            @if ($rejectionMessage)<p data-testid="rejection-message" role="alert" class="mt-3 rounded-ds-control border border-ds-warning bg-ds-warning-surface p-3 text-sm text-ds-warning">{{ $rejectionMessage }}</p>@endif
            <p wire:loading role="status" class="mt-2 text-sm text-ds-text-secondary">Processando jogada…</p>
        </div>

        <div class="experimental-match-sidebar flex min-w-0 flex-col gap-3">
            <div class="experimental-participants grid grid-cols-2 gap-2 md:grid-cols-1" aria-label="Participantes">
                <x-design-system.participant-card label="Jogador X" mark="X" :wins="$score['x']" :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::X" :finished="$state->isFinished()" :winner-mark="$state->winner?->value" data-testid="participant-x" />
                <x-design-system.participant-card label="Jogador O" mark="O" :wins="$score['o']" :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::O" :finished="$state->isFinished()" :winner-mark="$state->winner?->value" data-testid="participant-o" />
            </div>
            <p data-testid="score-draws" class="rounded-ds-control border border-ds-border bg-ds-surface-muted px-2 py-1 text-center text-xs font-bold text-ds-text-secondary">Empates: <span class="font-black text-ds-text">{{ $score['draws'] }}</span></p>
            <x-design-system.match-actions data-testid="match-actions">
                <button type="button" x-ref="restartButton" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Nova rodada</button>
                <button type="button" x-on:click="$refs.newMatchDialog.showModal()" data-testid="new-match-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Reiniciar partida</button>
                <livewire:exit-match :match-id="$matchId" modality="ultimate" />
            </x-design-system.match-actions>
        </div>
    </section>

    <dialog x-ref="newMatchDialog" data-testid="new-match-dialog" aria-modal="true" aria-labelledby="ultimate-new-match-title" class="ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-4 text-ds-text shadow-ds-elevated sm:p-5">
        <h2 id="ultimate-new-match-title" class="text-xl font-black">Reiniciar partida?</h2>
        <p class="mt-2 text-sm text-ds-text-secondary">O tabuleiro e o placar serão zerados. Você começará uma nova partida. Deseja continuar?</p>
        <div class="mt-4 grid grid-cols-2 gap-2.5">
            <button type="button" x-on:click="$refs.newMatchDialog.close()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text">Cancelar</button>
            <button type="button" wire:click="newMatch" wire:loading.attr="disabled" wire:target="newMatch" data-testid="confirm-new-match" class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary">Reiniciar partida</button>
        </div>
    </dialog>

    @if ($state->isFinished())
        <x-design-system.result-dialog :title="$state->status === MatchStatus::Drawn ? 'Rodada empatada!' : 'Jogador '.strtoupper($state->winner->value).' venceu!'" :eyebrow="$state->status === MatchStatus::Drawn ? 'Rodada empatada' : 'Vitória na rodada'" :description="$state->status === MatchStatus::Drawn ? 'Todos os subtabuleiros foram encerrados.' : 'Três subtabuleiros conquistados em linha.'" :winner-mark="$state->winner?->value" :score-x="$score['x']" :score-o="$score['o']" :draws="$score['draws']" x-ref="resultDialog" x-on:close="restoreFocus()" data-testid="result-dialog">
            <button type="button" wire:click="restart" wire:loading.attr="disabled" x-on:click="closeResult()" autofocus class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary">Próxima rodada</button>
            <button type="button" x-on:click="closeResult()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text">Fechar</button>
        </x-design-system.result-dialog>
    @endif
</div>
