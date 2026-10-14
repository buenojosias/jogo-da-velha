<?php

it('leaves a fresh match without showing the confirmation', function () {
    visit('/3-basico')
        ->click('[data-testid="exit-button"]')
        ->assertScript('location.pathname', '/')
        ->assertNoJavaScriptErrors();
});

it('opens the dialog, restores focus on cancellation and discards on confirmation', function () {
    visit('/3-basico')
        ->click('[data-testid="cell-0"]')
        ->click('[data-testid="exit-button"]')
        ->assertVisible('[data-testid="exit-dialog"]')
        ->assertScript('document.activeElement?.dataset.testid', 'exit-keep')
        ->keys('[data-testid="exit-dialog"]', 'Escape')
        ->assertScript('document.activeElement?.dataset.testid', 'exit-button')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->click('[data-testid="exit-button"]')
        ->click('[data-testid="exit-discard"]')
        ->assertScript('location.pathname', '/')
        ->click('a[href$="/3-basico"]')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertNoJavaScriptErrors();
});

it('keeps the confirmation accessible on a short landscape screen', function () {
    visit('/3-basico')
        ->resize(740, 360)
        ->click('[data-testid="cell-0"]')
        ->click('[data-testid="exit-button"]')
        ->assertVisible('[data-testid="exit-dialog"]')
        ->assertScript('document.querySelector("[data-testid=exit-dialog]").getBoundingClientRect().height <= innerHeight', true)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertNoJavaScriptErrors();
});

it('shows the renamed restart confirmation and keeps its cancel action', function () {
    visit('/3-basico')
        ->click('[data-testid="cell-0"]')
        ->click('[data-testid="new-match-button"]')
        ->assertVisible('[data-testid="new-match-dialog"]')
        ->assertSeeIn('[data-testid="new-match-dialog"]', 'Reiniciar partida?')
        ->assertSeeIn('[data-testid="new-match-dialog"]', 'O tabuleiro e o placar serão zerados.')
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->assertNoJavaScriptErrors();
});
