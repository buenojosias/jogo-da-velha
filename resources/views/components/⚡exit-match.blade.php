<?php

use App\Http\ExitMatchAction;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $matchId;

    #[Locked]
    public string $modality;

    public bool $confirming = false;

    public function mount(string $matchId, string $modality): void
    {
        $this->matchId = $matchId;
        $this->modality = $modality;
        app(ExitMatchAction::class)->assertActive($modality, $matchId);
    }

    public function requestExit(): void
    {
        $exit = app(ExitMatchAction::class);
        $state = $exit->assertActive($this->modality, $this->matchId);

        if (! $exit->hasProgress($state, $this->matchId)) {
            $this->redirectRoute('welcome');

            return;
        }

        $this->confirming = true;
        $this->dispatch('exit-confirm');
    }

    public function keep(): void
    {
        abort_unless($this->confirming, 404);
        app(ExitMatchAction::class)->assertActive($this->modality, $this->matchId);
        $this->redirectRoute('welcome');
    }

    public function discard(): void
    {
        abort_unless($this->confirming, 404);
        app(ExitMatchAction::class)->discard($this->modality, $this->matchId);
        $this->redirectRoute('welcome');
    }

    public function cancel(): void
    {
        $this->confirming = false;
        $this->dispatch('exit-cancelled');
    }
};
?>

<div x-data="{ busy: false }" x-on:exit-confirm.window="$refs.exitDialog.showModal(); $nextTick(() => $refs.keepButton.focus())" x-on:exit-cancelled.window="$refs.exitDialog.close(); $nextTick(() => $refs.exitButton.focus())">
    <button type="button" x-ref="exitButton" wire:click="requestExit" wire:loading.attr="disabled" wire:target="requestExit,keep,discard" data-testid="exit-button" class="mt-2 min-h-11 w-full rounded-ds-control border border-ds-border bg-ds-surface px-4 py-2 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Sair</button>

    <dialog x-ref="exitDialog" x-on:cancel.prevent="if (!busy) $wire.cancel()" data-testid="exit-dialog" aria-modal="true" aria-labelledby="exit-title" aria-describedby="exit-description" class="ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-4 text-ds-text shadow-ds-elevated sm:p-5">
        <h2 id="exit-title" class="text-xl font-black">Deseja manter esta partida?</h2>
        <p id="exit-description" class="mt-2 text-sm leading-relaxed text-ds-text-secondary">Você pode continuar de onde parou quando voltar a esta modalidade. Se descartar, a partida e o placar serão zerados.</p>
        <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
            <button type="button" x-ref="keepButton" x-on:click="if (!busy) { busy = true; $wire.keep(); }" x-bind:disabled="busy" data-testid="exit-keep" class="min-h-12 rounded-ds-control bg-ds-primary px-3 py-2.5 text-sm font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Sim, manter</button>
            <button type="button" x-on:click="if (!busy) { busy = true; $wire.discard(); }" x-bind:disabled="busy" data-testid="exit-discard" class="min-h-12 rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-bold text-ds-text hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">Não, descartar</button>
            <button type="button" x-on:click="if (!busy) $wire.cancel()" x-bind:disabled="busy" data-testid="exit-cancel" class="min-h-12 rounded-ds-control border border-ds-border bg-ds-surface px-3 py-2.5 text-sm font-semibold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60 sm:col-span-2">Cancelar</button>
        </div>
    </dialog>
</div>
