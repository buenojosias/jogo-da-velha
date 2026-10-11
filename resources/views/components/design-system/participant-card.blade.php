@props(['label', 'mark', 'active' => false])

<div {{ $attributes->class([
    'flex min-w-0 items-center gap-3 rounded-ds-card border border-l-4 bg-ds-surface p-3 shadow-ds-card sm:p-4',
    'border-ds-primary' => $active,
    'border-ds-border' => ! $active,
    'border-l-ds-turquoise' => strtoupper($mark) === 'X',
    'border-l-ds-coral' => strtoupper($mark) === 'O',
]) }}>
    <span @class([
        'flex size-12 shrink-0 items-center justify-center rounded-ds-control bg-ds-surface-muted text-2xl font-black',
        'text-ds-x' => strtoupper($mark) === 'X',
        'text-ds-o' => strtoupper($mark) === 'O',
    ]) aria-hidden="true">{{ strtoupper($mark) }}</span>
    <div class="min-w-0">
        <p class="truncate text-sm font-bold text-ds-text sm:text-base">{{ $label }}</p>
        <p class="text-xs font-semibold text-ds-text-secondary">{{ $active ? 'É a vez' : 'Participante' }}</p>
    </div>
</div>
