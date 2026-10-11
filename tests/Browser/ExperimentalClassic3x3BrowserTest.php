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

it('keeps the experimental board and controls usable without horizontal scrolling', function (int $width, int $height) {
    visit('/experimental/3-basico')
        ->resize($width, $height)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="cell-0"]')
        ->assertVisible('[data-theme-selector]')
        ->assertVisible('[data-testid="match-actions"]')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertNoJavaScriptErrors();
})->with([
    'small phone' => [360, 800],
    'desktop' => [1280, 800],
]);
