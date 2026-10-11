@props(['modality', 'title', 'description'])

<header {{ $attributes->class(['rounded-ds-card border border-ds-border bg-ds-surface px-4 py-3 shadow-ds-card sm:px-5 sm:py-4']) }}>
    <div class="flex items-center justify-between gap-3 sm:gap-5">
        <div class="min-w-0">
            <span class="text-xs font-extrabold uppercase tracking-widest text-ds-primary">{{ $modality }}</span>
            <h1 class="text-xl leading-tight font-extrabold tracking-tight text-ds-text sm:text-2xl">{{ $title }}</h1>
            @if ($description)
                <p class="sr-only">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</header>
