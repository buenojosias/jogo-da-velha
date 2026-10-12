<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

function twoLinesBrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $status = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($status);

    return strtolower(substr($status, -1));
}

function twoLinesBrowserPlace(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $page->click('[data-testid="cell-'.$cell.'"]');
    awaitBrowserCellMark($page, $cell, $mark);
}

it('wins by two lines, highlights both and keeps the score on the next round', function () {
    $page = visit('/')->click('a[href$="/2-linhas"]')
        ->assertScript('location.pathname', '/2-linhas')
        ->assertCount('[data-testid^="cell-"]', 25);
    $starter = twoLinesBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 5, 1, 6, 2, 9, 10, 14, 11, 19, 12] as $turn => $cell) {
        twoLinesBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
        if ($turn === 4) {
            $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
                ->assertScript('document.querySelector("[data-testid=result-dialog]")?.open ?? false', false);
        }
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 6)
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->refresh()
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertNoJavaScriptErrors();
});

it('shows a draw and resets only after the next-round action', function () {
    $page = visit('/2-linhas');
    $starter = twoLinesBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    $marks = str_split('ooxxoxooxxoxxooxooxxoxxxo');
    $firstCells = array_keys($marks, 'x', true);
    $secondCells = array_keys($marks, 'o', true);
    for ($turn = 0; $turn < 25; $turn++) {
        $cell = $turn % 2 === 0 ? array_shift($firstCells) : array_shift($secondCells);
        twoLinesBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Empate!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 0)
        ->assertSeeIn('[data-testid="score-draws"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="score-draws"]', '1')
        ->assertNoJavaScriptErrors();
});

it('confirms a new match and preserves the current round when confirmation is cancelled', function () {
    $page = visit('/2-linhas');
    $starter = twoLinesBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    twoLinesBrowserPlace($page, 0, $starter);
    $page->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('Cancelar')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', $starter)
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/2-linhas')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('keeps all twenty-five cells and controls usable at :dataset', function (int $width, int $height, string $theme) {
    $page = visit('/2-linhas')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 25)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = twoLinesBrowserStarter($page);
    twoLinesBrowserPlace($page, 24, $starter);
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-24]").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);
