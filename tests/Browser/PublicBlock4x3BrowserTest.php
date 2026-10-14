<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

function blockBrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $text = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($text);

    return strtolower(substr($text, -1));
}

function blockBrowserPlace(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $page->click('[data-testid="cell-'.$cell.'"]');
    awaitBrowserCellMark($page, $cell, $mark);
}

it('opens from the catalogue, shows a blocked line briefly, then permits another move', function () {
    $page = visit('/')->click('a[href$="/4-3-bloqueio"]')
        ->assertScript('location.pathname', '/4-3-bloqueio')
        ->assertCount('[data-testid^="cell-"]', 16);
    $starter = blockBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 3, 1, 4, 2] as $turn => $cell) {
        blockBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertScript('document.querySelector("[data-testid=result-dialog]")?.open ?? false', false)
        ->assertScript('document.querySelector("[data-testid=cell-3]").dataset.blockFeedback', 'true')
        ->assertVisible('[data-testid="blocked-feedback"]');
    blockBrowserPlace($page, 6, $other);
    $page->assertScript('document.querySelector("[data-block-feedback=true]") === null', true)
        ->assertScript('document.querySelector("[data-testid=blocked-feedback]").hidden', true)
        ->refresh()
        ->assertScript('document.querySelector("[data-testid=cell-3]").dataset.blockFeedback ?? "false"', 'false')
        ->assertNoJavaScriptErrors();
});

it('shows an unblocked winner, score and result dialog, then keeps the score on the next round', function () {
    $page = visit('/4-3-bloqueio');
    $starter = blockBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 4, 1, 5, 2] as $turn => $cell) {
        blockBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->refresh()->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertNoJavaScriptErrors();
});

it('requires confirmation for a fresh match and clears its previous board', function () {
    $page = visit('/4-3-bloqueio');
    $starter = blockBrowserStarter($page);
    blockBrowserPlace($page, 0, $starter);
    $page->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', $starter)
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/4-3-bloqueio')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('keeps the board and controls usable at each target size and in both themes', function (int $width, int $height, string $theme) {
    $page = visit('/4-3-bloqueio')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = blockBrowserStarter($page);
    blockBrowserPlace($page, 15, $starter);
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-15]").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);
