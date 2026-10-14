<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('plays, wins, scores, and restarts a classic 3x3 match', function () {
    $page = visit('/3-basico')
        ->assertCount('[data-testid^="cell-"]', 9)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0');

    $initialPlayer = publicClassicStarter($page);
    $otherPlayer = $initialPlayer === 'x' ? 'o' : 'x';
    $page->select('[data-theme-selector]', 'dark');
    $page->script('window.__publicBoardMarker = "same-document";');

    $page
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="cell-0"]', $initialPlayer)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($otherPlayer))
        ->assertDisabled('[data-testid="cell-0"]')
        ->script('document.querySelector("[data-testid=cell-0]").click()');

    $page->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1');

    foreach ([3, 1, 4, 2] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($initialPlayer).' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="score-'.$initialPlayer.'"]', '1')
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertScript('window.__publicBoardMarker', 'same-document')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($otherPlayer))
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertSeeIn('[data-testid="score-'.$initialPlayer.'"]', '1')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertNoJavaScriptErrors();
});

it('alternates the classic starting player on the regular restart button', function () {
    $page = visit('/3-basico');
    $initialPlayer = publicClassicStarter($page);
    $expectedPlayer = $initialPlayer === 'x' ? 'o' : 'x';

    $page
        ->click('Nova rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($expectedPlayer))
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertNoJavaScriptErrors();
});

it('resumes a public match and confirms before creating a new one', function () {
    $page = visit('/3-basico');
    $initialPlayer = publicClassicStarter($page);

    $page
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->refresh()
        ->assertSeeIn('[data-testid="cell-0"]', $initialPlayer)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").getAttribute("aria-modal")', 'true')
        ->keys('[data-testid="new-match-dialog"]', 'Escape')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', false)
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', false)
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/3-basico')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 0')
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertSeeIn('[data-testid="score-draws"]', '0')
        ->assertNoJavaScriptErrors();
});

function publicClassicStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($turn);

    return strtolower(substr($turn, -1));
}
