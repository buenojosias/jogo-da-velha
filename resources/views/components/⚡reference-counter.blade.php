<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
};
?>

<div>
    <output data-testid="reference-count">{{ $count }}</output>

    <button type="button" wire:click="increment">Incrementar</button>
</div>
