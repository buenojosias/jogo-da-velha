@props([
    'cells',
    'columns',
    'label',
    'highlightedCells' => [],
    'unavailableCells' => [],
    'highlightLabel' => 'casa destacada',
    'interaction' => 'place',
])
@php($columnCount = (int) $columns)

<div {{ $attributes->class([
    'experimental-board mx-auto mt-3 grid w-full max-w-[28rem] gap-2 sm:mt-4 sm:gap-3',
    'grid-cols-3' => $columnCount === 3,
    'grid-cols-4' => $columnCount === 4,
    'grid-cols-5' => $columnCount === 5,
]) }} role="group" aria-label="{{ $label }}">
    @foreach ($cells as $index => $cell)
        @php($isHighlighted = in_array($index, $highlightedCells, true))
        <button
            type="button"
            wire:key="cell-{{ $index }}"
            @if ($interaction === 'place') wire:click="place({{ $index }})" @else x-on:click="handleCell({{ $index }})" @endif
            wire:loading.attr="disabled"
            data-testid="cell-{{ $index }}"
            @if ($interaction === 'movement') data-mark="{{ $cell }}" x-bind:data-selected="selectedCell === {{ $index }} ? 'true' : 'false'" x-bind:aria-pressed="selectedCell === {{ $index }} ? 'true' : 'false'" @endif
            data-winning-cell="{{ $isHighlighted ? 'true' : 'false' }}"
            aria-label="Casa linha {{ intdiv($index, $columnCount) + 1 }}, coluna {{ $index % $columnCount + 1 }}: {{ $cell === '' ? 'vazia' : strtoupper($cell) }}{{ $isHighlighted ? ', '.$highlightLabel : '' }}"
            @disabled(in_array($index, $unavailableCells, true))
            class="relative aspect-square min-h-18 rounded-ds-control border-2 border-ds-border bg-ds-surface text-5xl font-black uppercase shadow-sm {{ $cell === 'o' ? 'text-ds-o' : 'text-ds-x' }} enabled:hover:border-ds-primary enabled:hover:bg-ds-hover enabled:hover:shadow-ds-card enabled:active:scale-[0.98] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed disabled:bg-ds-surface-muted motion-safe:transition-[transform,background-color,border-color,box-shadow] motion-safe:duration-200 motion-reduce:transition-none sm:text-6xl {{ $interaction === 'movement' ? 'data-[selected=true]:border-ds-primary data-[selected=true]:ring-4 data-[selected=true]:ring-ds-focus' : '' }}"
        >{{ $cell }}@if ($isHighlighted)<span aria-hidden="true" class="pointer-events-none absolute top-1 right-1 text-base text-ds-success">✦</span>@endif</button>
    @endforeach
</div>
