@props(['board', 'boardIndex', 'available', 'enlarged' => false])

<div role="group" aria-label="Casas do subtabuleiro {{ $boardIndex + 1 }}" data-density="{{ $enlarged ? 'regular' : 'compact' }}" class="ds-ultimate-cells grid aspect-square min-w-0 grid-cols-3 grid-rows-3">
    @foreach ($board as $cellIndex => $mark)
        <button type="button" wire:key="ultimate-{{ $enlarged ? 'zoom' : 'main' }}-cell-{{ $boardIndex }}-{{ $cellIndex }}"
            data-testid="ultimate-{{ $enlarged ? 'zoom-' : '' }}cell-{{ $boardIndex }}-{{ $cellIndex }}" data-mark="{{ $mark }}"
            aria-label="Subtabuleiro {{ $boardIndex + 1 }}, casa {{ $cellIndex + 1 }}{{ $mark ? ', '.strtoupper($mark) : ', vazia' }}"
            x-on:click="play({{ $boardIndex }}, {{ $cellIndex }})"
            wire:loading.attr="disabled" @disabled(! $available || $mark !== '')
            class="ds-board-cell ds-ultimate-cell flex aspect-square min-h-0 min-w-0 items-center justify-center font-black leading-none focus-visible:z-10 focus-visible:outline-offset-1 disabled:pointer-events-none {{ $enlarged ? 'text-3xl' : 'text-[clamp(0.6rem,2.5vw,1rem)]' }}">{{ strtoupper($mark) }}</button>
    @endforeach
</div>
