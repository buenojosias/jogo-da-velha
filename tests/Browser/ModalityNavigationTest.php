<?php

it('lists all fourteen modalities in the catalogue', function () {
    $page = visit('/');

    $page
        ->assertSee('Plataforma Jogo da Velha')
        ->assertCount('a[aria-label^="Jogar "]', 14)
        ->assertNoJavaScriptErrors();

    foreach (browserModalities() as [$modality]) {
        $page->assertPresent(sprintf('a[href$="%s"]', $modality['uri']));
    }
});

it('opens the public Livewire classic from the catalogue', function () {
    visit('/')
        ->click('a[href$="/3-basico"]')
        ->assertScript('location.pathname', '/3-basico')
        ->assertVisible('[data-testid="classic-board"]')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertNoJavaScriptErrors();
});

it('opens the public Livewire four by four classic from the catalogue', function () {
    visit('/')
        ->click('a[href$="/4-3-basico"]')
        ->assertScript('location.pathname', '/4-3-basico')
        ->assertVisible('[data-testid="classic-board"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertNoJavaScriptErrors();
});

it('opens the public Livewire movement game from the catalogue', function () {
    visit('/')
        ->click('a[href$="/3-mover"]')
        ->assertScript('location.pathname', '/3-mover')
        ->assertVisible('[data-testid="movement-board"]')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertNoJavaScriptErrors();
});

it('renders the :dataset modality in a real browser', function (array $modality) {
    $page = visit($modality['uri']);

    $page
        ->assertTitleContains($modality['title'])
        ->assertVisible($modality['board'])
        ->assertCount($modality['cells'], $modality['cell_count'])
        ->assertNoJavaScriptErrors();

    foreach ($modality['additional'] as $selector => $count) {
        $page->assertCount($selector, $count);
    }
})->with(browserModalities());

/**
 * @return array<string, array{array{uri: string, title: string, board: string, cells: string, cell_count: int, additional: array<string, int>} }>
 */
function browserModalities(): array
{
    return [
        'classic 3x3' => [[
            'uri' => '/3-basico',
            'title' => 'Clássico 3×3',
            'board' => '[data-testid="classic-board"]',
            'cells' => '[data-testid^="cell-"]',
            'cell_count' => 9,
            'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'movement 3x3' => [[
            'uri' => '/3-mover',
            'title' => 'Movimento 3×3',
            'board' => '[data-testid="movement-board"]',
            'cells' => '[data-testid^="cell-"]',
            'cell_count' => 9,
            'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'rotation 3x3' => [[
            'uri' => '/3-rotacao', 'title' => 'Rotação 3×3', 'board' => '[data-testid="rotation-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 9, 'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'classic 4x4' => [[
            'uri' => '/4-3-basico',
            'title' => 'Clássico 4×4',
            'board' => '[data-testid="classic-board"]',
            'cells' => '[data-testid^="cell-"]',
            'cell_count' => 16,
            'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'movement 4x4' => [[
            'uri' => '/4-3-mover',
            'title' => 'Movimento 4×4',
            'board' => '[data-testid="movement-board"]',
            'cells' => '[data-testid^="cell-"]',
            'cell_count' => 16,
            'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'rotation 4x4' => [[
            'uri' => '/4-3-rotacao', 'title' => 'Rotação 4×4', 'board' => '[data-testid="rotation-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 16, 'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'blocking 4x4' => [[
            'uri' => '/4-3-bloqueio', 'title' => 'Bloqueio 4×4', 'board' => '[data-testid="classic-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 16, 'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'hidden 4x4' => [[
            'uri' => '/4-3-oculto', 'title' => 'Oculto 4×4', 'board' => '[data-testid="hidden-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 16, 'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'line of four' => [[
            'uri' => '/4-mover', 'title' => 'Linha de 4 com Movimento', 'board' => '[data-testid="movement-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 16, 'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'two lines 5x5' => [[
            'uri' => '/2-linhas', 'title' => 'Duas Linhas 5×5', 'board' => '[data-testid="classic-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 25, 'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'score 5x5' => [[
            'uri' => '/5-cheia', 'title' => 'Pontuação 5×5', 'board' => '[data-testid="classic-board"]',
            'cells' => '[data-testid^="cell-"]', 'cell_count' => 25, 'additional' => ['[data-testid="round-points-x"]' => 1],
        ]],
        'dice' => [[
            'uri' => '/dado',
            'title' => 'Dados da Velha',
            'board' => '[data-testid="dice-board-esquerdo"]',
            'cells' => '[data-testid^="cell-"]',
            'cell_count' => 18,
            'additional' => ['[data-testid="dice-board-direito"]' => 1, '[data-testid="dice-face"]' => 1, '[data-testid="roll-button"]' => 1],
        ]],
        'crazy' => [[
            'uri' => '/maluca',
            'title' => 'Conquista Maluca',
            'board' => '[data-testid="crazy-board"]',
            'cells' => '[data-testid^="crazy-cell-"]',
            'cell_count' => 9,
            'additional' => ['[data-testid^="piece-x-"]' => 3, '[data-testid^="piece-o-"]' => 3],
        ]],
        'ultimate' => [[
            'uri' => '/ultimate',
            'title' => 'Ultimate Tic-Tac-Toe',
            'board' => '[data-testid="ultimate-overview"]',
            'cells' => '[data-testid^="ultimate-cell-"]',
            'cell_count' => 81,
            'additional' => ['[data-testid^="ultimate-sub-board-"]' => 9, '[data-testid="ultimate-focus-region"]' => 1],
        ]],
    ];
}

/**
 * @return array{uri: string, title: string, board: string, cells: string, cell_count: int, additional: array<string, int>}
 */
function modality(string $uri, string $title, int $cellCount): array
{
    return [
        'uri' => $uri,
        'title' => $title,
        'board' => '#board',
        'cells' => '#board .cell',
        'cell_count' => $cellCount,
        'additional' => [],
    ];
}
