<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('opens through the catalogue, resumes both phases and moves a selected piece', function () {
    $page = visit('/')
        ->click('a[href$="/3-mover"]')
        ->assertScript('location.pathname', '/3-mover')
        ->assertCount('[data-testid^="cell-"]', 9);
    $starter = publicMovementStarter($page);
    $opponent = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 1, 2] as $turn => $cell) {
        publicMovementPlace($page, $cell, $turn % 2 === 0 ? $starter : $opponent);
    }

    $page->refresh()
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', $starter);

    foreach ([3, 4, 6] as $turn => $cell) {
        publicMovementPlace($page, $cell, $turn % 2 === 0 ? $opponent : $starter);
    }

    $page->assertSeeIn('[data-testid="phase-instruction"]', 'Selecione uma peça e depois uma casa vazia')
        ->click('[data-testid="cell-0"]')->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')->assertScript('document.querySelector("[data-testid=cell-2]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')->assertScript('document.querySelector("[data-testid=cell-2]").dataset.selected', 'false')
        ->assertScript('document.querySelector("[data-testid=cell-1]").disabled', true)
        ->click('[data-testid="cell-0"]')->click('[data-testid="cell-5"]');
    awaitPublicMovementMark($page, 5, $starter);
    $page->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->refresh()
        ->assertScript('document.querySelector("[data-testid=cell-5]").dataset.mark', $starter)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($opponent))
        ->assertNoJavaScriptErrors();
});

it('presents a placement victory and keeps its score for the next round', function () {
    $page = visit('/3-mover');
    $starter = publicMovementStarter($page);
    $opponent = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 3, 1, 4, 2] as $turn => $cell) {
        publicMovementPlace($page, $cell, $turn % 2 === 0 ? $starter : $opponent);
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($opponent))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça')
        ->refresh()
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertNoJavaScriptErrors();
});

it('shows a movement victory and requires confirmation before a fresh match', function () {
    $page = visit('/3-mover');
    $starter = publicMovementStarter($page);
    $opponent = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        publicMovementPlace($page, $cell, $turn % 2 === 0 ? $starter : $opponent);
    }

    $page->click('[data-testid="cell-2"]')->click('[data-testid="cell-8"]');
    awaitPublicMovementMark($page, 8, $starter);
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('JSON.stringify([...document.querySelectorAll("[data-winning-cell=true]")].map(cell => Number(cell.dataset.testid.slice(5))))', '[0,4,8]')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->keys('[data-testid="result-dialog"]', 'Escape')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('Cancelar')
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/3-mover')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertNoJavaScriptErrors();
});

it('keeps an unfinished round when cancelling and clears selection after confirming a new match', function () {
    $page = visit('/3-mover');
    $starter = publicMovementStarter($page);
    $opponent = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        publicMovementPlace($page, $cell, $turn % 2 === 0 ? $starter : $opponent);
    }

    $page->click('[data-testid="cell-0"]')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="new-match-button"]')
        ->click('Cancelar')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', $starter)
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertNoJavaScriptErrors();
});

it('reconciles a selected piece after the server rejects a movement action', function () {
    $page = visit('/3-mover');
    $starter = publicMovementStarter($page);
    $opponent = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        publicMovementPlace($page, $cell, $turn % 2 === 0 ? $starter : $opponent);
    }

    $page->click('[data-testid="cell-0"]')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true');
    expect($page->script('(async () => { const root = document.querySelector(".experimental-match"); await window.Livewire.find(root.getAttribute("wire:id")).move(1, 5); return true; })()'))->toBeTrue();

    $page->assertSeeIn('[data-testid="rejection-message"]', 'Selecione uma peça sua.')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="cell-5"]');
    awaitPublicMovementMark($page, 5, $starter);
    $page->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertNoJavaScriptErrors();
});

it('keeps public controls usable at supported widths and in both themes', function (int $width, int $height, string $theme) {
    $page = visit('/3-mover')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="movement-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = publicMovementStarter($page);

    publicMovementPlace($page, 8, $starter);
    $page->refresh()
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-8]").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    'small portrait light' => [360, 800, 'light'],
    'short landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);

function publicMovementStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($turn);

    return strtolower(substr($turn, -1));
}

function publicMovementPlace(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $page->click('[data-testid="cell-'.$cell.'"]');
    awaitPublicMovementMark($page, $cell, $mark);
}

function awaitPublicMovementMark(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $selector = json_encode('[data-testid=cell-'.$cell.']', JSON_THROW_ON_ERROR);
    $expected = json_encode($mark, JSON_THROW_ON_ERROR);
    $expression = <<<JS
        (async () => {
            const selector = {$selector};
            const expected = {$expected};
            if (document.querySelector(selector)?.dataset.mark === expected) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (document.querySelector(selector)?.dataset.mark === expected) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(document.body, { subtree: true, childList: true, attributes: true, characterData: true });
                const timeout = setTimeout(() => {
                    observer.disconnect();
                    reject(new Error('Livewire did not confirm the selected cell'));
                }, 10000);
            });
        })()
        JS;

    expect($page->script($expression))->toBeTrue();
}
