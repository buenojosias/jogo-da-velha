@props(['label', 'mark', 'active' => false])

<div {{ $attributes->class([
    'flex min-w-0 items-center gap-2 rounded-ds-card border border-l-4 bg-ds-surface px-2.5 py-2 shadow-ds-card sm:gap-3 sm:p-3',
    'border-ds-primary bg-ds-hover' => $active,
    'border-ds-border' => ! $active,
    'border-l-ds-turquoise' => strtoupper($mark) === 'X',
    'border-l-ds-coral' => strtoupper($mark) === 'O',
]) }}>
    <span @class([
        'flex size-10 shrink-0 items-center justify-center rounded-ds-control bg-ds-surface-muted text-xl font-black sm:size-11 sm:text-2xl',
        'text-ds-x' => strtoupper($mark) === 'X',
        'text-ds-o' => strtoupper($mark) === 'O',
    ]) aria-hidden="true">{{ strtoupper($mark) }}</span>
    <div class="min-w-0">
        <p class="text-xs leading-tight font-bold text-ds-text sm:text-sm">{{ $label }}</p>
        <p class="text-xs font-semibold text-ds-text-secondary">{{ $active ? 'É a vez' : 'Aguardando' }}</p>
    </div>
</div>
