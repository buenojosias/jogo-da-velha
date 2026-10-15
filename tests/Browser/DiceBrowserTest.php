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
            const ready = () => {
                const stage = document.querySelector('[data-testid=dice-face]');
                return snapshot() !== before && stage?.dataset.face && stage.dataset.phase === 'idle' && stage.getAttribute('aria-label') !== 'Rolando dado';
            };
            if (ready()) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (ready()) {
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

function diceBrowserWaitForFace(AwaitableWebpage|PendingAwaitablePage $page): int
{
    $page->script(<<<'JS'
        (async () => {
            const stage = document.querySelector('[data-testid=dice-face]');
            if (stage.dataset.face) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (stage.dataset.face) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(stage, { attributes: true, attributeFilter: ['data-face'] });
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice result was not revealed')); }, 10000);
            });
        })()
        JS);

    return (int) $page->script('document.querySelector("[data-testid=dice-face]").dataset.face');
}

it('keeps the server result hidden until the die finishes landing', function () {
    $page = visit('/dado')->assertNoJavaScriptErrors();
    $before = $page->script('document.querySelector("[data-testid=dice-instruction]").textContent.trim()');
    $page->script('window.__diceRollStartedAt = performance.now()');

    $page->click('[data-testid="roll-button"]')
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.phase', 'spinning')
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', '')
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true)
        ->assertScript('document.querySelector("[data-testid=dice-board-esquerdo]").closest("[inert]") !== null', true)
        ->assertScript('document.querySelector("[data-testid=dice-instruction]").textContent.trim()', $before);

    $page->script(<<<'JS'
        (async () => {
            if (document.querySelector('[data-testid=dice-face]')?.dataset.face) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (document.querySelector('[data-testid=dice-face]')?.dataset.face) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(document.body, { subtree: true, attributes: true });
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice result was not revealed')); }, 7000);
            });
        })()
        JS);

    $face = (int) $page->script('document.querySelector("[data-testid=dice-face]").dataset.face');
    $elapsed = $page->script('performance.now() - window.__diceRollStartedAt');
    expect($face)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(6);
    expect($elapsed)->toBeGreaterThanOrEqual(2300);
    $page->assertScript('(() => { const faces = [...document.querySelectorAll(".dice-flat-face")]; const visible = faces.filter(face => getComputedStyle(face).display !== "none"); return visible.length === 1 && visible[0] === faces['.($face - 1).'] && visible[0].querySelectorAll(".dice-pip").length === '.$face.'; })()', true)
        ->assertNoJavaScriptErrors();
});

it('keeps the dice faces changing until the server result appears at :dataset', function (int $width, int $height) {
    $page = visit('/dado', ['reducedMotion' => 'no-preference'])->resize($width, $height);
    $before = $page->script('document.querySelector("[data-testid=dice-instruction]").textContent.trim()');
    $page->script(<<<'JS'
        (() => {
            const stage = document.querySelector('[data-testid=dice-face]');
            const faces = [...stage.querySelectorAll('.dice-flat-face')];
            window.__diceTrace = [];
            window.__diceFinalAt = null;
            window.__diceStartedAt = performance.now();
            let previous = null;
            new MutationObserver(() => {
                if (stage.dataset.face) {
                    window.__diceFinalAt ??= performance.now();
                    return;
                }
                const visible = faces.findIndex(face => getComputedStyle(face).display !== 'none') + 1;
                if (visible && visible !== previous) {
                    window.__diceTrace.push({ face: visible, at: performance.now() });
                    previous = visible;
                }
            }).observe(stage, { subtree: true, attributes: true, attributeFilter: ['style', 'data-face'] });
        })()
        JS);

    $early = $page->script(<<<'JS'
        (async () => {
            document.querySelector('[data-testid=roll-button]').click();
            await new Promise(resolve => setTimeout(resolve, 1000));
            return {
                trace: window.__diceTrace.length,
                phase: document.querySelector('[data-testid=dice-face]').dataset.phase,
                face: document.querySelector('[data-testid=dice-face]').dataset.face,
                disabled: document.querySelector('[data-testid=roll-button]').disabled,
                instruction: document.querySelector('[data-testid=dice-instruction]').textContent.trim(),
            };
        })()
        JS);
    expect($early['trace'])->toBeGreaterThanOrEqual(7);
    expect($early['phase'])->toBe('spinning');
    expect($early['face'])->toBe('');
    expect($early['disabled'])->toBeTrue();
    expect($early['instruction'])->toBe($before);

    $face = diceBrowserWaitForFace($page);
    $page->assertScript('(() => { const trace = window.__diceTrace; const gaps = trace.slice(1).map((entry, index) => entry.at - trace[index].at); return trace.length >= 18 && trace.every((entry, index) => index === 0 || entry.face !== trace[index - 1].face) && gaps.every(gap => gap < 300) && window.__diceFinalAt - trace.at(-1).at < 300 && window.__diceFinalAt - window.__diceStartedAt >= 2300; })()', true)
        ->assertScript('(() => { const stage = document.querySelector("[data-testid=dice-face]"); const faces = [...stage.querySelectorAll(".dice-flat-face")]; return faces.findIndex(face => getComputedStyle(face).display !== "none") + 1 === Number(stage.dataset.face); })()', true)
        ->assertScript('document.querySelector("[data-testid=dice-instruction]").textContent.includes("Dado "+document.querySelector("[data-testid=dice-face]").dataset.face)', true)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true);
    $count = $page->script('window.__diceTrace.length');
    $page->script('new Promise(resolve => setTimeout(resolve, 400))');
    $page->assertScript('window.__diceTrace.length', $count)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.phase', 'idle')
        ->assertNoJavaScriptErrors();
})->with([
    'smartphone' => [320, 800],
    'tablet' => [768, 900],
    'desktop' => [1280, 900],
]);

it('continues cycling while a slow server response is pending and blocks another roll', function () {
    $page = visit('/dado', ['reducedMotion' => 'no-preference'])->resize(320, 800);
    $pending = $page->script(<<<'JS'
        (async () => {
            const originalFetch = window.fetch;
            const stage = document.querySelector('[data-testid=dice-face]');
            const faces = [...stage.querySelectorAll('.dice-flat-face')];
            let previous = 1;
            window.__diceChanges = 0;
            window.__diceLastChange = performance.now();
            window.__diceRequestCount = 0;
            new MutationObserver(() => {
                const visible = faces.findIndex(face => getComputedStyle(face).display !== 'none') + 1;
                if (visible && visible !== previous) {
                    previous = visible;
                    window.__diceChanges++;
                    window.__diceLastChange = performance.now();
                }
            }).observe(stage, { subtree: true, attributes: true, attributeFilter: ['style'] });
            window.fetch = (...arguments) => {
                window.__diceRequestCount++;
                return originalFetch(...arguments).then(async response => {
                    await new Promise(resolve => { window.__releaseRollResponse = resolve; });
                    return response;
                });
            };
            document.querySelector('[data-testid=roll-button]').click();
            await new Promise(resolve => setTimeout(resolve, 2800));
            return {
                phase: stage.dataset.phase,
                face: stage.dataset.face,
                changes: window.__diceChanges,
                lastChangeAge: performance.now() - window.__diceLastChange,
                requests: window.__diceRequestCount,
                responseReady: typeof window.__releaseRollResponse === 'function',
                blocked: document.querySelector('[data-testid=roll-button]').disabled,
            };
        })()
        JS);
    expect($pending['phase'])->toBe('spinning');
    expect($pending['face'])->toBe('');
    expect($pending['changes'])->toBeGreaterThanOrEqual(25);
    expect($pending['lastChangeAge'])->toBeLessThan(250);
    expect($pending['requests'])->toBe(1);
    expect($pending['responseReady'])->toBeTrue();
    expect($pending['blocked'])->toBeTrue();

    $page->script('document.querySelector("[data-testid=roll-button]").click(); window.__releaseRollResponse()');
    $face = diceBrowserWaitForFace($page);
    $page->assertScript('window.__diceRequestCount', 1)
        ->assertScript('(() => { const stage = document.querySelector("[data-testid=dice-face]"); return [...stage.querySelectorAll(".dice-flat-face")].findIndex(face => getComputedStyle(face).display !== "none") + 1 === Number(stage.dataset.face); })()', true)
        ->assertScript('document.querySelector("[data-testid=dice-instruction]").textContent.includes("Dado "+document.querySelector("[data-testid=dice-face]").dataset.face)', true)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertNoJavaScriptErrors();
});

it('respects reduced motion without revealing the result before a pending response', function () {
    $page = visit('/dado', ['reducedMotion' => 'reduce'])->resize(320, 800)
        ->assertScript('matchMedia("(prefers-reduced-motion: reduce)").matches', true);
    $pending = $page->script(<<<'JS'
        (async () => {
            const originalFetch = window.fetch;
            const stage = document.querySelector('[data-testid=dice-face]');
            const faces = [...stage.querySelectorAll('.dice-flat-face')];
            const initialFace = faces.findIndex(face => getComputedStyle(face).display !== 'none') + 1;
            let changes = 0;
            new MutationObserver(() => { changes++; }).observe(stage.querySelector('.dice-flat'), { subtree: true, attributes: true, attributeFilter: ['style'] });
            window.fetch = (...arguments) => originalFetch(...arguments).then(async response => {
                await new Promise(resolve => { window.__releaseRollResponse = resolve; });
                return response;
            });
            document.querySelector('[data-testid=roll-button]').click();
            await new Promise(resolve => setTimeout(resolve, 400));
            return {
                phase: stage.dataset.phase,
                face: stage.dataset.face,
                changes,
                initialFace,
                currentFace: faces.findIndex(face => getComputedStyle(face).display !== 'none') + 1,
                animation: getComputedStyle(stage.querySelector('.dice-flat')).animationName,
                responseReady: typeof window.__releaseRollResponse === 'function',
            };
        })()
        JS);
    expect($pending)->toBe([
        'phase' => 'spinning',
        'face' => '',
        'changes' => 0,
        'initialFace' => 1,
        'currentFace' => 1,
        'animation' => 'none',
        'responseReady' => true,
    ]);

    $page->script('window.__releaseRollResponse()');
    $face = diceBrowserWaitForFace($page);
    $page->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.phase', 'idle')
        ->assertNoJavaScriptErrors();
});

it('stops the dice animation and unlocks the roll button after a network failure', function () {
    $page = visit('/dado', ['reducedMotion' => 'no-preference']);
    $page->script(<<<'JS'
        (() => {
            window.fetch = () => Promise.reject(new TypeError('Network unavailable'));
            document.querySelector('[data-testid=roll-button]').click();
        })()
        JS);
    $page->script(<<<'JS'
        (async () => {
            const error = document.querySelector('[data-testid=dice-roll-error]');
            if (error.textContent.trim()) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (error.textContent.trim()) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(error, { subtree: true, childList: true, characterData: true });
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice error was not shown')); }, 5000);
            });
        })()
        JS);

    $page->assertSeeIn('[data-testid="dice-roll-error"]', 'Não foi possível confirmar a rolagem')
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.phase', 'idle')
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', '')
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', false)
        ->assertScript('Alpine.$data(document.querySelector(".dice-game")).faceFrame', null)
        ->assertNoJavaScriptErrors();
});

it('rolls on the server, retains a pending result and applies only an authorized placement', function () {
    $page = visit('/')->click('a[href$="/dado"]')
        ->assertScript('location.pathname', '/dado')
        ->assertCount('[data-testid^="cell-"]', 18)
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', false);
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $page->click('[data-testid="dice-board-esquerdo"] [data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'Role o dado antes');
    $page->script('window.__dicePageSentinel = 1');
    $face = diceBrowserRoll($page);
    expect($face)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(6);
    $side = $face <= 3 ? 'esquerdo' : 'direito';
    $otherSide = $side === 'esquerdo' ? 'direito' : 'esquerdo';
    $column = ($face - 1) % 3;
    $page->assertScript('document.querySelector("[data-testid=dice-face]").dataset.phase', 'idle')
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true)
        ->assertScript('(() => { const button = document.querySelector("[data-testid=roll-button]"); const face = document.querySelector("[data-testid=dice-face]").dataset.face; button.click(); return button.disabled && document.querySelector("[data-testid=dice-face]").dataset.face === face; })()', true)
        ->refresh()
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true)
        ->click('[data-testid="dice-board-'.$otherSide.'"] [data-testid="cell-'.$column.'"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'coluna indicada')
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true);
    diceBrowserPlace($page, $side, $column, $starter);
    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.($starter === 'x' ? 'O' : 'X'))
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', false)
        ->assertNoJavaScriptErrors();
});

it('keeps the cells unchanged and hides processing messages during dice actions in :dataset', function (string $theme) {
    $page = visit('/dado')->resize(320, 800)->select('[data-theme-selector]', $theme);
    $page->script(<<<'JS'
        (() => {
            window.__diceProcessingMessageSeen = false;
            new MutationObserver(() => {
                if (document.body.textContent.includes('Processando jogada')) window.__diceProcessingMessageSeen = true;
            }).observe(document.body, { subtree: true, childList: true, attributes: true, characterData: true });
        })()
        JS);

    $face = diceBrowserRoll($page);
    $page->assertScript('window.__diceProcessingMessageSeen', false)
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true);

    $selector = '[data-testid=dice-board-'.($face <= 3 ? 'esquerdo' : 'direito').'] [data-testid=cell-'.(($face - 1) % 3).']';
    $page->script(<<<'JS'
        (() => {
            const originalFetch = window.fetch;
            window.__diceCellBackground = getComputedStyle(document.querySelector('.dice-board > button:not(:disabled)')).backgroundColor;
            window.fetch = (...arguments) => originalFetch(...arguments).then(async response => {
                await new Promise(resolve => { window.__releaseDiceResponse = resolve; });
                return response;
            });
        })()
        JS);
    $page->click($selector)
        ->assertScript('typeof window.__releaseDiceResponse', 'function')
        ->assertScript('(() => { const cells = [...document.querySelectorAll(".dice-board > button")]; return cells.every(cell => cell.disabled && (cell.dataset.mark !== "" || getComputedStyle(cell).backgroundColor === window.__diceCellBackground)); })()', true)
        ->assertScript('window.__diceProcessingMessageSeen', false);
    $page->script('document.querySelector('.json_encode($selector, JSON_THROW_ON_ERROR).').click(); window.__releaseDiceResponse()');
    $page->script(<<<'JS'
        (async () => {
            if (document.querySelectorAll('.dice-board [data-mark]:not([data-mark=""])').length === 1) return true;
            return await new Promise((resolve, reject) => {
                const observer = new MutationObserver(() => {
                    if (document.querySelectorAll('.dice-board [data-mark]:not([data-mark=""])').length === 1) {
                        observer.disconnect();
                        clearTimeout(timeout);
                        resolve(true);
                    }
                });
                observer.observe(document.body, { subtree: true, attributes: true });
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice placement did not update the board')); }, 10000);
            });
        })()
        JS);
    $page->assertScript('document.querySelectorAll(".dice-board [data-mark]:not([data-mark=\"\"])").length', 1)
        ->assertScript('window.__diceProcessingMessageSeen', false)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

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
    diceBrowserRoll($page);
    $page->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true)
        ->click('[x-ref="restartButton"]')
        ->assertScript('document.querySelector("[data-testid=roll-button]").disabled', false)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', '')
        ->assertScript('(() => [...document.querySelectorAll(".dice-flat-face")].findIndex(face => getComputedStyle(face).display !== "none") === 0)()', true);
    $face = diceBrowserRoll($page);
    $page->assertScript('document.querySelector("[data-testid=roll-button]").disabled', true);
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
    $boardLayout = <<<'JS'
        (() => {
            const boards = ['esquerdo', 'direito'].map(side => document.querySelector(`[data-testid=dice-board-${side}]`));
            const bounds = boards.map(board => board.getBoundingClientRect());
            const cells = boards.map(board => [...board.querySelectorAll('[data-testid^=cell-]')].map(element => ({ ...element.getBoundingClientRect().toJSON(), element })));
            const tolerance = 1;

            return bounds[0].right + tolerance <= bounds[1].left
                && Math.abs(bounds[0].top - bounds[1].top) <= tolerance
                && Math.abs(bounds[0].width - bounds[1].width) <= tolerance
                && bounds.every((rect, index) => rect.left >= 0 && rect.right <= innerWidth
                    && cells[index].length === 9
                    && cells[index].every((cell, cellIndex) =>
                        Math.abs(cell.width - cell.height) <= tolerance
                        && Math.abs(cell.width - cells[index][0].width) <= tolerance
                        && cell.width >= (innerWidth <= 320 ? 38 : 44)
                        && cell.left >= rect.left - tolerance
                        && cell.right <= rect.right + tolerance
                        && cell.top >= rect.top - tolerance
                        && cell.bottom <= rect.bottom + tolerance
                        && cell.element.scrollWidth <= cell.element.clientWidth + tolerance
                        && (cellIndex % 3 === 0 || cells[index][cellIndex - 1].right <= cell.left + tolerance)
                    ));
        })()
        JS;

    $page = visit('/dado')->resize($width, $height);
    $page->select('[data-theme-selector]', $theme)
        ->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertVisible('[data-testid="dice-board-esquerdo"]')
        ->assertVisible('[data-testid="dice-board-direito"]')
        ->assertVisible('[data-testid="roll-button"]')
        ->assertCount('[data-testid^="cell-"]', 18)
        ->assertScript('document.documentElement.scrollWidth <= innerWidth', true)
        ->assertScript($boardLayout, true)
        ->click('[data-testid="dice-board-esquerdo"] [data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'Role o dado antes');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $face = diceBrowserRoll($page);
    diceBrowserPlace($page, $face <= 3 ? 'esquerdo' : 'direito', ($face - 1) % 3, $starter);

    $page->refresh()->assertScript('document.documentElement.dataset.theme', $theme)
        ->assertScript('document.querySelector("[data-testid=dice-face]").dataset.face', (string) $face)
        ->assertScript($boardLayout, true)
        ->assertNoJavaScriptErrors();
})->with([
    '320 light' => [320, 800, 'light'],
    '320 dark' => [320, 800, 'dark'],
    '360 light' => [360, 800, 'light'],
    '360 dark' => [360, 800, 'dark'],
    '375 light' => [375, 812, 'light'],
    '375 dark' => [375, 812, 'dark'],
    '390 light' => [390, 844, 'light'],
    '390 dark' => [390, 844, 'dark'],
    '414 light' => [414, 896, 'light'],
    '414 dark' => [414, 896, 'dark'],
    '768 light' => [768, 900, 'light'],
    '768 dark' => [768, 900, 'dark'],
    '1280 light' => [1280, 900, 'light'],
    '1280 dark' => [1280, 900, 'dark'],
]);
