@props(['board', 'boardIndex', 'available', 'localLine', 'enlarged' => false])

<div role="group" aria-label="Casas do subtabuleiro {{ $boardIndex + 1 }}" class="grid aspect-square min-w-0 grid-cols-3 grid-rows-3 {{ $enlarged ? 'gap-1.5' : 'gap-0.5' }}">
    @foreach ($board as $cellIndex => $mark)
        <button type="button" wire:key="ultimate-{{ $enlarged ? 'zoom' : 'main' }}-cell-{{ $boardIndex }}-{{ $cellIndex }}"
            data-testid="ultimate-{{ $enlarged ? 'zoom-' : '' }}cell-{{ $boardIndex }}-{{ $cellIndex }}" data-mark="{{ $mark }}"
            aria-label="Subtabuleiro {{ $boardIndex + 1 }}, casa {{ $cellIndex + 1 }}{{ $mark ? ', '.strtoupper($mark) : ', vazia' }}"
            x-on:click="play({{ $boardIndex }}, {{ $cellIndex }})"
            wire:loading.attr="disabled" @disabled(! $available || $mark !== '')
            class="flex aspect-square min-h-0 min-w-0 items-center justify-center rounded-sm border bg-ds-elevated font-black leading-none enabled:cursor-pointer enabled:hover:bg-ds-hover focus-visible:z-10 focus-visible:outline-3 focus-visible:outline-offset-1 focus-visible:outline-ds-focus disabled:pointer-events-none {{ $enlarged ? 'text-3xl shadow-ds-card' : 'text-[clamp(0.6rem,2.5vw,1rem)]' }} {{ in_array($cellIndex, $localLine ?? [], true) ? 'border-ds-success ring-2 ring-ds-success' : 'border-ds-board-border' }} {{ $mark === 'x' ? 'text-ds-x' : 'text-ds-o' }}">{{ strtoupper($mark) }}</button>
    @endforeach
</div>
