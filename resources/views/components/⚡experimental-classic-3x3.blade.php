<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
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

        abort_if(app(SessionMatchStore::class)->find($matchId) === null, 404);
    }

    #[Computed]
    public function matchState(): MatchState
    {
        return app(SessionMatchStore::class)->find($this->matchId) ?? abort(404);
    }

    public function place(mixed $cellIndex): void
    {
        $state = $this->matchState;
        $actingPlayer = $state->currentPlayer ?? PlayerMark::X;
        $result = app(Classic3x3Rules::class)->apply(
            $state,
            new GameAction($actingPlayer, 'place', ['cell' => $cellIndex]),
        );

        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'game_finished' => 'A partida terminou. Reinicie para jogar novamente.',
                'invalid_cell' => 'Escolha uma casa válida.',
                default => 'Não foi possível realizar esta jogada.',
            };

            return;
        }

        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        unset($this->matchState);
    }

    public function restart(): void
    {
        $store = app(SessionMatchStore::class);
        $state = $store->find($this->matchId) ?? abort(404);
        $nextStarter = $store->startingPlayer($this->matchId) === PlayerMark::X
            ? PlayerMark::O
            : PlayerMark::X;

        $store->restart(
            $this->matchId,
            app(Classic3x3Rules::class)->initialState($state->modality, $state->participants, $nextStarter),
        );

        $this->rejectionMessage = null;
        unset($this->matchState);
    }
};
?>

@php($state = $this->matchState)

<div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col gap-5 px-4 py-5 text-ds-text sm:gap-6 sm:px-6 sm:py-8">
    <x-design-system.match-header
        modality="Clássico 3×3"
        title="Jogo da Velha 3×3"
        description="Partida local: Jogador X e Jogador O no mesmo dispositivo."
        data-testid="match-header"
    >
        <x-slot:actions>
            <x-design-system.theme-selector data-testid="theme-selector" />
        </x-slot:actions>
    </x-design-system.match-header>

    <section aria-label="Partida local" class="flex flex-col gap-5">
        <div class="grid grid-cols-2 gap-3" aria-label="Participantes">
            <x-design-system.participant-card
                label="Jogador X"
                mark="X"
                :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::X"
                data-testid="participant-x"
            />
            <x-design-system.participant-card
                label="Jogador O"
                mark="O"
                :active="$state->status === MatchStatus::InProgress && $state->currentPlayer === PlayerMark::O"
                data-testid="participant-o"
            />
        </div>

        <p data-testid="move-count" class="text-center text-sm font-semibold text-ds-text-secondary">Jogadas realizadas: {{ count(array_filter($state->data['board'], fn (string $cell): bool => $cell !== '')) }}</p>

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

        @if ($rejectionMessage)
            <p data-testid="rejection-message" role="alert" class="rounded-lg border border-ds-warning bg-ds-warning-surface p-3 text-sm text-ds-warning">{{ $rejectionMessage }}</p>
        @endif

        <div data-testid="classic-board" role="group" aria-label="Tabuleiro 3 por 3" class="mx-auto grid w-full max-w-md grid-cols-3 gap-2">
            @foreach ($state->data['board'] as $index => $cell)
                <button
                    type="button"
                    wire:key="cell-{{ $index }}"
                    wire:click="place({{ $index }})"
                    wire:loading.attr="disabled"
                    data-testid="cell-{{ $index }}"
                    aria-label="Casa linha {{ intdiv($index, 3) + 1 }}, coluna {{ $index % 3 + 1 }}: {{ $cell === '' ? 'vazia' : strtoupper($cell) }}"
                    @disabled($cell !== '' || $state->isFinished())
                    class="aspect-square min-h-20 rounded-xl border-2 border-ds-border bg-ds-surface text-4xl font-bold uppercase {{ $cell === 'o' ? 'text-ds-o' : 'text-ds-x' }} transition-colors hover:border-ds-primary hover:bg-ds-hover focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed disabled:bg-ds-surface-muted"
                >{{ $cell }}</button>
            @endforeach
        </div>

        <p wire:loading role="status" class="text-sm text-ds-text-secondary">Processando jogada…</p>

        <x-design-system.match-actions data-testid="match-actions">
            <button type="button" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60">
                Reiniciar partida
            </button>
        </x-design-system.match-actions>
    </section>
</div>
