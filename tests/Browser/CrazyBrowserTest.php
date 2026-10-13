<?php

it('selects a piece, stacks a larger one, rejects an equal cover, and resumes after reload', function () {
    $page = visit('/maluca');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    $page->click('[data-testid="piece-'.$starter.'-1-1"]')
        ->click('[data-testid="crazy-cell-0"]')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', $starter.'-1-1')
        ->click('[data-testid="piece-'.$other.'-2-1"]')
        ->click('[data-testid="crazy-cell-0"]')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-stack-count', '2')
        ->click('[data-testid="piece-'.$starter.'-2-1"]')
        ->click('[data-testid="crazy-cell-0"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'maior')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', $other.'-2-1')
        ->assertNoJavaScriptErrors();
    $page->navigate('/maluca')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-stack-count', '2')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($starter));
});

it('shows definitive victory, result dialog, score and next round', function () {
    $page = visit('/maluca');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([[$starter, 3, 1, 0], [$other, 1, 1, 3], [$starter, 3, 2, 1], [$other, 1, 2, 4], [$starter, 3, 3, 2]] as [$mark, $size, $copy, $cell]) {
        $page->click('[data-testid="piece-'.$mark.'-'.$size.'-'.$copy.'"]')
            ->click('[data-testid="crazy-cell-'.$cell.'"]');
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-testid=crazy-board] .ring-4").length', 3)
        ->click('Próxima rodada')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', '')
        ->assertNoJavaScriptErrors();
});

it('changes theme and only resets after new-match confirmation', function () {
    $page = visit('/maluca');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $page->select('[data-theme-selector]', 'dark')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->click('[data-testid="piece-'.$starter.'-1-1"]')
        ->click('[data-testid="crazy-cell-0"]')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('Cancelar')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', $starter.'-1-1')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', '')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertNoJavaScriptErrors();
});

it('keeps the board and selectors usable across mobile and desktop themes', function (int $width, int $height) {
    $page = visit('/maluca')->resize($width, $height)
        ->assertVisible('[data-testid="crazy-board"]')
        ->assertVisible('[data-testid="piece-selector"]')
        ->assertCount('[data-testid^="crazy-cell-"]', 9)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth', true);
    $page->assertNoJavaScriptErrors();
})->with([[360, 800], [740, 360], [800, 360], [1280, 900]]);
