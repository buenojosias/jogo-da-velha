<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('plays a reactive four by four round, presents the result, and keeps the score on restart', function () {
    $page = visit('/4-3-basico')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0');

    $starter = fourByFourStarter($page);
    $next = $starter === 'X' ? 'O' : 'X';
    $page->script('window.__fourByFourMarker = "same-document";');

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.$starter.' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('JSON.stringify([...document.querySelectorAll("[data-winning-cell=true]")].map(cell => Number(cell.dataset.testid.slice(5))))', '[0,1,2]')
        ->assertSeeIn('[data-testid="score-'.strtolower($starter).'"]', '1')
        ->assertScript('window.__fourByFourMarker', 'same-document')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.$next)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertSeeIn('[data-testid="score-'.strtolower($starter).'"]', '1')
        ->refresh()
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.$next)
        ->assertSeeIn('[data-testid="score-'.strtolower($starter).'"]', '1')
        ->assertNoJavaScriptErrors();
});

it('keeps the theme after reload and closes the result dialog with Escape', function () {
    $page = visit('/4-3-basico');
    fourByFourStartingWith($page, 'X');

    $page->select('[data-theme-selector]', 'dark')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->refresh()
        ->assertScript('document.documentElement.dataset.theme', 'dark');

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->keys('[data-testid="result-dialog"]', 'Escape')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->assertScript('document.activeElement === document.querySelector("[data-testid=match-actions] button")', true)
        ->assertNoJavaScriptErrors();
});

it('keeps the result dialog usable on a short landscape screen', function (int $width) {
    $page = visit('/4-3-basico')->resize($width, 360);
    fourByFourStartingWith($page, 'X');

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('(() => { const dialog = document.querySelector("[data-testid=result-dialog]"); const rect = dialog.getBoundingClientRect(); return rect.top >= 0 && rect.bottom <= innerHeight && dialog.scrollHeight >= dialog.clientHeight; })()', true)
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertNoJavaScriptErrors();
})->with([
    '740 landscape' => [740],
    '800 landscape' => [800],
]);

it('shows a neutral result and draw score after sixteen moves', function () {
    $page = visit('/4-3-basico');

    foreach ([1, 0, 3, 2, 4, 6, 5, 7, 10, 8, 11, 9, 12, 14, 13, 15] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Rodada empatada!')
        ->assertSeeIn('[data-testid="result-score-draws"]', '1')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 0)
        ->assertNoJavaScriptErrors();
});

it('keeps sixteen cells accessible without horizontal overflow', function (int $width, int $height, string $theme) {
    $page = $theme === 'dark'
        ? visit('/4-3-basico')->inDarkMode()
        : visit('/4-3-basico')->inLightMode();

    $page->resize($width, $height)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="match-actions"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => { const board = document.querySelector("[data-testid=classic-board]").getBoundingClientRect(); return board.left >= 0 && board.right <= innerWidth && Math.abs(board.width - board.height) < 2; })()', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true)
        ->click('[data-testid="cell-15"]')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->assertNoJavaScriptErrors();
})->with([
    'small portrait light' => [360, 800, 'light'],
    'portrait dark' => [390, 844, 'dark'],
    'short landscape light' => [740, 360, 'light'],
    'short landscape dark' => [800, 360, 'dark'],
    'tablet light' => [768, 1024, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);

function fourByFourStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($turn);

    return substr($turn, -1);
}

function fourByFourStartingWith(AwaitableWebpage|PendingAwaitablePage $page, string $mark): void
{
    if (fourByFourStarter($page) !== $mark) {
        $page->click('Nova rodada');
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.$mark);
}
