@props(['id' => 'theme-preference'])

<div {{ $attributes->class(['flex flex-col gap-1.5']) }} wire:ignore>
    <label for="{{ $id }}" class="text-sm font-bold text-ds-text">Tema</label>
    <select
        id="{{ $id }}"
        data-theme-selector
        hidden
        class="min-h-12 w-full rounded-ds-control border-2 border-ds-border bg-ds-surface px-3 py-2 text-base font-semibold text-ds-text shadow-ds-card hover:border-ds-primary focus-visible:border-ds-focus focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-ds-focus sm:min-w-40"
    >
        <option value="auto">Automático</option>
        <option value="light">Claro</option>
        <option value="dark">Escuro</option>
    </select>
</div>
