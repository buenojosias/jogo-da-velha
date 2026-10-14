<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('plays an experimental classic match reactively without reloading the page', function () {
    $page = visit('/3-basico')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0');

    experimentalPageStartingWith($page, 'x');
    $page->assertSeeIn('[data-testid="participant-x"]', 'É a vez');
    $page->script('window.__experimentalMatchMarker = "same-document";');

    $page
        ->assertScript('document.querySelector("[data-testid=cell-0]").disabled', false)
        ->assertVisible('[data-testid="cell-0"]')
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="cell-0"]', 'x')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertSeeIn('[data-testid="participant-o"]', 'É a vez')
        ->click('[data-testid="cell-3"]')
        ->click('[data-testid="cell-1"]')
        ->click('[data-testid="cell-4"]')
        ->click('[data-testid="cell-2"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertScript('window.__experimentalMatchMarker', 'same-document')
        ->assertScript('performance.getEntriesByType("navigation").length', 1)
        ->assertNoJavaScriptErrors();
});

it('keeps the board and controls usable without clipping or overlap', function (int $width, int $height, string $theme) {
    $page = $theme === 'dark'
        ? visit('/3-basico')->inDarkMode()
        : visit('/3-basico')->inLightMode();

    experimentalPageStartingWith($page, 'x');
    $page
        ->resize($width, $height)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="cell-0"]')
        ->assertVisible('[data-theme-selector]')
        ->assertVisible('[data-testid="match-actions"]')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertScript('(() => { const board = document.querySelector("[data-testid=classic-board]").getBoundingClientRect(); return board.left >= 0 && board.right <= innerWidth && Math.abs(board.width - board.height) < 2; })()', true)
        ->assertScript('(() => { const selectors = ["[data-testid=match-header]", "[data-testid=game-region]", "[data-testid=participant-x]", "[data-testid=participant-o]", "[data-testid=match-actions]"]; const rectangles = selectors.map(selector => document.querySelector(selector).getBoundingClientRect()); return rectangles.every((a, i) => rectangles.every((b, j) => i === j || a.right <= b.left || b.right <= a.left || a.bottom <= b.top || b.bottom <= a.top)); })()', true)
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->click('Nova rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertScript('document.querySelector("[data-testid=cell-0]").textContent.trim()', '')
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertNoJavaScriptErrors();
})->with([
    'small phone light' => [360, 800, 'light'],
    'small phone dark' => [360, 800, 'dark'],
    'phone light' => [390, 844, 'light'],
    'phone dark' => [390, 844, 'dark'],
    'tablet light' => [768, 1024, 'light'],
    'tablet dark' => [768, 1024, 'dark'],
    'desktop light' => [1280, 900, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);

it('keeps every cell and essential control in reach on a short landscape screen', function (int $width, string $theme) {
    $page = $theme === 'dark'
        ? visit('/3-basico')->inDarkMode()
        : visit('/3-basico')->inLightMode();

    experimentalPageStartingWith($page, 'x');
    $page
        ->resize($width, 360)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('document.documentElement.scrollHeight <= innerHeight + 24', true)
        ->assertScript('(() => { const board = document.querySelector("[data-testid=classic-board]").getBoundingClientRect(); const participant = document.querySelector("[data-testid=participant-x]").getBoundingClientRect(); return board.right < participant.left && Math.abs(board.width - board.height) < 2; })()', true)
        ->assertScript('(() => { const cells = [...document.querySelectorAll("[data-testid^=cell-]")]; return cells.length === 9 && cells.every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.top >= 0 && rect.bottom <= innerHeight && rect.left >= 0 && rect.right <= innerWidth && document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2) === cell; }); })()', true)
        ->assertScript('(() => { const selectors = ["[data-testid=match-status]", "[data-testid=participant-x]", "[data-testid=participant-o]", "[data-theme-selector]", "[data-testid=match-actions] button"]; return selectors.every(selector => { const element = document.querySelector(selector); const rect = element.getBoundingClientRect(); const topmost = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2); return rect.top >= 0 && rect.bottom <= innerHeight && rect.left >= 0 && rect.right <= innerWidth && (topmost === element || element.contains(topmost)); }); })()', true)
        ->click('[data-testid="cell-8"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->click('Nova rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertNoJavaScriptErrors();
})->with([
    '740 landscape light' => [740, 'light'],
    '740 landscape dark' => [740, 'dark'],
    '800 landscape light' => [800, 'light'],
    '800 landscape dark' => [800, 'dark'],
]);

it('shows a reactive scoreboard that survives restarting a round', function (int $width, int $height) {
    $page = visit('/3-basico')->resize($width, $height)
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertSeeIn('[data-testid="score-draws"]', '0');

    experimentalPageStartingWith($page, 'x');
    foreach ([0, 3, 1, 4, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertSeeIn('[data-testid="score-draws"]', '0')
        ->click('Fechar')
        ->click('Nova rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->assertVisible('[data-testid="score-draws"]')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertNoJavaScriptErrors();
})->with([
    'phone scoreboard' => [360, 800],
    'desktop scoreboard' => [1280, 900],
]);

it('opens the result only after a winning move and continues with the next round', function () {
    $page = visit('/3-basico')
        ->assertScript('document.querySelector("[data-testid=result-dialog]") === null', true);

    experimentalPageStartingWith($page, 'x');
    $page->script('window.__resultPageMarker = "same-document";');

    foreach ([0, 3, 1, 4] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page->assertScript('document.querySelector("[data-testid=result-dialog]") === null', true)
        ->click('[data-testid="cell-2"]')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="result-dialog"]', 'Jogador X venceu!')
        ->assertSeeIn('[data-testid="result-score-x"]', '1')
        ->assertScript('JSON.stringify([...document.querySelectorAll("[data-winning-cell=true]")].map(cell => Number(cell.dataset.testid.slice(5))))', '[0,1,2]')
        ->assertScript('document.activeElement.closest("dialog") !== null', true)
        ->assertScript('getComputedStyle(document.documentElement).overflowY', 'hidden')
        ->click('Próxima rodada')
        ->assertScript('document.querySelector("[data-testid=result-dialog]") === null', true)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador X')
        ->assertScript('window.__resultPageMarker', 'same-document')
        ->assertNoJavaScriptErrors();
});

it('closes by button or Escape and does not reopen on rerender or reload', function () {
    $page = visit('/3-basico');

    experimentalPageStartingWith($page, 'x');
    foreach ([0, 3, 1, 4, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->click('Fechar')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->assertScript('document.activeElement === document.querySelector("[data-testid=match-actions] button")', true);

    $page->script('window.Livewire.find(document.querySelector(".experimental-match").getAttribute("wire:id")).call("place", 5)');

    $page
        ->assertSeeIn('[data-testid="rejection-message"]', 'A rodada terminou.')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->refresh()
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->click('Nova rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O');

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="result-dialog"]', 'Jogador O venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->keys('[data-testid="result-dialog"]', 'Escape')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', false)
        ->assertScript('document.activeElement === document.querySelector("[data-testid=match-actions] button")', true)
        ->assertNoJavaScriptErrors();
});

it('opens a neutral result dialog for a draw with the updated score', function () {
    $page = visit('/3-basico');

    experimentalPageStartingWith($page, 'x');
    foreach ([0, 1, 2, 4, 3, 5, 7, 6, 8] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="result-dialog"]', 'Rodada empatada!')
        ->assertSeeIn('[data-testid="result-score-draws"]', '1')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 0)
        ->assertScript('(() => { const dialog = document.querySelector("[data-testid=result-dialog]"); return dialog.getAttribute("aria-modal") === "true" && Boolean(document.getElementById(dialog.getAttribute("aria-labelledby"))) && Boolean(document.getElementById(dialog.getAttribute("aria-describedby"))); })()', true)
        ->assertNoJavaScriptErrors();
});

it('keeps the result dialog within the viewport and its actions reachable', function (int $width, int $height, string $theme) {
    $page = $theme === 'dark'
        ? visit('/3-basico')->inDarkMode()
        : visit('/3-basico')->inLightMode();

    experimentalPageStartingWith($page, 'x');
    $page->resize($width, $height);

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('(() => { const dialog = document.querySelector("[data-testid=result-dialog]"); const rect = dialog.getBoundingClientRect(); return rect.top >= 0 && rect.bottom <= innerHeight && rect.left >= 0 && rect.right <= innerWidth && dialog.clientHeight <= innerHeight - 12; })()', true)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertVisible('[data-testid="result-dialog"] button[autofocus]')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertNoJavaScriptErrors();
})->with([
    'small phone result light' => [360, 800, 'light'],
    'phone result dark' => [390, 844, 'dark'],
    'small landscape result light' => [740, 360, 'light'],
    'landscape result dark' => [800, 360, 'dark'],
    'desktop result light' => [1280, 900, 'light'],
]);

function experimentalPageStartingWith(AwaitableWebpage|PendingAwaitablePage $page, string $expectedMark): void
{
    $observedTurn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($observedTurn);

    if ($observedTurn !== 'Vez do jogador '.strtoupper($expectedMark)) {
        $page->click('Nova rodada');
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($expectedMark));
}
