@props(['title', 'eyebrow', 'description', 'winnerMark' => null, 'scoreX' => 0, 'scoreO' => 0, 'draws' => 0, 'dialogId' => 'round-result'])

<dialog {{ $attributes->class(['ds-result-dialog m-auto w-[min(27rem,calc(100vw-1.5rem))] max-h-[calc(100dvh-1.5rem)] max-w-none overflow-y-auto rounded-ds-card border border-ds-border bg-ds-elevated p-0 text-ds-text shadow-ds-elevated']) }} aria-modal="true" aria-labelledby="{{ $dialogId }}-title" aria-describedby="{{ $dialogId }}-description">
    <div class="p-4 sm:p-5">
        <div class="flex items-center gap-3">
            <span @class([
                'flex size-12 shrink-0 items-center justify-center rounded-ds-control text-3xl font-black',
                'bg-ds-hover text-ds-x' => $winnerMark === 'x',
                'bg-ds-hover text-ds-o' => $winnerMark === 'o',
                'bg-ds-surface-muted text-ds-primary' => $winnerMark === null,
            ]) aria-hidden="true">{{ $winnerMark === null ? '＝' : '✦' }}</span>
            <div class="min-w-0">
                <p class="text-xs font-extrabold uppercase tracking-widest text-ds-primary">{{ $eyebrow }}</p>
                <h2 id="{{ $dialogId }}-title" class="text-2xl leading-tight font-black tracking-tight text-ds-text">{{ $title }}</h2>
            </div>
        </div>

        <p id="{{ $dialogId }}-description" class="mt-3 text-sm leading-relaxed text-ds-text-secondary">{{ $description }}</p>

        <dl aria-label="Placar da partida" class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div @class([
                'rounded-ds-control border bg-ds-surface px-1.5 py-2',
                'border-ds-x bg-ds-hover' => $winnerMark === 'x',
                'border-ds-border' => $winnerMark !== 'x',
            ])>
                <dt class="text-xs font-bold text-ds-text-secondary">X</dt>
                <dd data-testid="result-score-x" class="text-xl font-black text-ds-x">{{ $scoreX }}</dd>
            </div>
            <div @class([
                'rounded-ds-control border bg-ds-surface px-1.5 py-2',
                'border-ds-o bg-ds-hover' => $winnerMark === 'o',
                'border-ds-border' => $winnerMark !== 'o',
            ])>
                <dt class="text-xs font-bold text-ds-text-secondary">O</dt>
                <dd data-testid="result-score-o" class="text-xl font-black text-ds-o">{{ $scoreO }}</dd>
            </div>
            <div @class([
                'rounded-ds-control border bg-ds-surface px-1.5 py-2',
                'border-ds-primary bg-ds-hover' => $winnerMark === null,
                'border-ds-border' => $winnerMark !== null,
            ])>
                <dt class="text-xs font-bold text-ds-text-secondary">Empates</dt>
                <dd data-testid="result-score-draws" class="text-xl font-black text-ds-text">{{ $draws }}</dd>
            </div>
        </dl>

        <div class="mt-4 grid grid-cols-2 gap-2.5">{{ $slot }}</div>
    </div>
</dialog>
