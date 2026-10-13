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
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));

    $page->click('[data-testid="piece-'.$starter.'-1-1"]')
        ->click('[data-testid="crazy-cell-0"]')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', $starter.'-1-1')
        ->assertNoJavaScriptErrors();
})->with([[360, 800], [740, 360], [800, 360], [1280, 900]]);

it('distinguishes available, waiting, selected, and used pieces without losing accessible labels', function () {
    $page = visit('/maluca');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    $currentPiece = '[data-testid="piece-'.$starter.'-1-1"]';
    $waitingPiece = '[data-testid="piece-'.$other.'-1-1"]';

    $page->assertAttribute($currentPiece, 'data-stock-state', 'available')
        ->assertEnabled($currentPiece)
        ->assertAttributeContains($currentPiece, 'aria-label', 'disponível')
        ->assertAttribute($waitingPiece, 'data-stock-state', 'waiting')
        ->assertDisabled($waitingPiece)
        ->assertAttributeContains($waitingPiece, 'aria-label', 'aguardando turno')
        ->assertScript('getComputedStyle(document.querySelector('.json_encode($waitingPiece).')).borderStyle', 'dashed')
        ->keys($currentPiece, 'Enter')
        ->assertAttribute($currentPiece, 'aria-pressed', 'true')
        ->assertScript('document.activeElement?.dataset.testid', 'piece-'.$starter.'-1-1')
        ->assertScript('getComputedStyle(document.querySelector('.json_encode($currentPiece).')).outlineStyle !== "none"', true)
        ->assertVisible($currentPiece.' [x-show]')
        ->click('[data-testid="crazy-cell-0"]')
        ->assertAttribute('[data-testid="crazy-cell-0"]', 'data-top', $starter.'-1-1')
        ->assertAttribute($currentPiece, 'data-stock-state', 'used')
        ->assertDisabled($currentPiece)
        ->assertAttributeContains($currentPiece, 'aria-label', 'usada')
        ->assertSeeIn($currentPiece, '✓')
        ->assertAttribute($waitingPiece, 'data-stock-state', 'available')
        ->assertEnabled($waitingPiece)
        ->assertScript('getComputedStyle(document.querySelector('.json_encode($currentPiece).')).borderStyle', 'solid')
        ->assertNoJavaScriptErrors();
});

it('keeps selection and placement close and touch targets comfortable at :dataset', function (int $width, int $height, string $theme) {
    $page = visit('/maluca')->resize($width, $height)->select('[data-theme-selector]', $theme);
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $piece = '[data-testid="piece-'.$starter.'-1-1"]';

    $page->click($piece)
        ->assertAttribute($piece, 'aria-pressed', 'true')
        ->assertScript('(() => { const board = document.querySelector("[data-testid=crazy-board]").getBoundingClientRect(); const selector = document.querySelector("[data-testid=piece-selector]").getBoundingClientRect(); const pieces = [...document.querySelectorAll("button[data-piece]")]; const cells = [...document.querySelectorAll("[data-testid^=crazy-cell-]")]; return board.bottom <= innerHeight && selector.bottom <= innerHeight && Math.min(...pieces.map(piece => piece.getBoundingClientRect().width), ...pieces.map(piece => piece.getBoundingClientRect().height), ...cells.map(cell => cell.getBoundingClientRect().width), ...cells.map(cell => cell.getBoundingClientRect().height)) >= 44 && document.documentElement.scrollWidth <= innerWidth; })()', true)
        ->click('[data-testid="crazy-cell-8"]')
        ->assertAttribute('[data-testid="crazy-cell-8"]', 'data-top', $starter.'-1-1')
        ->assertAttribute($piece, 'data-stock-state', 'used')
        ->assertScript('window.scrollY', 0)
        ->click('[data-testid="new-match-button"]')
        ->assertScript('(() => { const dialog = document.querySelector("[data-testid=new-match-dialog]"); const bounds = dialog.getBoundingClientRect(); return dialog.open && bounds.top >= 0 && bounds.bottom <= innerHeight; })()', true)
        ->click('Cancelar')
        ->assertAttribute('[data-testid="crazy-cell-8"]', 'data-top', $starter.'-1-1')
        ->assertNoJavaScriptErrors();
})->with([
    '740 light' => [740, 360, 'light'],
    '740 dark' => [740, 360, 'dark'],
    '800 light' => [800, 360, 'light'],
    '800 dark' => [800, 360, 'dark'],
]);
