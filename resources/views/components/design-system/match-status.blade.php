@props(['tone' => 'turn'])

<p {{ $attributes->class([
    'ds-match-status mx-auto w-full max-w-[28rem] rounded-ds-control border px-3 py-2.5 text-center text-base font-extrabold sm:text-lg',
    'border-ds-primary bg-ds-hover text-ds-text' => $tone === 'turn',
    'border-ds-success bg-ds-hover text-ds-success' => $tone === 'win',
    'border-ds-border bg-ds-surface-muted text-ds-text' => $tone === 'draw',
]) }} role="status" aria-live="polite" aria-atomic="true">{{ $slot }}</p>
