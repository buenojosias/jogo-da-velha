<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\CrazyRules;
use App\Http\ClassicRoundActions;
use App\Http\CrazyMatchManager;
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
        abort_unless(app(CrazyMatchManager::class)->isActive($this->matchId), 404);
        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== 'crazy', 404);

        return $state;
    }

    /** @return array{x: int, o: int, draws: int} */
    #[Computed]
    public function score(): array
    {
        return app(SessionMatchStore::class)->score($this->matchId);
    }

    /** @return array{int, int, int}|null */
    #[Computed]
    public function winningLine(): ?array
    {
        return app(CrazyRules::class)->winningLine($this->matchState);
    }

    public function place(mixed $piece, mixed $cell): void
    {
        $state = $this->matchState;
        $result = app(CrazyRules::class)->apply($state, new GameAction($state->currentPlayer ?? PlayerMark::X, 'place', ['piece' => $piece, 'cell' => $cell]));
        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'piece_not_larger' => 'Escolha uma peça maior que a peça visível nesta casa.',
                'piece_used' => 'Esta peça já foi usada.',
                'not_your_piece' => 'Selecione uma peça do jogador da vez.',
                'game_finished' => 'A rodada terminou. Inicie a próxima rodada.',
                default => 'Escolha uma peça disponível e uma casa válida.',
            };

            return;
        }
        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningLine);
        $this->dispatch('crazy-selection-clear');
        if ($result->state->isFinished()) {
            $this->dispatch('crazy-round-finished');
        }
    }

    public function restart(): void
    {
        app(ClassicRoundActions::class)->restart($this->matchId, $this->matchState, app(CrazyRules::class));
        $this->rejectionMessage = null;
        unset($this->matchState, $this->score, $this->winningLine);
        $this->dispatch('crazy-selection-clear');
    }

    public function newMatch(): void
    {
        $this->matchState;
        $this->matchId = app(CrazyMatchManager::class)->createNewActive();
        $this->redirectRoute('crazy');
    }
};
?>

@php
    $state = $this->matchState;
    $rules = app(CrazyRules::class);
    $score = $this->score;
    $line = $this->winningLine;
    $stacks = $rules->stacks($state);
    $sizeLabels = [1 => 'Pequena', 2 => 'Média', 3 => 'Grande'];
@endphp

<div class="experimental-match crazy-game mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-4 px-4 py-4 text-ds-text sm:px-6 sm:py-6"
    x-data="{
        selectedPiece: null,
        resultOrigin: null,
        openResult() { this.$nextTick(() => { const dialog = this.$refs.resultDialog; if (!dialog || dialog.open) return; this.resultOrigin = document.activeElement; dialog.showModal(); }); },
        closeResult() { this.$refs.resultDialog?.close(); },
        restoreFocus() { const origin = this.resultOrigin; requestAnimationFrame(() => (origin instanceof HTMLElement && origin.isConnected && origin.tabIndex >= 0 && !origin.matches(':disabled') ? origin : this.$refs.restartButton)?.focus()); }
    }"
    x-on:crazy-selection-clear.window="selectedPiece = null"
    x-on:crazy-round-finished.window="openResult()"
>
    <x-design-system.match-header modality="Conquista Maluca" title="Jogo da Velha" description="Escolha uma peça e depois uma casa." data-testid="match-header">
        <x-slot:actions><x-design-system.theme-selector data-testid="theme-selector" /></x-slot:actions>
    </x-design-system.match-header>

    <section aria-label="Partida local" class="experimental-match-layout grid items-start gap-4 md:grid-cols-[minmax(0,1fr)_17rem] md:gap-5">
        <div data-testid="game-region" class="experimental-game-region min-w-0 rounded-ds-card border border-t-4 border-ds-border border-t-ds-primary bg-ds-surface p-3 shadow-ds-elevated sm:p-5">
            <x-design-system.match-status :tone="$state->status === MatchStatus::Completed ? 'win' : ($state->status === MatchStatus::Drawn ? 'draw' : 'turn')" data-testid="match-status">
                @if ($state->status === MatchStatus::Completed)
                    Jogador {{ strtoupper($state->winner->value) }} venceu!
                @elseif ($state->status === MatchStatus::Drawn)
                    Empate!
                @else
                    Vez do jogador {{ strtoupper($state->currentPlayer->value) }}
                @endif
            </x-design-system.match-status>
            <p class="crazy-instruction mt-2 text-center text-sm font-semibold text-ds-text-secondary">Peça maior cobre menor. A linha vence se o rival não puder cobri-la.</p>

            <div data-testid="crazy-board" role="group" aria-label="Tabuleiro 3 por 3" class="crazy-board mx-auto mt-3 grid w-full max-w-[25rem] grid-cols-3 gap-2">
                @foreach ($stacks as $index => $stack)
                    @php $top = $stack === [] ? null : $stack[array_key_last($stack)]; @endphp
                    <button type="button" data-testid="crazy-cell-{{ $index }}" data-cell="{{ $index }}" data-top="{{ $top ?? '' }}" data-stack-count="{{ count($stack) }}"
                        aria-label="Casa {{ $index + 1 }}{{ $top ? ', peça '.strtoupper($top[0]).' '.strtolower($sizeLabels[(int) $top[2]]).', '.count($stack).' na pilha' : ', vazia' }}"
                        x-on:click="if (selectedPiece) $wire.place(selectedPiece, {{ $index }})"
                        wire:loading.attr="disabled" @disabled($state->isFinished())
                        class="crazy-cell relative aspect-square min-h-16 rounded-ds-control border-2 bg-ds-elevated transition-colors hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed {{ in_array($index, $line ?? [], true) ? 'border-ds-success ring-4 ring-ds-success' : 'border-ds-board-border' }}">
                        @if ($top)
                            <span class="mx-auto flex shrink-0 items-center justify-center rounded-full border-2 border-current bg-ds-hover font-black shadow-ds-card {{ $top[0] === 'x' ? 'text-ds-x' : 'text-ds-o' }} {{ (int) $top[2] === 1 ? 'size-9 text-lg' : ((int) $top[2] === 2 ? 'size-14 text-2xl' : 'size-[min(5rem,21vw)] text-3xl') }}">{{ strtoupper($top[0]) }}</span>
                            @if (count($stack) > 1)<span class="absolute bottom-1 right-1 rounded-full bg-ds-surface px-1.5 text-xs font-bold text-ds-text">{{ count($stack) }}</span>@endif
                        @endif
                    </button>
                @endforeach
            </div>
            @if ($rejectionMessage)<p data-testid="rejection-message" role="alert" class="mt-3 rounded-ds-control border border-ds-warning bg-ds-warning-surface p-3 text-sm text-ds-warning">{{ $rejectionMessage }}</p>@endif
            <p wire:loading role="status" class="mt-2 text-sm text-ds-text-secondary">Processando jogada…</p>
        </div>

        <div class="experimental-match-sidebar flex min-w-0 flex-col gap-3">
            <div class="experimental-participants grid grid-cols-2 gap-2 md:grid-cols-1" aria-label="Participantes">
                <x-design-system.participant-card label="Jogador X" mark="X" :wins="$score['x']" :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::X" :finished="$state->isFinished()" :winner-mark="$state->winner?->value" data-testid="participant-x" />
                <x-design-system.participant-card label="Jogador O" mark="O" :wins="$score['o']" :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::O" :finished="$state->isFinished()" :winner-mark="$state->winner?->value" data-testid="participant-o" />
            </div>
            <p data-testid="score-draws" class="crazy-draw-score rounded-ds-control border border-ds-border bg-ds-surface-muted px-2 py-1 text-center text-xs font-bold text-ds-text-secondary">Empates: <span class="font-black text-ds-text">{{ $score['draws'] }}</span></p>
            <section aria-label="Seletor de peças" data-testid="piece-selector" class="crazy-piece-selector rounded-ds-card border border-ds-border bg-ds-surface p-3 shadow-ds-card">
                <h2 class="text-sm font-extrabold">Peças disponíveis</h2>
                <div class="crazy-inventory-groups">
                    @foreach ([PlayerMark::X, PlayerMark::O] as $player)
                        @php $available = $rules->availablePieces($state, $player); @endphp
                        <div class="crazy-inventory-group mt-2 min-w-0">
                            <p class="text-xs font-bold text-ds-text-secondary">Jogador {{ strtoupper($player->value) }}</p>
                            <div class="mt-1 grid grid-cols-3 gap-1.5">
                                @for ($size = 1; $size <= 3; $size++)
                                    <div class="flex flex-col gap-1">
                                        @for ($copy = 1; $copy <= 3; $copy++)
                                            @php
                                                $piece = $player->value.'-'.$size.'-'.$copy;
                                                $used = ! in_array($piece, $available, true);
                                                $stockState = $used ? 'used' : ($state->isFinished() ? 'finished' : ($state->currentPlayer === $player ? 'available' : 'waiting'));
                                            @endphp
                                            <button type="button" data-testid="piece-{{ $piece }}" data-piece="{{ $piece }}" data-stock-state="{{ $stockState }}"
                                                aria-label="{{ $sizeLabels[$size] }} {{ $copy }} do jogador {{ strtoupper($player->value) }}{{ $used ? ', usada' : ($state->isFinished() ? ', rodada encerrada' : ($state->currentPlayer !== $player ? ', aguardando turno' : ', disponível')) }}"
                                                x-on:click="selectedPiece = selectedPiece === '{{ $piece }}' ? null : '{{ $piece }}'"
                                                x-bind:aria-pressed="selectedPiece === '{{ $piece }}'"
                                                x-bind:class="selectedPiece === '{{ $piece }}' ? 'ring-4 ring-ds-focus' : ''"
                                                @disabled($used || $state->isFinished() || $state->currentPlayer !== $player)
                                                class="relative flex min-h-11 items-center justify-center rounded-ds-control border-2 font-black focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed {{ $stockState === 'used' ? 'border-ds-border bg-ds-surface-muted text-ds-text-muted' : ($stockState === 'available' ? 'border-ds-board-border bg-ds-elevated hover:border-ds-primary hover:bg-ds-hover' : 'border-dashed border-ds-border bg-ds-surface-muted') }} {{ $stockState !== 'used' ? ($player === PlayerMark::X ? 'text-ds-x' : 'text-ds-o') : '' }}">
                                                <span class="shrink-0 rounded-full border-2 border-current text-center leading-7 {{ $size === 1 ? 'size-7 text-xs' : ($size === 2 ? 'size-8 text-sm' : 'size-9 text-base') }}">{{ strtoupper($player->value) }}</span>
                                                @if ($used)<span aria-hidden="true" class="absolute right-0.5 top-0.5 rounded bg-ds-surface px-0.5 text-[0.65rem] font-black leading-none text-ds-text-secondary">✓</span>@endif
                                                @if (! $used && $stockState === 'available')<span x-show="selectedPiece === '{{ $piece }}'" x-cloak aria-hidden="true" class="absolute right-0.5 top-0.5 text-sm leading-none text-ds-text">●</span>@endif
                                            </button>
                                        @endfor
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            <x-design-system.match-actions data-testid="match-actions">
                <button type="button" x-ref="restartButton" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Reiniciar partida</button>
                <button type="button" x-on:click="$refs.newMatchDialog.showModal()" data-testid="new-match-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Nova partida</button>
                <livewire:exit-match :match-id="$matchId" modality="crazy" />
            </x-design-system.match-actions>
        </div>
    </section>

    <dialog x-ref="newMatchDialog" data-testid="new-match-dialog" aria-modal="true" aria-labelledby="crazy-new-match-title" class="ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-4 text-ds-text shadow-ds-elevated sm:p-5">
        <h2 id="crazy-new-match-title" class="text-xl font-black">Criar nova partida?</h2>
        <p class="mt-2 text-sm text-ds-text-secondary">A rodada e o placar atuais serão deixados para trás. Próxima rodada mantém o placar.</p>
        <div class="mt-4 grid grid-cols-2 gap-2.5">
            <button type="button" x-on:click="$refs.newMatchDialog.close()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text">Cancelar</button>
            <button type="button" wire:click="newMatch" wire:loading.attr="disabled" wire:target="newMatch" data-testid="confirm-new-match" class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary">Criar nova partida</button>
        </div>
    </dialog>

    @if ($state->isFinished())
        <x-design-system.result-dialog :title="$state->status === MatchStatus::Drawn ? 'Empate!' : 'Jogador '.strtoupper($state->winner->value).' venceu!'" :eyebrow="$state->status === MatchStatus::Drawn ? 'Rodada empatada' : 'Vitória da rodada'" :description="$state->status === MatchStatus::Drawn ? 'Todas as peças foram usadas.' : 'A linha não pode mais ser coberta.'" :winner-mark="$state->winner?->value" :score-x="$score['x']" :score-o="$score['o']" :draws="$score['draws']" x-ref="resultDialog" x-on:close="restoreFocus()" data-testid="result-dialog">
            <button type="button" wire:click="restart" wire:loading.attr="disabled" x-on:click="closeResult()" autofocus class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary">Próxima rodada</button>
            <button type="button" x-on:click="closeResult()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text">Fechar</button>
        </x-design-system.result-dialog>
    @endif
</div>
