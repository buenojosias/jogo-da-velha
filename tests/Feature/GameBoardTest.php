<?php

use Illuminate\Support\Facades\Blade;

test('renders a labelled square board with the configured number of columns', function (int $columns) {
    $cells = array_fill(0, $columns * $columns, '');
    $label = "Tabuleiro {$columns} por {$columns}";

    $html = Blade::render('<x-design-system.game-board :cells="$cells" :columns="$columns" :label="$label" data-testid="board-example" />', compact('cells', 'columns', 'label'));

    expect($html)
        ->toContain('data-testid="board-example"')
        ->toContain('role="group"')
        ->toContain('aria-label="'.$label.'"')
        ->toContain('grid-cols-'.$columns)
        ->toContain('aria-label="Casa linha '.$columns.', coluna '.$columns.': vazia"')
        ->toContain('wire:click="place('.($columns * $columns - 1).')"');
    expect(substr_count($html, 'data-testid="cell-'))->toBe($columns * $columns);
})->with([
    '3 by 3' => [3],
    '4 by 4' => [4],
    '5 by 5' => [5],
]);

test('renders marks, highlighted cells, and unavailable cells without changing their values', function () {
    $cells = ['', '', '', '', 'x', '', '', '', 'o'];

    $html = Blade::render('<x-design-system.game-board :cells="$cells" :columns="3" label="Partida" :highlighted-cells="[4]" :unavailable-cells="[0, 4, 8]" highlight-label="casa vencedora" />', compact('cells'));
    $document = new DOMDocument;
    $document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $emptyUnavailableCell = $xpath->query('//button[@data-testid="cell-0"]')->item(0);
    $highlightedCell = $xpath->query('//button[@data-testid="cell-4"]')->item(0);
    $occupiedOCell = $xpath->query('//button[@data-testid="cell-8"]')->item(0);
    $availableCell = $xpath->query('//button[@data-testid="cell-1"]')->item(0);

    expect($emptyUnavailableCell->hasAttribute('disabled'))->toBeTrue();
    expect($highlightedCell->hasAttribute('disabled'))->toBeTrue();
    expect($highlightedCell->getAttribute('data-winning-cell'))->toBe('true');
    expect($highlightedCell->getAttribute('aria-label'))->toBe('Casa linha 2, coluna 2: X, casa vencedora');
    expect($highlightedCell->getAttribute('class'))->toContain('text-ds-x');
    expect($html)->toContain('✦');
    expect($occupiedOCell->hasAttribute('disabled'))->toBeTrue();
    expect($occupiedOCell->getAttribute('aria-label'))->toBe('Casa linha 3, coluna 3: O');
    expect($occupiedOCell->getAttribute('class'))->toContain('text-ds-o');
    expect($availableCell->hasAttribute('disabled'))->toBeFalse();
    expect($availableCell->getAttribute('aria-label'))->toBe('Casa linha 1, coluna 2: vazia');
    expect($availableCell->getAttribute('wire:loading.attr'))->toBe('disabled');
    expect($availableCell->getAttribute('class'))->toContain('focus-visible:outline-ds-focus');
});

test('accepts a column count passed as a Blade attribute', function () {
    $cells = array_fill(0, 16, '');

    $html = Blade::render('<x-design-system.game-board :cells="$cells" columns="4" label="Tabuleiro" />', compact('cells'));

    expect($html)
        ->toContain('grid-cols-4')
        ->toContain('aria-label="Casa linha 4, coluna 4: vazia"');
});
