<?php

it('plays the public four by four classic, resumes it, and starts a confirmed fresh match', function () {
    $page = visit('/4-3-basico')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0');

    $starter = substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1);
    expect(['X', 'O'])->toContain($starter);
    $page->script('window.__publicClassicMarker = "same-document";');

    foreach ([0, 4, 1, 5, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.$starter.' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('window.__publicClassicMarker', 'same-document')
        ->assertSeeIn('[data-testid="score-'.strtolower($starter).'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->refresh()
        ->assertSeeIn('[data-testid="score-'.strtolower($starter).'"]', '1')
        ->click('[data-testid="cell-15"]')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('Cancelar')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/4-3-basico')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('keeps the public four by four board usable across supported viewports and themes', function (int $width, int $height, string $theme) {
    $page = visit('/4-3-basico')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true)
        ->click('[data-testid="cell-0"]')
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
