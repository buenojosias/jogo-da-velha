<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

function fullBrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $status = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($status);

    return strtolower(substr($status, -1));
}

function fullBrowserPlace(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $page->click('[data-testid="cell-'.$cell.'"]');
    awaitBrowserCellMark($page, $cell, $mark);
}

it('scores a growing line without ending early and keeps points separate from match wins', function () {
    $page = visit('/')->click('a[href$="/5-cheia"]')
        ->assertScript('location.pathname', '/5-cheia')
        ->assertCount('[data-testid^="cell-"]', 25);
    $starter = fullBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 5, 1, 6, 2, 7, 3, 8, 4] as $turn => $cell) {
        fullBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
        if ($turn === 4) {
            $page->assertSeeIn('[data-testid="round-points-'.$starter.'"]', '3');
        }
        if ($turn === 6) {
            $page->assertSeeIn('[data-testid="round-points-'.$starter.'"]', '6');
        }
    }
    $page->assertSeeIn('[data-testid="round-points-'.$starter.'"]', '10')
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '0')
        ->assertScript('document.querySelector("[data-testid=result-dialog]")?.open ?? false', false)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 0)
        ->refresh()
        ->assertSeeIn('[data-testid="round-points-'.$starter.'"]', '10')
        ->assertNoJavaScriptErrors();
});

it('ends only at the last placement and shows final points in the result dialog', function () {
    $page = visit('/5-cheia');
    $starter = fullBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    $marks = str_split('ooxxoxoooxoxxoxoxooxxxxo');
    $firstCells = array_keys($marks, $starter, true);
    $otherCells = array_keys($marks, $other, true);
    for ($turn = 0; $turn < 24; $turn++) {
        $cell = $turn % 2 === 0 ? array_shift($firstCells) : array_shift($otherCells);
        fullBrowserPlace($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertScript('document.querySelector("[data-testid=result-dialog]")?.open ?? false', false);
    fullBrowserPlace($page, 24, $starter);
    $page->assertSeeIn('[data-testid="match-status"]', $starter === 'x' ? 'Jogador X venceu!' : 'Rodada empatada!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="result-round-points"]', $starter === 'x' ? 'X 15 · O 12' : 'X 12 · O 12')
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', $starter === 'x' ? '1' : '0')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="round-points-x"]', '0')
        ->assertSeeIn('[data-testid="round-points-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('requires confirmation for a new match and preserves the round if cancelled', function () {
    $page = visit('/5-cheia');
    $starter = fullBrowserStarter($page);
    fullBrowserPlace($page, 0, $starter);
    $page->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', $starter)
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/5-cheia')
        ->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('keeps all cells usable in :dataset', function (int $width, int $height, string $theme) {
    $page = visit('/5-cheia')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="round-points-x"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 25)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = fullBrowserStarter($page);
    fullBrowserPlace($page, 24, $starter);
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-24]").dataset.mark', $starter)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);
