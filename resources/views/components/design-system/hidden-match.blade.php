@props(['finished', 'currentPlayer', 'winner', 'displayCells', 'winningCells', 'score', 'feedbackMessage'])

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
        closeRoundResult() { this.$refs.resultDialog?.close(); },
        restoreRoundResultFocus() {
            const origin = this.resultOrigin;
            const canReturn = origin instanceof HTMLElement && origin.isConnected && origin.tabIndex >= 0 && !origin.matches(':disabled');
            requestAnimationFrame(() => (canReturn ? origin : this.$refs.restartButton)?.focus());
        }
    }"
    x-on:hidden-round-finished.window="openRoundResult()"
>
    <x-design-system.match-header
        modality="Oculto 4×4"
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
            <x-design-system.match-status :tone="$finished ? 'win' : 'turn'" data-testid="match-status">
                {{ $finished ? 'Jogador '.strtoupper($winner).' venceu!' : 'Vez do jogador '.strtoupper($currentPlayer) }}
            </x-design-system.match-status>

            @if ($finished)
                <x-design-system.game-board
                    :cells="$displayCells"
                    :columns="4"
                    label="Tabuleiro Oculto 4 por 4, peças reveladas"
                    :highlighted-cells="$winningCells"
                    :unavailable-cells="range(0, 15)"
                    highlight-label="casa vencedora"
                    data-testid="hidden-board"
                    class="classic-4x3-board"
                />
            @else
                <div data-testid="hidden-board" class="experimental-board classic-4x3-board mx-auto mt-3 grid w-full max-w-[28rem] grid-cols-4 gap-2 sm:mt-4 sm:gap-3" role="group" aria-label="Tabuleiro Oculto 4 por 4">
                    @foreach ($displayCells as $index => $cell)
                        <button
                            type="button"
                            wire:key="cell-{{ $index }}"
                            wire:click="choose({{ $index }})"
                            wire:loading.attr="disabled"
                            wire:target="choose"
                            data-testid="cell-{{ $index }}"
                            aria-label="Escolher casa linha {{ intdiv($index, 4) + 1 }}, coluna {{ $index % 4 + 1 }}"
                            class="relative aspect-square min-h-18 rounded-ds-control border-2 border-ds-border bg-ds-surface text-5xl font-black uppercase shadow-sm enabled:hover:border-ds-primary enabled:hover:bg-ds-hover enabled:hover:shadow-ds-card enabled:active:scale-[0.98] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-wait motion-safe:transition-[transform,background-color,border-color,box-shadow] motion-safe:duration-200 motion-reduce:transition-none"
                        ><span aria-hidden="true" class="text-ds-text-muted">?</span></button>
                    @endforeach
                </div>
            @endif

            @if ($feedbackMessage)
                <p data-testid="hidden-feedback" role="status" aria-live="polite" class="mt-3 rounded-ds-control border border-ds-border bg-ds-surface-muted p-3 text-sm text-ds-text-secondary">{{ $feedbackMessage }}</p>
            @endif
            <p wire:loading role="status" class="mt-3 text-sm text-ds-text-secondary">Processando jogada…</p>
            <p class="mt-3 text-center text-xs font-semibold text-ds-text-muted">As peças permanecem ocultas até a vitória. Escolha qualquer casa para jogar.</p>
        </div>

        <div class="experimental-match-sidebar flex min-w-0 flex-col gap-3">
            <div class="experimental-participants grid grid-cols-2 gap-2.5 md:grid-cols-1" aria-label="Participantes">
                <x-design-system.participant-card label="Jogador X" mark="X" :wins="$score['x']" :active="!$finished && $currentPlayer === 'x'" :finished="$finished" :winner-mark="$winner" data-testid="participant-x" />
                <x-design-system.participant-card label="Jogador O" mark="O" :wins="$score['o']" :active="!$finished && $currentPlayer === 'o'" :finished="$finished" :winner-mark="$winner" data-testid="participant-o" />
            </div>
            <p data-testid="score-draws" class="rounded-ds-control border border-ds-border bg-ds-surface-muted px-2 py-1 text-center text-xs font-bold text-ds-text-secondary">Empates: <span class="font-black text-ds-text">{{ $score['draws'] }}</span></p>
            <x-design-system.match-actions data-testid="match-actions">
                <button type="button" x-ref="restartButton" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Reiniciar partida</button>
                <button type="button" x-on:click="$refs.newMatchDialog.showModal()" data-testid="new-match-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Nova partida</button>
            </x-design-system.match-actions>
        </div>
    </section>

    <dialog x-ref="newMatchDialog" data-testid="new-match-dialog" aria-modal="true" aria-labelledby="new-match-title" aria-describedby="new-match-description" class="ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-4 text-ds-text shadow-ds-elevated sm:p-5">
        <h2 id="new-match-title" class="text-xl font-black">Criar nova partida?</h2>
        <p id="new-match-description" class="mt-2 text-sm leading-relaxed text-ds-text-secondary">O tabuleiro e o placar atuais serão deixados para trás. A próxima rodada mantém o placar; uma nova partida começa do zero.</p>
        <div class="mt-4 grid grid-cols-2 gap-2.5">
            <button type="button" x-on:click="$refs.newMatchDialog.close()" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Cancelar</button>
            <button type="button" wire:click="newMatch" wire:loading.attr="disabled" wire:target="newMatch" data-testid="confirm-new-match" class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Criar nova partida</button>
        </div>
    </dialog>

    @if ($finished)
        <x-design-system.result-dialog
            :title="'Jogador '.strtoupper($winner).' venceu!'"
            eyebrow="Vitória da rodada"
            description="Três em linha! As peças foram reveladas e o placar foi atualizado."
            :winner-mark="$winner"
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
