@props(['modality', 'title', 'description'])

<header {{ $attributes->class(['ds-match-header rounded-ds-card border border-ds-border bg-ds-surface px-4 py-3 shadow-ds-card sm:px-5 sm:py-3']) }}>
    <div class="flex items-center justify-between gap-3 sm:gap-5">
        <div class="min-w-0">
            <span class="ds-modality inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-ds-primary before:size-2 before:rotate-45 before:rounded-[2px] before:bg-ds-turquoise before:content-['']">{{ $modality }}</span>
            <h1 class="ds-match-title text-xl leading-tight font-extrabold tracking-tight text-ds-text sm:text-2xl"><span class="sr-only">{{ $modality }} — </span>{{ $title }}</h1>
            @if ($description)
                <p class="sr-only">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</header>
