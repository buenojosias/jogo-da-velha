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

    /**
     * @return array{x: int, o: int, draws: int}
     */
    #[Computed]
    public function score(): array
    {
        return app(SessionMatchStore::class)->score($this->matchId);
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
        unset($this->matchState, $this->score);
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
        unset($this->matchState, $this->score);
    }
};
?>

@php($state = $this->matchState)
@php($score = $this->score)

<div class="experimental-match mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-4 px-4 py-4 text-ds-text sm:px-6 sm:py-6">
    <x-design-system.match-header
        modality="Clássico 3×3"
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

            <div data-testid="classic-board" role="group" aria-label="Tabuleiro 3 por 3" class="experimental-board mx-auto mt-3 grid w-full max-w-[28rem] grid-cols-3 gap-2 sm:mt-4 sm:gap-3">
                @foreach ($state->data['board'] as $index => $cell)
                    <button
                        type="button"
                        wire:key="cell-{{ $index }}"
                        wire:click="place({{ $index }})"
                        wire:loading.attr="disabled"
                        data-testid="cell-{{ $index }}"
                        aria-label="Casa linha {{ intdiv($index, 3) + 1 }}, coluna {{ $index % 3 + 1 }}: {{ $cell === '' ? 'vazia' : strtoupper($cell) }}"
                        @disabled($cell !== '' || $state->isFinished())
                        class="aspect-square min-h-18 rounded-ds-control border-2 border-ds-border bg-ds-surface text-5xl font-black uppercase shadow-sm {{ $cell === 'o' ? 'text-ds-o' : 'text-ds-x' }} enabled:hover:border-ds-primary enabled:hover:bg-ds-hover enabled:hover:shadow-ds-card enabled:active:scale-[0.98] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed disabled:bg-ds-surface-muted motion-safe:transition-[transform,background-color,border-color,box-shadow] motion-safe:duration-200 motion-reduce:transition-none sm:text-6xl"
                    >{{ $cell }}</button>
                @endforeach
            </div>

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
                <button type="button" wire:click="restart" wire:loading.attr="disabled" class="min-h-12 w-full rounded-ds-control bg-ds-primary px-5 py-3 text-base font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:opacity-60 motion-safe:transition-colors motion-safe:duration-200 motion-reduce:transition-none">
                    Reiniciar partida
                </button>
            </x-design-system.match-actions>

            <p data-testid="move-count" class="text-center text-xs font-semibold text-ds-text-muted">Jogadas realizadas: {{ count(array_filter($state->data['board'], fn (string $cell): bool => $cell !== '')) }}</p>
        </div>
    </section>
</div>
