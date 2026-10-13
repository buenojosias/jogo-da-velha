@props(['kind' => 'grid', 'size' => 3])

<div {{ $attributes->class(['flex min-h-32 items-center justify-center gap-3 rounded-ds-control bg-ds-surface-muted p-3']) }} aria-hidden="true">
    @if ($kind === 'dice')
        @foreach ([0, 1] as $board)
            <div class="grid size-20 grid-cols-3 gap-1 rounded-lg border-2 border-ds-border p-1">
                @for ($cell = 0; $cell < 9; $cell++)
                    <span class="flex items-center justify-center rounded-[3px] bg-ds-surface text-sm font-black {{ $cell === ($board ? 4 : 0) ? 'text-ds-x' : 'text-ds-o' }}">{{ $cell === ($board ? 4 : 0) ? 'X' : ($cell === ($board ? 2 : 8) ? 'O' : '') }}</span>
                @endfor
            </div>
        @endforeach
    @elseif ($kind === 'ultimate')
        <div class="grid size-28 grid-cols-3 gap-1.5 rounded-lg border-2 border-ds-primary p-1.5">
            @for ($board = 0; $board < 9; $board++)
                <div class="grid grid-cols-3 gap-px rounded-[2px] bg-ds-border p-px">
                    @for ($cell = 0; $cell < 9; $cell++)
                        <span class="bg-ds-surface"></span>
                    @endfor
                </div>
            @endfor
        </div>
    @elseif ($kind === 'crazy')
        <div class="grid size-28 grid-cols-3 gap-1 rounded-lg border-2 border-ds-border p-1">
            @for ($cell = 0; $cell < 9; $cell++)
                <span class="flex items-center justify-center rounded bg-ds-surface font-black {{ $cell % 2 ? 'text-ds-o' : 'text-ds-x' }}" style="font-size: {{ [12, 20, 27][$cell % 3] }}px">{{ in_array($cell, [0, 4, 8]) ? 'X' : ($cell === 2 ? 'O' : '') }}</span>
            @endfor
        </div>
    @else
        <div class="grid size-28 gap-1 rounded-lg border-2 border-ds-border p-1" style="grid-template-columns: repeat({{ $size }}, minmax(0, 1fr))">
            @for ($cell = 0; $cell < $size * $size; $cell++)
                <span class="flex items-center justify-center rounded-[3px] bg-ds-surface font-black {{ $cell % 2 ? 'text-ds-o' : 'text-ds-x' }} {{ $size === 5 ? 'text-sm' : 'text-lg' }}">{{ $cell === 0 || $cell === $size + 1 ? 'X' : ($cell === $size - 1 || $cell === $size * 2 - 1 ? 'O' : '') }}</span>
            @endfor
        </div>
        @if ($kind === 'rotation')<span class="text-3xl font-black text-ds-primary">↻</span>@endif
        @if ($kind === 'movement')<span class="text-3xl font-black text-ds-primary">↗</span>@endif
        @if ($kind === 'hidden')<span class="text-3xl font-black text-ds-primary">?</span>@endif
    @endif
</div>
