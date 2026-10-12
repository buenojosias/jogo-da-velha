<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('opens from catalogue, places eight pieces, and moves a selected piece', function () {
    $page = visit('/')->click('a[href$="/4-mover"]')
        ->assertScript('location.pathname', '/4-mover')
        ->assertCount('[data-testid^="cell-"]', 16);
    $starter = move4x4BrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 5, 1, 6, 2, 8, 4, 9] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="phase-instruction"]', 'Selecione uma peça e depois uma casa vazia')
        ->click('[data-testid="cell-0"]')->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="cell-1"]')->assertScript('document.querySelector("[data-testid=cell-1]").dataset.selected', 'true')
        ->click('[data-testid="cell-1"]')->assertScript('document.querySelector("[data-testid=cell-1]").dataset.selected', 'false')
        ->click('[data-testid="cell-4"]')->click('[data-testid="cell-15"]');
    awaitBrowserCellMark($page, 15, $starter);
    $page->assertScript('document.querySelector("[data-testid=cell-4]").dataset.mark', '')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->refresh()->assertScript('document.querySelector("[data-testid=cell-15]").dataset.mark', $starter)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertNoJavaScriptErrors();
});

it('shows a four-cell victory, modal and score, then starts the next round', function () {
    $page = visit('/4-mover');
    $starter = move4x4BrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 4, 1, 5, 2, 6, 3] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 4)
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')->assertNoJavaScriptErrors();
});

it('wins through movement and requires confirmation before a new match', function () {
    $page = visit('/4-mover');
    $starter = move4x4BrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 5, 1, 6, 2, 8, 4, 9] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->click('[data-testid="cell-4"]')->click('[data-testid="cell-3"]');
    awaitBrowserCellMark($page, 3, $starter);
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->keys('[data-testid="result-dialog"]', 'Escape')
        ->click('[data-testid="new-match-button"]')->click('Cancelar')
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('[data-testid="new-match-button"]')->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/4-mover')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('keeps the public movement board usable across themes and widths', function (int $width, int $height, string $theme) {
    $page = visit('/4-mover')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="movement-board"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = move4x4BrowserStarter($page);
    $page->click('[data-testid="cell-15"]');
    awaitBrowserCellMark($page, 15, $starter);
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-15]").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);

function move4x4BrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($turn);

    return strtolower(substr($turn, -1));
}
