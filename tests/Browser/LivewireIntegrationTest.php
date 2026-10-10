<?php

it('updates the reference counter through Livewire without a full page reload', function () {
    $page = visit('/livewire-reference')
        ->assertSee('Incrementar')
        ->assertSeeIn('[data-testid="reference-count"]', '0')
        ->assertScript('typeof window.Livewire !== "undefined"');

    $page->script('window.__livewireBrowserMarker = "same-document";');

    $page
        ->click('Incrementar')
        ->assertSeeIn('[data-testid="reference-count"]', '1')
        ->assertScript('window.__livewireBrowserMarker', 'same-document')
        ->assertScript('performance.getEntriesByType("navigation").length', 1)
        ->assertNoJavaScriptErrors();
});
