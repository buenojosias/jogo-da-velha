<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('opens from the catalogue, resumes the movement phase and changes selection', function () {
    $page = visit('/')->click('a[href$="/4-3-mover"]')
        ->assertScript('location.pathname', '/4-3-mover')
        ->assertCount('[data-testid^="cell-"]', 16);
    $starter = move4BrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 1, 2] as $turn => $cell) {
        move4BrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->refresh()->assertSeeIn('[data-testid="phase-instruction"]', 'Coloque uma peça');
    foreach ([3, 4, 6] as $turn => $cell) {
        move4BrowserPlace($page, $cell, $turn % 2 === 0 ? $other : $starter);
    }
    $page->assertSeeIn('[data-testid="phase-instruction"]', 'Selecione uma peça e depois uma casa vazia')
        ->click('[data-testid="cell-0"]')->assertScript('document.querySelector("[data-testid=cell-0]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')->assertScript('document.querySelector("[data-testid=cell-2]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')->assertScript('document.querySelector("[data-testid=cell-2]").dataset.selected', 'false')
        ->assertScript('document.querySelector("[data-testid=cell-1]").disabled', true)
        ->click('[data-testid="cell-0"]')->click('[data-testid="cell-15"]');
    awaitMove4Mark($page, 15, $starter);
    $page->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->refresh()
        ->assertScript('document.querySelector("[data-testid=cell-15]").dataset.mark', $starter)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertNoJavaScriptErrors();
});

it('shows victory, winning cells, result dialog and preserved score after next round', function () {
    $page = visit('/4-3-mover');
    $starter = move4BrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 4, 1, 5, 2] as $turn => $cell) {
        move4BrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->refresh()
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertNoJavaScriptErrors();
});

it('wins by moving and requires confirmation for a fresh match', function () {
    $page = visit('/4-3-mover');
    $starter = move4BrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        move4BrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->click('[data-testid="cell-2"]')->click('[data-testid="cell-8"]');
    awaitMove4Mark($page, 8, $starter);
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('JSON.stringify([...document.querySelectorAll("[data-winning-cell=true]")].map(cell => Number(cell.dataset.testid.slice(5))))', '[0,4,8]')
        ->keys('[data-testid="result-dialog"]', 'Escape')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('Cancelar')
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/4-3-mover')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertNoJavaScriptErrors();
});

it('keeps sixteen cells and public controls usable in both themes at supported widths', function (int $width, int $height, string $theme) {
    $page = visit('/4-3-mover')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="movement-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = move4BrowserStarter($page);
    move4BrowserPlace($page, 15, $starter);
    $page->refresh()
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-15]").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);

function move4BrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($turn);

    return strtolower(substr($turn, -1));
}

function move4BrowserPlace(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $page->click('[data-testid="cell-'.$cell.'"]');
    awaitMove4Mark($page, $cell, $mark);
}

function awaitMove4Mark(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
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
