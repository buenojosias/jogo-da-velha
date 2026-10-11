@props(['modality', 'title', 'description'])

<header {{ $attributes->class(['rounded-ds-card border border-ds-border bg-ds-surface p-5 shadow-ds-card sm:p-6']) }}>
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 flex-col items-start gap-2">
            <span class="rounded-full bg-ds-hover px-3 py-1 text-xs font-bold uppercase tracking-wide text-ds-primary">{{ $modality }}</span>
            <h1 class="text-2xl font-extrabold tracking-tight text-ds-text sm:text-3xl">{{ $title }}</h1>
            <p class="max-w-prose text-sm leading-relaxed text-ds-text-secondary">{{ $description }}</p>
        </div>

        @isset($actions)
            <div class="w-full shrink-0 sm:w-auto">{{ $actions }}</div>
        @endisset
    </div>
</header>
