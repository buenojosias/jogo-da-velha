@props([
    'modality',
    'state',
    'columns',
    'boardLabel',
    'boardClass' => '',
    'winningCells',
    'score',
    'rejectionMessage',
    'publicEntry',
    'roundFinishedEvent',
    'instruction' => null,
    'resultDescription' => 'Três em linha! O placar foi atualizado.',
    'drawDescription' => 'Ninguém completou uma linha nesta rodada.',
])
@use('App\Games\{MatchStatus, PlayerMark}')
@php($unavailableCells = $state->isFinished() ? array_keys($state->data['board']) : array_keys(array_filter($state->data['board'])))

<div
    class="experimental-match mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-4 px-4 py-4 text-ds-text sm:px-6 sm:py-6"
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
        closeRoundResult() {
            this.$refs.resultDialog?.close();
        },
        restoreRoundResultFocus() {
            const origin = this.resultOrigin;
            const canReturnToOrigin = origin instanceof HTMLElement && origin.isConnected && origin.tabIndex >= 0 && !origin.matches(':disabled');
            const target = canReturnToOrigin ? origin : this.$refs.restartButton;
            requestAnimationFrame(() => target?.focus());
        }
    }"
    x-on:{{ $roundFinishedEvent }}.window="openRoundResult()"
>
    <x-design-system.match-header
        modality="{{ $modality }}"
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
                    Empate!
                @else
                    Vez do jogador {{ strtoupper($state->currentPlayer->value) }}
                @endif
            </x-design-system.match-status>

            @if ($instruction && ! $state->isFinished())
                <p data-testid="game-instruction" class="mx-auto mt-2 max-w-[28rem] text-center text-sm font-semibold text-ds-text-secondary">{{ $instruction }}</p>
            @endif

            <x-design-system.game-board
                :cells="$state->data['board']"
                :columns="$columns"
                label="{{ $boardLabel }}"
                :highlighted-cells="$winningCells"
                :unavailable-cells="$unavailableCells"
                highlight-label="casa vencedora"
                data-testid="classic-board"
                class="{{ $boardClass }}"
            />

            @if ($rejectionMessage)
                <p data-testid="rejection-message" role="alert" class="mt-3 rounded-ds-control border border-ds-warning bg-ds-warning-surface p-3 text-sm text-ds-warning">{{ $rejectionMessage }}</p>
            @endif

            <p wire:loading role="status" class="mt-3 text-sm text-ds-text-secondary">Processando jogada…</p>
        </div>

        <div class="experimental-match-sidebar flex min-w-0 flex-col gap-3">
            <div class="experimental-participants grid grid-cols-2 gap-2.5 md:grid-cols-1" aria-label="Participantes">
                <x-design-system.participant-card
                    label="Jogador X"
                    mark="X"
                    :wins="$score['x']"
                    :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::X"
                    data-testid="participant-x"
                />
                <x-design-system.participant-card
                    label="Jogador O"
                    mark="O"
                    :wins="$score['o']"
                    :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::O"
                    data-testid="participant-o"
                />
            </div>

            <p data-testid="score-draws" class="rounded-ds-control border border-ds-border bg-ds-surface-muted px-2 py-1 text-center text-xs font-bold text-ds-text-secondary">Empates: <span class="font-black text-ds-text">{{ $score['draws'] }}</span></p>

            <x-design-system.match-actions data-testid="match-actions">
                <button type="button" x-ref="restartButton" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60 motion-safe:transition-colors motion-safe:duration-200 motion-reduce:transition-none">
                    Reiniciar partida
                </button>
                @if ($publicEntry)
                    <button type="button" x-on:click="$refs.newMatchDialog.showModal()" data-testid="new-match-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">
                        Nova partida
                    </button>
                @endif
            </x-design-system.match-actions>

            <p data-testid="move-count" class="text-center text-xs font-semibold text-ds-text-muted">Jogadas realizadas: {{ count(array_filter($state->data['board'], fn (string $cell): bool => $cell !== '')) }}</p>
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
            :title="$state->status === MatchStatus::Drawn ? 'Empate!' : 'Jogador '.strtoupper($state->winner->value).' venceu!'"
            :eyebrow="$state->status === MatchStatus::Drawn ? 'Rodada empatada' : 'Vitória da rodada'"
            :description="$state->status === MatchStatus::Drawn ? $drawDescription : $resultDescription"
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
