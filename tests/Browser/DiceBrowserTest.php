<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

function diceBrowserRoll(AwaitableWebpage|PendingAwaitablePage $page): int
{
    $before = $page->script('JSON.stringify([document.querySelector("[data-testid=match-status]")?.textContent, document.querySelector("[data-testid=dice-instruction]")?.textContent, document.querySelector("[data-testid=dice-roll-notice]")?.textContent, document.querySelector("[data-testid=dice-face]")?.dataset.face])');
    $page->click('[data-testid="roll-button"]');
    $encoded = json_encode($before, JSON_THROW_ON_ERROR);
    $page->script(<<<JS
        (async () => {
            const before = {$encoded};
            const snapshot = () => JSON.stringify([document.querySelector('[data-testid=match-status]')?.textContent, document.querySelector('[data-testid=dice-instruction]')?.textContent, document.querySelector('[data-testid=dice-roll-notice]')?.textContent, document.querySelector('[data-testid=dice-face]')?.dataset.face]);
            if (snapshot() !== before) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (snapshot() !== before) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(document.body, { subtree: true, childList: true, attributes: true, characterData: true });
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice roll did not update the page')); }, 10000);
            });
        })()
        JS);

    return (int) $page->script('document.querySelector("[data-testid=dice-face]").dataset.face');
}

function diceBrowserPlace(AwaitableWebpage|PendingAwaitablePage $page, string $side, int $cell, string $mark): void
{
    $selector = '[data-testid=dice-board-'.$side.'] [data-testid=cell-'.$cell.']';
    $page->click($selector);
    $encodedSelector = json_encode($selector, JSON_THROW_ON_ERROR);
    $encodedMark = json_encode($mark, JSON_THROW_ON_ERROR);
    $page->script(<<<JS
        (async () => {
            const selector = {$encodedSelector};
            const expected = {$encodedMark};
            if (document.querySelector(selector)?.dataset.mark === expected) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (document.querySelector(selector)?.dataset.mark === expected) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(document.body, { subtree: true, childList: true, attributes: true });
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice placement did not update the board')); }, 10000);
            });
        })()
        JS);
}

it('rolls on the server, retains a pending result and applies only an authorized placement', function () {
    $page = visit('/')->click('a[href$="/dado"]')
        ->assertScript('location.pathname', '/dado')
        ->assertCount('[data-testid^="cell-"]', 18);
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $page->click('[data-testid="dice-board-esquerdo"] [data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'Role o dado antes');
    $page->script('window.__dicePageSentinel = 1');
    $face = diceBrowserRoll($page);
    expect($face)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(6);
    $side = $face <= 3 ? 'esquerdo' : 'direito';
    $otherSide = $side === 'esquerdo' ? 'direito' : 'esquerdo';
    $column = ($face - 1) % 3;
    $page->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true)
        ->refresh()
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true)
        ->click('[data-testid="dice-board-'.$otherSide.'"] [data-testid="cell-'.$column.'"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'coluna indicada');
    diceBrowserPlace($page, $side, $column, $starter);
    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.($starter === 'x' ? 'O' : 'X'))
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', false)
        ->assertNoJavaScriptErrors();
});

it('plays through a random dice game until the server displays a result and offers the next round', function () {
    $page = visit('/dado');
    for ($turn = 0; $turn < 120; $turn++) {
        $status = trim($page->script('document.querySelector("[data-testid=match-status]").textContent'));
        if (str_contains($status, 'venceu') || str_contains($status, 'Empate')) {
            break;
        }
        $mark = strtolower(substr($status, -1));
        $face = diceBrowserRoll($page);
        $pending = $page->script('document.querySelector("[data-testid=roll-button]").disabled');
        if (! $pending) {
            $page->assertSeeIn('[data-testid="dice-roll-notice"]', 'perdeu a vez');

            continue;
        }
        $side = $face <= 3 ? 'esquerdo' : 'direito';
        $column = ($face - 1) % 3;
        $cell = $page->script('(() => { const cells = document.querySelectorAll("[data-testid=dice-board-'.$side.'] [data-testid^=cell-]"); return [...cells].find(cell => Number(cell.dataset.testid.slice(5)) % 3 === '.$column.' && cell.dataset.mark === "")?.dataset.testid.slice(5) ?? null; })()');
        expect($cell)->not->toBeNull();
        diceBrowserPlace($page, $side, (int) $cell, $mark);
    }

    $page->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->click('Próxima rodada')
        ->assertScript('document.querySelectorAll("[data-testid^=cell-][data-mark]:not([data-mark=\"\"])").length', 0)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador')
        ->assertNoJavaScriptErrors();
});

it('confirms a new match while cancellation leaves the current boards intact', function () {
    $page = visit('/dado');
    $page->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', false);
    $face = diceBrowserRoll($page);
    $page->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertScript('location.pathname', '/dado')
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', '')
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', false)
        ->assertCount('[data-testid^="cell-"]', 18)
        ->assertNoJavaScriptErrors();
    expect($face)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(6);
});

it('keeps both boards accessible in :dataset', function (int $width, int $height, string $theme) {
    $page = visit('/dado')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="dice-board-esquerdo"]')
        ->assertVisible('[data-testid="dice-board-direito"]')
        ->assertVisible('[data-testid="roll-button"]')
        ->assertCount('[data-testid^="cell-"]', 18)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript('(() => [...document.querySelectorAll("[data-testid^=cell-]")].every(cell => { const rect = cell.getBoundingClientRect(); return rect.width >= 44 && rect.height >= 44 && rect.left >= 0 && rect.right <= innerWidth; }))()', true);
    $face = diceBrowserRoll($page);
    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertNoJavaScriptErrors();
})->with([
    'portrait light' => [360, 800, 'light'],
    'landscape dark' => [740, 360, 'dark'],
    'wide landscape light' => [800, 360, 'light'],
    'desktop dark' => [1280, 900, 'dark'],
]);
