<?php

it('keeps the catalogue readable and within the viewport at :dataset', function (int $width, int $height) {
    visit('/')
        ->resize($width, $height)
        ->assertVisible('#catalogo')
        ->assertCount('a[aria-label^="Jogar "]', 14)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavaScriptErrors();
})->with([
    'phone portrait' => [360, 800],
    'short landscape' => [740, 360],
    'wide landscape' => [800, 360],
    'desktop' => [1280, 900],
]);

it('returns to the catalogue and resumes the same match', function () {
    visit('/')
        ->click('a[href$="/3-basico"]')
        ->assertVisible('[data-testid="classic-board"]')
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->click('[data-testid="catalog-return"]')
        ->assertScript('location.pathname', '/')
        ->click('a[href$="/3-basico"]')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->assertNoJavaScriptErrors();
});
