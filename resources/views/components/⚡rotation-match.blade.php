<?php

use App\Games\GameAction;
use App\Games\MatchState;
use App\Games\Modality;
use App\Games\PlayerMark;
use App\Games\Rules\Rotation3x3Rules;
use App\Games\Rules\Rotation4x3Rules;
use App\Games\Rules\RotationRules;
use App\Http\RotationMatchManager;
use App\Http\SessionMatchStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $matchId;

    #[Locked]
    public string $modalityKey;

    public ?string $rejectionMessage = null;

    public function mount(string $matchId, string $modalityKey): void
    {
        abort_unless(in_array($modalityKey, ['3-rotation', '4x3-rotation'], true), 404);
        $this->matchId = $matchId;
        $this->modalityKey = $modalityKey;
        $this->matchState();
    }

    #[Computed]
    public function matchState(): MatchState
    {
        $matches = app(RotationMatchManager::class);
        $active = $this->modalityKey === '3-rotation'
            ? $matches->isActive3x3($this->matchId)
            : $matches->isActive4x3($this->matchId);
        abort_unless($active, 404);

        try {
            $state = app(SessionMatchStore::class)->find($this->matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }
        abort_if($state === null || $state->modality->key !== $this->modalityKey, 404);

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
        return $this->rules()->winningLine($this->matchState) ?? [];
    }

    /** @return array{x: ?int, o: ?int} */
    #[Computed]
    public function oldestPieces(): array
    {
        return $this->rules()->oldestPieces($this->matchState);
    }

    public function place(mixed $cellIndex): void
    {
        $state = $this->matchState;
        $player = $state->currentPlayer ?? app(SessionMatchStore::class)->startingPlayer($this->matchId);
        $result = $this->rules()->apply($state, new GameAction($player, 'place', ['cell' => $cellIndex]));

        if (! $result->validation->valid) {
            $this->rejectionMessage = match ($result->validation->reason) {
                'cell_occupied' => 'Esta casa já está ocupada.',
                'game_finished' => 'A rodada terminou. Inicie uma nova rodada para jogar novamente.',
                'invalid_cell' => 'Escolha uma casa válida.',
                default => 'Não foi possível realizar esta jogada.',
            };

            return;
        }

        app(SessionMatchStore::class)->save($this->matchId, $result->state);
        $this->rejectionMessage = null;
        $this->forgetComputed();
        if ($result->state->isFinished()) {
            $this->dispatch('rotation-round-finished');
        }
    }

    public function restart(): void
    {
        $state = $this->matchState;
        $starter = app(SessionMatchStore::class)->startingPlayer($this->matchId);
        $next = $starter === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
        app(SessionMatchStore::class)->restart($this->matchId,
            $this->rules()->initialState(new Modality($this->modalityKey), $state->participants, $next));
        $this->rejectionMessage = null;
        $this->forgetComputed();
    }

    public function newMatch(): void
    {
        $this->matchState;
        $matches = app(RotationMatchManager::class);
        $this->matchId = $this->modalityKey === '3-rotation'
            ? $matches->createNewActive3x3()
            : $matches->createNewActive4x3();
        $this->rejectionMessage = null;
        $this->forgetComputed();
        $this->redirectRoute($this->modalityKey);
    }

    private function rules(): RotationRules
    {
        return $this->modalityKey === '3-rotation' ? app(Rotation3x3Rules::class) : app(Rotation4x3Rules::class);
    }

    private function forgetComputed(): void
    {
        unset($this->matchState, $this->score, $this->winningCells, $this->oldestPieces);
    }
};
?>

<x-design-system.rotation-match
    :match-id="$matchId"
    :modality="$modalityKey === '3-rotation' ? 'Rotação 3×3' : 'Rotação 4×4 (3 peças)'"
    :state="$this->matchState"
    :columns="$modalityKey === '3-rotation' ? 3 : 4"
    :board-label="$modalityKey === '3-rotation' ? 'Tabuleiro Rotação 3 por 3' : 'Tabuleiro Rotação 4 por 4'"
    :board-class="$modalityKey === '3-rotation' ? '' : 'classic-4x3-board'"
    :winning-cells="$this->winningCells"
    :oldest-pieces="$this->oldestPieces"
    :score="$this->score"
    :rejection-message="$rejectionMessage"
    :public-entry="true"
    round-finished-event="rotation-round-finished"
/>
