<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

function hiddenBrowserStarter(AwaitableWebpage|PendingAwaitablePage $page): string
{
    $text = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($text);

    return strtolower(substr($text, -1));
}

function hiddenBrowserChoose(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $next): void
{
    $page->click('[data-testid="cell-'.$cell.'"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($next));
}

function hiddenBrowserAssertFeedbackPalette(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $kind, string $colorToken): void
{
    $page->assertAttribute('[data-testid="cell-'.$cell.'"]', 'data-feedback-kind', $kind)
        ->assertScript('(() => { const cell = document.querySelector("[data-testid=cell-'.$cell.']"); const probe = document.createElement("span"); probe.style.color = "var(--ds-'.$colorToken.')"; cell.append(probe); const tokenColor = getComputedStyle(probe).color; probe.remove(); return getComputedStyle(cell).borderColor === tokenColor && getComputedStyle(cell).borderWidth === "2px" && getComputedStyle(cell).backgroundColor !== getComputedStyle(document.querySelector("[data-testid=cell-15]")).backgroundColor && getComputedStyle(cell, "::after").content === "none"; })()', true);
}

it('opens from the catalogue and hides all occupied positions through Livewire updates and reload', function () {
    $page = visit('/')->click('a[href$="/4-3-oculto"]')
        ->assertScript('location.pathname', '/4-3-oculto')
        ->assertCount('[data-testid^="cell-"]', 16);
    $starter = hiddenBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    hiddenBrowserChoose($page, 0, $other);
    hiddenBrowserChoose($page, 1, $starter);
    $page->assertScript('Array.from(document.querySelectorAll("[data-testid^=cell-]")).every(cell => !cell.hasAttribute("data-mark") && cell.textContent.trim() === "?" && !cell.disabled)', true)
        ->assertScript('Object.keys(Object.values(window.Livewire.all())[0].canonical).sort().join(",")', 'feedbackMessage,matchId')
        ->refresh()
        ->assertScript('Array.from(document.querySelectorAll("[data-testid^=cell-]")).every(cell => !cell.hasAttribute("data-mark") && cell.textContent.trim() === "?")', true)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($starter))
        ->assertNoJavaScriptErrors();
});

it('consumes a turn on an opponent piece and hides the destination of an own-piece move', function () {
    $page = visit('/4-3-oculto');
    $starter = hiddenBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    hiddenBrowserChoose($page, 0, $other);
    $page->assertAttribute('[data-testid="cell-0"]', 'data-feedback-kind', 'placed-'.$starter)
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=cell-0]")).animationName', 'hidden-cell-feedback-fade')
        ->select('[data-theme-selector]', 'dark')
        ->assertScript('document.documentElement.dataset.theme', 'dark');
    hiddenBrowserChoose($page, 0, $starter);
    $page->assertSeeIn('[data-testid="hidden-feedback"]', 'Você perdeu a vez.')
        ->assertAttribute('[data-testid="cell-0"]', 'data-feedback-kind', 'turn-lost');
    hiddenBrowserChoose($page, 0, $other);
    $page->assertSeeIn('[data-testid="hidden-feedback"]', 'Uma peça foi movimentada.')
        ->assertAttribute('[data-testid="cell-0"]', 'data-feedback-kind', 'own-move')
        ->assertScript('document.querySelectorAll("[data-feedback-kind]").length', 1)
        ->assertScript('Array.from(document.querySelectorAll("[data-testid^=cell-]")).every(cell => !cell.hasAttribute("data-mark") && cell.textContent.trim() === "?")', true)
        ->click('[data-testid="match-status"]')
        ->assertScript('async function() { const cell = document.querySelector("[data-testid=cell-0]"); if (!cell.hasAttribute("data-feedback-kind")) return true; return await new Promise(resolve => { const observer = new MutationObserver(() => { if (!cell.hasAttribute("data-feedback-kind")) { observer.disconnect(); resolve(true); } }); observer.observe(cell, { attributes: true, attributeFilter: ["data-feedback-kind"] }); setTimeout(() => { observer.disconnect(); resolve(false); }, 1800); }); }', true)
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=cell-0]")).borderColor === getComputedStyle(document.querySelector("[data-testid=cell-15]")).borderColor && getComputedStyle(document.querySelector("[data-testid=cell-0]")).backgroundColor === getComputedStyle(document.querySelector("[data-testid=cell-15]")).backgroundColor', true)
        ->click('Nova rodada')
        ->assertScript('document.querySelectorAll("[data-feedback-kind]").length', 0)
        ->assertNoJavaScriptErrors();

    $reduced = visit('/4-3-oculto', ['reducedMotion' => 'reduce']);
    $reducedStarter = hiddenBrowserStarter($reduced);
    $reducedOther = $reducedStarter === 'x' ? 'o' : 'x';
    $reduced->assertScript('matchMedia("(prefers-reduced-motion: reduce)").matches', true);
    foreach (['light', 'dark'] as $theme) {
        $reduced->select('[data-theme-selector]', $theme)
            ->assertScript('document.documentElement.dataset.theme', $theme);
        hiddenBrowserChoose($reduced, 1, $reducedOther);
        hiddenBrowserAssertFeedbackPalette($reduced, 1, 'placed-'.$reducedStarter, $reducedStarter === 'x' ? 'x' : 'o');
        hiddenBrowserChoose($reduced, 2, $reducedStarter);
        hiddenBrowserAssertFeedbackPalette($reduced, 2, 'placed-'.$reducedOther, $reducedOther === 'x' ? 'x' : 'o');
        hiddenBrowserChoose($reduced, 2, $reducedOther);
        hiddenBrowserAssertFeedbackPalette($reduced, 2, 'turn-lost', 'warning');
        hiddenBrowserChoose($reduced, 2, $reducedStarter);
        hiddenBrowserAssertFeedbackPalette($reduced, 2, 'own-move', 'turquoise');
        if ($theme === 'light') {
            $reduced->click('Nova rodada');
            [$reducedStarter, $reducedOther] = [$reducedOther, $reducedStarter];
        }
    }
    $reduced->assertScript('getComputedStyle(document.querySelector("[data-testid=cell-2]")).animationName', 'none')
        ->assertScript('async function() { const cell = document.querySelector("[data-testid=cell-2]"); if (!cell.hasAttribute("data-feedback-kind")) return true; return await new Promise(resolve => { const observer = new MutationObserver(() => { if (!cell.hasAttribute("data-feedback-kind")) { observer.disconnect(); resolve(true); } }); observer.observe(cell, { attributes: true, attributeFilter: ["data-feedback-kind"] }); setTimeout(() => { observer.disconnect(); resolve(false); }, 1800); }); }', true)
        ->click('[data-testid="match-status"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=cell-2]")).borderColor === getComputedStyle(document.querySelector("[data-testid=cell-15]")).borderColor', true)
        ->assertNoJavaScriptErrors();
});

it('reveals three winning cells, opens the result dialog and preserves score on the next round', function () {
    $page = visit('/4-3-oculto');
    $starter = hiddenBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 4, 1, 5] as $turn => $cell) {
        hiddenBrowserChoose($page, $cell, $turn % 2 === 0 ? $other : $starter);
    }
    $page->click('[data-testid="cell-2"]')
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->click('Próxima rodada')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertSeeIn('[data-testid="score-'.$starter.'"]', '1')
        ->assertScript('document.querySelector("[data-testid=cell-0]").hasAttribute("data-mark")', false)
        ->assertNoJavaScriptErrors();
});

it('uses confirmation for a fresh match and keeps all cells available', function () {
    $page = visit('/4-3-oculto');
    $starter = hiddenBrowserStarter($page);
    $other = $starter === 'x' ? 'o' : 'x';
    hiddenBrowserChoose($page, 0, $other);
    $page->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/4-3-oculto')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertSeeIn('[data-testid="score-x"]', '0')
        ->assertSeeIn('[data-testid="score-o"]', '0')
        ->assertNoJavaScriptErrors();
});

it('keeps the hidden board usable at :dataset', function (int $width, int $height, string $theme) {
    $page = visit('/4-3-oculto')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="hidden-board"]')
        ->assertVisible('[data-testid="match-status"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertCount('[data-testid^="cell-"]', 16)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $starter = hiddenBrowserStarter($page);
    hiddenBrowserChoose($page, 15, $starter === 'x' ? 'o' : 'x');
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=cell-15]").hasAttribute("data-mark")', false)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);
