@props(['tone' => 'turn'])

<p {{ $attributes->class([
    'rounded-ds-card border px-4 py-4 text-center text-lg font-extrabold sm:text-xl',
    'border-ds-primary bg-ds-hover text-ds-text' => $tone === 'turn',
    'border-ds-success bg-ds-surface text-ds-success' => $tone === 'win',
    'border-ds-border bg-ds-surface-muted text-ds-text' => $tone === 'draw',
]) }} role="status" aria-live="polite" aria-atomic="true">{{ $slot }}</p>
