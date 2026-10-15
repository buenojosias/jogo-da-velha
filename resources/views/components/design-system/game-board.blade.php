@props([
    'cells',
    'columns',
    'label',
    'highlightedCells' => [],
    'progressCells' => [],
    'oldestCells' => [],
    'oldestIndicator' => 'default',
    'currentPlayerMark' => null,
    'unavailableCells' => [],
    'restrictedCells' => [],
    'highlightLabel' => 'casa destacada',
    'interaction' => 'place',
    'boardIndex' => null,
    'allowedColumn' => null,
    'lastMove' => null,
])
@php($columnCount = (int) $columns)

<div {{ $attributes->class([
    'experimental-board ds-board-grid mx-auto mt-3 grid w-full max-w-[28rem] sm:mt-4',
    'grid-cols-3' => $columnCount === 3,
    'grid-cols-4' => $columnCount === 4,
    'grid-cols-5' => $columnCount === 5,
]) }} role="group" aria-label="{{ $label }}">
    @foreach ($cells as $index => $cell)
        @php($isHighlighted = in_array($index, $highlightedCells, true))
        @php($progressMark = $cell !== '' && in_array($index, $progressCells[$cell] ?? [], true) ? $cell : null)
        @php($isOldest = in_array($index, $oldestCells, true))
        @php($isRotationOldest = $isOldest && $oldestIndicator === 'rotation')
        @php($isCurrentOldest = $isRotationOldest && $cell === $currentPlayerMark)
        @php($isRestricted = in_array($index, $restrictedCells, true))
        <button
            type="button"
            wire:key="{{ $interaction === 'dice' ? 'dice-'.$boardIndex.'-cell-'.$index : 'cell-'.$index }}"
            @if ($interaction === 'place') wire:click="place({{ $index }})" @elseif ($interaction === 'dice') wire:click="place({{ $boardIndex }}, {{ $index }})" @else x-on:click="handleCell({{ $index }})" @endif
            wire:loading.attr="disabled"
            data-testid="cell-{{ $index }}"
            data-mark="{{ $cell }}"
            @if ($interaction === 'movement') x-bind:data-selected="selectedCell === {{ $index }} ? 'true' : 'false'" x-bind:aria-pressed="selectedCell === {{ $index }} ? 'true' : 'false'" @endif
            data-winning-cell="{{ $isHighlighted ? 'true' : 'false' }}"
            @if ($lastMove === $index) data-last-move="true" @endif
            @if ($progressMark !== null) data-line-progress="{{ $progressMark }}" @endif
            data-oldest-cell="{{ $isOldest ? 'true' : 'false' }}"
            @if ($isRotationOldest) data-oldest-turn="{{ $isCurrentOldest ? 'current' : 'opponent' }}" title="{{ $isCurrentOldest ? 'Esta peça será removida automaticamente quando você colocar a próxima.' : 'Esta peça será removida quando o adversário colocar a próxima.' }}" @endif
            @if ($isRestricted) data-restricted-cell="true" @endif
            @if ($interaction === 'dice') data-allowed-column="{{ $allowedColumn !== null && $index % 3 === $allowedColumn ? 'true' : 'false' }}" @endif
            aria-label="Casa linha {{ intdiv($index, $columnCount) + 1 }}, coluna {{ $index % $columnCount + 1 }}: {{ $cell === '' ? 'vazia' : strtoupper($cell) }}{{ $isRotationOldest ? ($isCurrentOldest ? ', sua peça mais antiga; será removida automaticamente quando você colocar a próxima' : ', peça mais antiga do adversário; será removida quando ele colocar a próxima') : ($isOldest ? ', peça mais antiga' : '') }}{{ $isHighlighted ? ', '.$highlightLabel : '' }}{{ $progressMark !== null ? ', linha do jogador '.strtoupper($progressMark) : '' }}{{ $isRestricted ? ', peça indisponível neste turno' : '' }}{{ $lastMove === $index ? ', última jogada do adversário' : '' }}"
            @disabled(in_array($index, $unavailableCells, true))
            class="ds-board-cell relative aspect-square min-h-18 text-5xl font-black uppercase {{ $cell === 'o' ? 'text-ds-o' : 'text-ds-x' }} {{ $lastMove === $index ? 'border-ds-primary' : '' }} {{ $isRotationOldest ? ($isCurrentOldest ? 'border-dashed ring-2 ring-inset ring-ds-warning' : 'border-dashed ring-1 ring-inset ring-ds-border') : '' }} {{ $isRestricted ? 'border-dashed border-ds-warning enabled:cursor-not-allowed enabled:hover:border-ds-warning' : 'enabled:hover:border-ds-primary' }} enabled:hover:bg-ds-hover enabled:hover:shadow-ds-card enabled:active:scale-[0.98] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus disabled:cursor-not-allowed disabled:bg-ds-surface-muted motion-safe:transition-[transform,background-color,border-color,box-shadow] motion-safe:duration-200 motion-reduce:transition-none sm:text-6xl {{ $interaction === 'movement' ? 'data-[selected=true]:border-ds-primary data-[selected=true]:ring-2 data-[selected=true]:ring-ds-focus' : '' }}"
        >{{ $cell }}@if ($isRotationOldest)<span data-testid="oldest-removal-label" aria-hidden="true" class="pointer-events-none absolute rounded-ds-control border px-1.5 text-[0.625rem] font-black leading-none {{ $columnCount === 4 ? 'right-0 top-0 py-0' : 'right-1 top-1 py-0.5' }} {{ $isCurrentOldest ? 'border-ds-warning bg-ds-warning-surface text-ds-warning' : 'border-ds-border bg-ds-surface-muted text-ds-text-secondary' }}">SAI</span>@elseif ($isOldest)<span aria-hidden="true" class="pointer-events-none absolute bottom-1 left-1 rounded-full bg-ds-warning-surface px-1.5 py-0.5 text-xs font-black text-ds-warning">1ª</span>@endif @if ($isHighlighted)<span aria-hidden="true" class="pointer-events-none absolute top-1 right-1 text-base text-ds-success">✦</span>@endif</button>
    @endforeach
</div>
