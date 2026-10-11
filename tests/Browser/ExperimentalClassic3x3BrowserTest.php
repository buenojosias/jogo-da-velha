<?php

it('plays an experimental classic match reactively without reloading the page', function () {
    $page = visit('/experimental/3-basico')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador X')
        ->assertSeeIn('[data-testid="participant-x"]', 'É a vez')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0');

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
        ? visit('/experimental/3-basico')->inDarkMode()
        : visit('/experimental/3-basico')->inLightMode();

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
        ->click('Reiniciar partida')
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
        ? visit('/experimental/3-basico')->inDarkMode()
        : visit('/experimental/3-basico')->inLightMode();

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
        ->click('Reiniciar partida')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertNoJavaScriptErrors();
})->with([
    '740 landscape light' => [740, 'light'],
    '740 landscape dark' => [740, 'dark'],
    '800 landscape light' => [800, 'light'],
    '800 landscape dark' => [800, 'dark'],
]);

it('shows a reactive scoreboard that survives restarting a round', function (int $width, int $height) {
    $page = visit('/experimental/3-basico')->resize($width, $height)
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertSeeIn('[data-testid="score-draws"]', '0');

    foreach ([0, 3, 1, 4, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertSeeIn('[data-testid="score-draws"]', '0')
        ->click('Reiniciar partida')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertSeeIn('[data-testid="score-x"]', '1')
        ->assertVisible('[data-testid="score-draws"]')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertNoJavaScriptErrors();
})->with([
    'phone scoreboard' => [360, 800],
    'desktop scoreboard' => [1280, 900],
]);
