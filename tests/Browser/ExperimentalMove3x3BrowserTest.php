<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('places three pieces per player, selects and moves a piece without a full reload', function () {
    $page = visit('/3-mover')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça');
    movementStartingWith($page, 'X');

    foreach ([0, 1, 2] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? 'x' : 'o');
    }
    $page->refresh()->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O');
    $page->script('window.__movementMarker = "same-document";');

    foreach ([3, 4, 6] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? 'o' : 'x');
    }
    $page->assertSeeIn('[data-testid="phase-instruction"]', 'Selecione uma peça e depois uma casa vazia');

    $page->click('[data-testid="cell-0"]')->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')->assertScript('document.querySelector("[data-testid=cell-2]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')->assertScript('document.querySelector("[data-testid=cell-2]").dataset.selected', 'false')
        ->assertScript('document.querySelector("[data-testid=cell-1]").disabled', true)
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->click('[data-testid="cell-0"]')->click('[data-testid="cell-5"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('document.querySelector("[data-testid=cell-5]").dataset.mark', 'x')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertScript('window.__movementMarker', 'same-document')
        ->click('[data-testid="cell-1"]')
        ->assertScript('document.querySelector("[data-testid=cell-1]").dataset.selected', 'true')
        ->click('Nova rodada')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça')
        ->assertNoJavaScriptErrors();
});

it('shows a movement victory, score and modal, then restarts with the other player', function () {
    $page = visit('/3-mover');
    movementStartingWith($page, 'X');

    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? 'x' : 'o');
    }

    $page->click('[data-testid="cell-2"]')->click('[data-testid="cell-8"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('JSON.stringify([...document.querySelectorAll("[data-winning-cell=true]")].map(cell => Number(cell.dataset.testid.slice(5))))', '[0,4,8]')
        ->keys('[data-testid="result-dialog"]', 'Escape')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->refresh()
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->click('Nova rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça')
        ->assertNoJavaScriptErrors();
});

it('resets temporary selection on reload and preserves the theme', function () {
    $page = visit('/3-mover');
    movementStartingWith($page, 'X');
    $page->select('[data-theme-selector]', 'dark')
        ->assertScript('document.documentElement.dataset.theme', 'dark');

    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? 'x' : 'o');
    }

    $page->click('[data-testid="cell-0"]')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->refresh()
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Selecione uma peça e depois uma casa vazia')
        ->assertNoJavaScriptErrors();
});

it('keeps the movement board and controls usable at supported viewport sizes', function (int $width, int $height) {
    $page = visit('/3-mover')->resize($width, $height)
        ->assertVisible('[data-testid="movement-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="match-actions"]')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true)
        ->click('[data-testid="cell-8"]')
        ->assertScript('document.querySelector("[data-testid=cell-8]").dataset.mark !== ""', true)
        ->assertNoJavaScriptErrors();
})->with([
    'small portrait' => [360, 800],
    'short landscape' => [740, 360],
    'desktop' => [1280, 900],
]);

function movementStartingWith(AwaitableWebpage|PendingAwaitablePage $page, string $mark): void
{
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    if ($turn !== 'Vez do jogador '.$mark) {
        $page->click('Nova rodada');
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.$mark);
}
