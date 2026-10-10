<?php

it('plays an experimental classic match reactively without reloading the page', function () {
    $page = visit('/experimental/3-basico')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador X')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0');

    $page->script('window.__experimentalMatchMarker = "same-document";');

    $page
        ->assertScript('document.querySelector("[data-testid=cell-0]").disabled', false)
        ->assertVisible('[data-testid="cell-0"]')
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="cell-0"]', 'x')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->click('[data-testid="cell-3"]')
        ->click('[data-testid="cell-1"]')
        ->click('[data-testid="cell-4"]')
        ->click('[data-testid="cell-2"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')
        ->assertScript('window.__experimentalMatchMarker', 'same-document')
        ->assertScript('performance.getEntriesByType("navigation").length', 1)
        ->assertNoJavaScriptErrors();
});

it('keeps the experimental board visible and interactive on a small phone', function () {
    visit('/experimental/3-basico')
        ->resize(360, 800)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="cell-0"]')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O')
        ->assertNoJavaScriptErrors();
});
