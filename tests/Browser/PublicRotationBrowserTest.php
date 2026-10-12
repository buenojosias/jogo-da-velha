<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

function rotationBrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $text = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($text);

    return strtolower(substr($text, -1));
}

function rotationBrowserPlace(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $page->click('[data-testid="cell-'.$cell.'"]');
    awaitBrowserCellMark($page, $cell, $mark);
}

it('rotates the oldest piece on the public four by four board', function () {
    $page = visit('/')->click('a[href$="/4-3-rotacao"]')
        ->assertScript('location.pathname', '/4-3-rotacao')
        ->assertCount('[data-testid^="cell-"]', 16);
    $starter = rotationBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        rotationBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="phase-instruction"]', 'Sua peça mais antiga será removida')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.oldestCell', 'true')
        ->assertScript('document.querySelector("[data-testid=cell-1]").dataset.oldestCell', 'true')
        ->assertSeeIn('[data-testid="oldest-x"]', $starter === 'x' ? 'casa 1 será removida' : 'casa 2 será removida')
        ->refresh()
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.oldestCell', 'true');
    rotationBrowserPlace($page, 5, $starter);
    $page->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('document.querySelector("[data-testid=cell-2]").dataset.oldestCell', 'true')
        ->assertNoJavaScriptErrors();
});

it('shows a win and score then resets only the round on :dataset', function (string $uri, int $size) {
    $page = visit($uri)->assertCount('[data-testid^="cell-"]', $size);
    $starter = rotationBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 3, 1, 4, 2] as $turn => $cell) {
        rotationBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->refresh()
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertNoJavaScriptErrors();
})->with(['3x3' => ['/3-rotacao', 9], '4x4' => ['/4-3-rotacao', 16]]);

it('shows a victory produced by removing the oldest piece on :dataset', function (string $uri, array $placements, int $destination, array $line) {
    $page = visit($uri);
    $starter = rotationBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ($placements as $turn => $cell) {
        rotationBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    rotationBrowserPlace($page, $destination, $starter);
    $lineJson = json_encode($line, JSON_THROW_ON_ERROR);
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('JSON.stringify([...document.querySelectorAll("[data-winning-cell=true]")].map(cell => Number(cell.dataset.testid.slice(5))))', $lineJson)
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertNoJavaScriptErrors();
})->with([
    '3x3' => ['/3-rotacao', [0, 1, 4, 2, 5, 6], 3, [3, 4, 5]],
    '4x4' => ['/4-3-rotacao', [0, 1, 4, 3, 5, 8], 6, [4, 5, 6]],
]);

it('requires confirmation for a new match on :dataset', function (string $uri) {
    $page = visit($uri);
    $starter = rotationBrowserStarter($page);
    rotationBrowserPlace($page, 0, $starter);

    $page->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('Cancelar')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', $starter)
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', $uri)
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
})->with(['3x3' => ['/3-rotacao'], '4x4' => ['/4-3-rotacao']]);

it('keeps the board and controls usable in both themes at :dataset', function (string $uri, int $size, int $width, int $height, string $theme) {
    $page = visit($uri)->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="rotation-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', $size)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = rotationBrowserStarter($page);
    rotationBrowserPlace($page, $size - 1, $starter);
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-'.($size - 1).']").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    '3x3 portrait light' => ['/3-rotacao', 9, 360, 800, 'light'],
    '3x3 landscape dark' => ['/3-rotacao', 9, 740, 360, 'dark'],
    '3x3 wide landscape light' => ['/3-rotacao', 9, 800, 360, 'light'],
    '3x3 desktop dark' => ['/3-rotacao', 9, 1280, 900, 'dark'],
    '4x4 portrait dark' => ['/4-3-rotacao', 16, 360, 800, 'dark'],
    '4x4 landscape light' => ['/4-3-rotacao', 16, 740, 360, 'light'],
    '4x4 wide landscape dark' => ['/4-3-rotacao', 16, 800, 360, 'dark'],
    '4x4 desktop light' => ['/4-3-rotacao', 16, 1280, 900, 'light'],
]);
