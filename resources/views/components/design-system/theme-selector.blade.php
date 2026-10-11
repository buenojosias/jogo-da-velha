@props(['id' => 'theme-preference'])

<div {{ $attributes->class(['flex flex-col gap-1']) }} wire:ignore>
    <label for="{{ $id }}" class="text-xs font-bold text-ds-text-secondary">Tema</label>
    <select
        id="{{ $id }}"
        data-theme-selector
        hidden
        class="min-h-11 w-32 rounded-ds-control border-2 border-ds-border bg-ds-surface px-2 py-1.5 text-sm font-bold text-ds-text hover:border-ds-primary focus-visible:border-ds-focus focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus sm:w-36"
    >
        <option value="auto">Automático</option>
        <option value="light">Claro</option>
        <option value="dark">Escuro</option>
    </select>
</div>
