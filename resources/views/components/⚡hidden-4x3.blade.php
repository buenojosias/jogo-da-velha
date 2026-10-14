<?php

use App\Games\{GameAction, MatchState, Modality, PlayerMark};
use App\Games\Rules\Hidden4x3Rules;
use App\Http\{HiddenMatchManager, SessionMatchStore};
use Livewire\Attributes\{Computed, Locked};
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $matchId;

    public ?string $feedbackMessage = null;

    public function mount(string $matchId): void
    {
        $this->matchId = $matchId;
        $this->matchState();
    }

    #[Computed]
    public function matchState(): MatchState
    {
        abort_unless(app(HiddenMatchManager::class)->isActive($this->matchId), 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== '4x3-hidden', 404);

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
        return app(Hidden4x3Rules::class)->winningLine($this->matchState) ?? [];
    }

    public function choose(mixed $cellIndex): void
    {
        $state = $this->matchState;
        $player = $state->currentPlayer ?? app(SessionMatchStore::class)->startingPlayer($this->matchId);
        $result = app(Hidden4x3Rules::class)->apply($state, new GameAction($player, 'choose', ['cell' => $cellIndex]));
        if (! $result->validation->valid) {
            $this->feedbackMessage = match ($result->validation->reason) {
                'game_finished' => 'A rodada terminou. Inicie a próxima rodada.',
                'invalid_cell' => 'Escolha uma casa válida.',
                'no_empty_cell' => 'Não há casa livre para deslocar a peça.',
                default => 'Não foi possível realizar esta jogada.',
            };
            $this->dispatch('hidden-cell-feedback-clear');

            return;
        }

        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $previousCell = $state->data['board'][$cellIndex];
        $this->feedbackMessage = $result->state->isFinished() ? null : match ($previousCell) {
            '' => 'Jogada registrada.',
            $player->value => 'Uma peça foi movimentada.',
            default => 'Você perdeu a vez.',
        };
        unset($this->matchState, $this->score, $this->winningCells);
        if ($result->state->isFinished()) {
            $this->dispatch('hidden-cell-feedback-clear');
            $this->dispatch('hidden-round-finished');
        } else {
            $kind = match ($previousCell) {
                '' => 'placed-'.$player->value,
                $player->value => 'own-move',
                default => 'turn-lost',
            };
            $this->dispatch('hidden-cell-feedback', cell: $cellIndex, kind: $kind);
        }
    }

    public function restart(): void
    {
        $state = $this->matchState;
        $store = app(SessionMatchStore::class);
        $starter = $store->startingPlayer($this->matchId);
        $next = $starter === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
        $store->restart($this->matchId, app(Hidden4x3Rules::class)->initialState(
            new Modality('4x3-hidden'), $state->participants, $next,
        ));
        $this->feedbackMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('hidden-cell-feedback-clear');
    }

    public function newMatch(): void
    {
        $this->matchState;
        $this->matchId = app(HiddenMatchManager::class)->createNewActive();
        $this->feedbackMessage = null;
        unset($this->matchState, $this->score, $this->winningCells);
        $this->dispatch('hidden-cell-feedback-clear');
        $this->redirectRoute('4x3-hidden');
    }
};
?>

<x-design-system.hidden-match
    :match-id="$matchId"
    :finished="$this->matchState->isFinished()"
    :current-player="$this->matchState->currentPlayer?->value"
    :winner="$this->matchState->winner?->value"
    :display-cells="$this->matchState->isFinished() ? $this->matchState->data['board'] : array_fill(0, 16, '')"
    :winning-cells="$this->winningCells"
    :score="$this->score"
    :feedback-message="$feedbackMessage"
/>
