<?php

it('lists all fourteen modalities in the catalogue', function () {
    $page = visit('/');

    $page
        ->assertSee('Jogos da Velha')
        ->assertCount('main a, body > div.container a', 14)
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
        'rotation 3x3' => [modality('/3-rotacao', 'Rotação 3x3', 9)],
        'classic 4x4' => [[
            'uri' => '/4-3-basico',
            'title' => 'Clássico 4×4',
            'board' => '[data-testid="classic-board"]',
            'cells' => '[data-testid^="cell-"]',
            'cell_count' => 16,
            'additional' => ['[data-testid="new-match-button"]' => 1],
        ]],
        'movement 4x4' => [modality('/4-3-mover', 'Movimento 4x4', 16)],
        'rotation 4x4' => [modality('/4-3-rotacao', 'Rotação 4x4', 16)],
        'blocking 4x4' => [modality('/4-3-bloqueio', 'Bloqueio 4x4', 16)],
        'hidden 4x4' => [modality('/4-3-oculto', 'Oculto 4x4', 16)],
        'line of four' => [modality('/4-mover', 'Linha de 4 com Movimento', 16)],
        'two lines 5x5' => [modality('/2-linhas', '2 Linhas 5x5', 25)],
        'score 5x5' => [modality('/5-cheia', 'Pontuação 5x5', 25)],
        'dice' => [[
            'uri' => '/dado',
            'title' => 'Dados da Velha',
            'board' => '#board1',
            'cells' => '.boards .cell',
            'cell_count' => 18,
            'additional' => ['.boards .board' => 2, '#dice' => 1, '#rollBtn' => 1],
        ]],
        'crazy' => [[
            'uri' => '/maluca',
            'title' => 'Conquista Maluca',
            'board' => '#board',
            'cells' => '#board .cell',
            'cell_count' => 9,
            'additional' => ['#piecesX .select-piece' => 9, '#piecesO .select-piece' => 9],
        ]],
        'ultimate' => [[
            'uri' => '/ultimate',
            'title' => 'Ultimate Tic-Tac-Toe',
            'board' => '#game',
            'cells' => '#game .cell',
            'cell_count' => 81,
            'additional' => ['#game .sub-board' => 9, '#game .sub-board.active' => 9],
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
