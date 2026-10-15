<?php

it('directs moves, marks a conquered subboard, and finishes a global line', function () {
    $page = visit('/ultimate');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $moves = [
        [0, 1], [1, 0], [0, 2], [2, 0], [0, 0],
        [6, 1], [1, 4], [4, 1], [1, 5], [5, 1], [1, 3],
        [3, 2], [2, 4], [4, 2], [2, 5], [5, 2], [2, 3],
    ];
    foreach ($moves as $turn => [$board, $cell]) {
        $mark = $turn % 2 === 0 ? $starter : ($starter === 'x' ? 'o' : 'x');
        $page->click('[data-testid="ultimate-cell-'.$board.'-'.$cell.'"]')
            ->assertAttribute('[data-testid="ultimate-cell-'.$board.'-'.$cell.'"]', 'data-mark', $mark);
        if ($turn === 4) {
            $page->assertAttribute('[data-testid="ultimate-sub-board-0"]', 'data-result', $starter)
                ->assertSeeIn('[data-testid="target-board-status"]', 'qualquer subtabuleiro disponível');
        }
    }

    $page->assertAttribute('[data-testid="ultimate-sub-board-1"]', 'data-result', $starter)
        ->assertAttribute('[data-testid="ultimate-sub-board-2"]', 'data-result', $starter)
        ->assertSeeIn('[data-testid="match-status"]', 'venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertNoJavaScriptErrors();
});

it('shows the draw dialog after all nine subboards close without a global winner', function () {
    $page = visit('/ultimate');
    $moves = [
        [4, 0], [0, 8], [8, 8], [8, 6], [6, 8], [8, 0], [0, 3], [3, 3], [3, 1],
        [1, 5], [5, 4], [4, 4], [4, 7], [7, 4], [4, 2], [2, 8], [8, 7], [7, 1],
        [1, 8], [8, 4], [4, 8], [8, 5], [5, 3], [3, 6], [6, 5], [5, 0], [0, 7],
        [7, 5], [5, 6], [6, 7], [7, 7], [7, 3], [3, 2], [2, 6], [6, 1], [1, 0],
        [0, 0], [0, 4], [4, 1], [1, 1], [1, 7], [5, 1], [1, 3], [3, 4], [3, 8],
        [8, 1], [1, 2], [2, 2], [2, 7], [3, 0], [0, 6], [6, 4], [8, 3], [2, 1],
        [1, 4], [6, 3], [6, 0], [5, 7], [5, 8], [8, 2], [2, 4], [2, 0], [6, 6],
        [6, 2], [1, 6], [5, 5], [5, 2],
    ];
    foreach ($moves as [$board, $cell]) {
        $page->click('[data-testid="ultimate-cell-'.$board.'-'.$cell.'"]')
            ->assertScript('document.querySelector("[data-testid=ultimate-cell-'.$board.'-'.$cell.']").dataset.mark !== ""', true);
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Rodada empatada!')
        ->assertSeeIn('[data-testid="score-draws"]', 'Empates: 1')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertNoJavaScriptErrors();
});

function ultimateLongPress($page, int $board, int $cell = 0): void
{
    $page->script('(async () => {
        const cell = document.querySelector("[data-testid=ultimate-cell-'.$board.'-'.$cell.']");
        const bounds = cell.getBoundingClientRect();
        const options = { bubbles: true, pointerId: 31, pointerType: "touch", clientX: bounds.left + bounds.width / 2, clientY: bounds.top + bounds.height / 2 };
        cell.dispatchEvent(new PointerEvent("pointerdown", options));
        await new Promise(resolve => setTimeout(resolve, 500));
        window.dispatchEvent(new PointerEvent("pointerup", options));
        cell.click();
    })()');
}

it('allows manual visual selection without making a move and routes the next move automatically', function () {
    $page = visit('/ultimate');
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    $page->script('document.querySelector("[data-testid=ultimate-sub-board-4]").click()');
    $page->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'data-selected', 'true')
        ->assertScript('Array.from(document.querySelectorAll("[data-testid^=ultimate-cell-]")).every(cell => cell.dataset.mark === "")', true)
        ->assertScript('document.querySelector("[data-testid=match-status]").textContent.trim()', $turn)
        ->click('[data-testid="ultimate-cell-0-4"]')
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'data-target', 'true')
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'data-selected', 'true')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-4]").dataset.mark !== ""', true)
        ->assertNoJavaScriptErrors();
});

it('keeps blocked boards and occupied cells unavailable in the main grid', function () {
    $page = visit('/ultimate')->click('[data-testid="ultimate-cell-0-4"]')
        ->assertAttribute('[data-testid="ultimate-sub-board-0"]', 'data-available', 'false')
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'data-available', 'true')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-0]").disabled', true)
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-4]").disabled', true)
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").disabled', false);
    $mark = $page->script('document.querySelector("[data-testid=ultimate-cell-0-4]").dataset.mark');
    $page->script('document.querySelector("[data-testid=ultimate-cell-0-4]").click()');
    $page->assertAttribute('[data-testid="ultimate-cell-0-4"]', 'data-mark', $mark)
        ->assertNoJavaScriptErrors();
});

it('keeps nine square subboards in one 3 by 3 grid without horizontal overflow at :dataset', function (int $width, int $height) {
    $page = visit('/ultimate')->resize($width, $height);

    foreach (['light', 'dark'] as $theme) {
        $page->select('[data-theme-selector]', $theme)
            ->assertCount('[data-testid^="ultimate-sub-board-"]', 9)
            ->assertCount('[data-testid^="ultimate-cell-"]', 81)
            ->assertMissing('[data-testid="ultimate-focus-region"]')
            ->assertScript('(() => {
                const grid = document.querySelector("[data-testid=ultimate-overview]");
                const boards = [...grid.children];
                const first = boards[0].getBoundingClientRect();
                return getComputedStyle(grid).gridTemplateColumns.split(" ").length === 3
                    && boards.every(board => {
                        const bounds = board.getBoundingClientRect();
                        const cells = [...board.querySelectorAll("button")];
                        return Math.abs(bounds.width - first.width) < 1
                            && Math.abs(bounds.height - first.height) < 1
                            && cells.length === 9
                            && cells.every(cell => Math.abs(cell.clientWidth - cell.clientHeight) <= 1 && cell.getBoundingClientRect().right <= innerWidth);
                    }) && document.documentElement.scrollWidth <= innerWidth;
            })()', true);
    }

    $page->click('[data-testid="ultimate-cell-0-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-0]").dataset.mark !== ""', true)
        ->assertNoJavaScriptErrors();
})->with([
    '320px' => [320, 720],
    '360px' => [360, 800],
    '375px' => [375, 812],
    '390px' => [390, 844],
    '414px' => [414, 896],
    '768px' => [768, 1024],
    '1280px' => [1280, 900],
]);

it('does not scroll when a board is selected or a direct move is made', function () {
    $page = visit('/ultimate')->resize(320, 720);
    $page->script('window.scrollTo(0, 100)');
    $before = $page->script('window.scrollY');

    $page->script('document.querySelector("[data-testid=ultimate-sub-board-4]").click()');
    $page->assertScript('window.scrollY', $before)
        ->click('[data-testid="ultimate-cell-4-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").dataset.mark !== ""', true)
        ->assertScript('window.scrollY', $before)
        ->assertNoJavaScriptErrors();
});

it('opens a persistent touch enlargement and closes it only after a confirmed move', function () {
    $page = visit('/ultimate')->resize(320, 720);
    $before = $page->script('window.scrollY');

    ultimateLongPress($page, 4);
    $page->assertVisible('[data-testid="ultimate-zoom-panel"]')
        ->assertAttribute('[data-testid="ultimate-cell-4-0"]', 'data-mark', '')
        ->assertScript('window.scrollY', $before)
        ->assertScript('(() => { const panel = document.querySelector("[data-testid=ultimate-zoom-panel]").getBoundingClientRect(); return panel.left >= 0 && panel.top >= 0 && panel.right <= innerWidth && panel.bottom <= innerHeight; })()', true)
        ->resize(360, 640)
        ->assertScript('(() => { const panel = document.querySelector("[data-testid=ultimate-zoom-panel]").getBoundingClientRect(); return panel.left >= 0 && panel.top >= 0 && panel.right <= innerWidth && panel.bottom <= innerHeight; })()', true)
        ->click('[data-testid="ultimate-zoom-cell-4-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").dataset.mark !== ""', true)
        ->assertScript('window.scrollY', $before)
        ->assertNoJavaScriptErrors();
    expect($page->script('async () => { const panel = document.querySelector("[data-testid=ultimate-zoom-layer]"); const deadline = performance.now() + 2000; while (performance.now() < deadline) { if (getComputedStyle(panel).display === "none") return true; await new Promise(requestAnimationFrame); } return false; }'))->toBeTrue();
});

it('cancels enlargement by outside touch, close button, and Escape without changing the turn', function () {
    $page = visit('/ultimate')->resize(390, 844);
    $turn = $page->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    ultimateLongPress($page, 4);
    $page->script('new Promise(resolve => setTimeout(resolve, 180))');
    $page->script('document.querySelector("[data-testid=ultimate-zoom-backdrop]").click()');
    $page->assertScript('getComputedStyle(document.querySelector("[data-testid=ultimate-zoom-layer]")).display', 'none');
    ultimateLongPress($page, 0);
    $page->click('[data-testid="ultimate-zoom-close"]')->assertScript('getComputedStyle(document.querySelector("[data-testid=ultimate-zoom-layer]")).display', 'none');
    ultimateLongPress($page, 1);
    $page->keys('[data-testid="ultimate-zoom-close"]', 'Escape')
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=ultimate-zoom-layer]")).display', 'none')
        ->assertScript('document.querySelector("[data-testid=match-status]").textContent.trim()', $turn)
        ->assertScript('Array.from(document.querySelectorAll("[data-testid^=ultimate-cell-]")).every(cell => cell.dataset.mark === "")', true)
        ->assertNoJavaScriptErrors();
});

it('does not interpret a scrolling gesture or short touch as enlargement', function () {
    $page = visit('/ultimate')->resize(360, 800);
    $result = $page->script('(async () => {
        const cell = document.querySelector("[data-testid=ultimate-cell-0-0]");
        const options = { bubbles: true, pointerId: 41, pointerType: "touch", clientX: 60, clientY: 180 };
        cell.dispatchEvent(new PointerEvent("pointerdown", options));
        window.dispatchEvent(new PointerEvent("pointermove", { ...options, clientY: 215 }));
        await new Promise(resolve => setTimeout(resolve, 500));
        window.dispatchEvent(new PointerEvent("pointerup", options));
        cell.dispatchEvent(new PointerEvent("pointerdown", { ...options, pointerId: 42 }));
        window.dispatchEvent(new PointerEvent("pointerup", { ...options, pointerId: 42 }));
        return getComputedStyle(document.querySelector("[data-testid=ultimate-zoom-layer]")).display;
    })()');

    expect($result)->toBe('none');
    $page->assertNoJavaScriptErrors();
});

it('does not play a cell when the player holds the long press after enlargement opens', function () {
    $page = visit('/ultimate')->resize(320, 720);
    $page->script('(async () => {
        const cell = document.querySelector("[data-testid=ultimate-cell-4-0]");
        const options = { bubbles: true, pointerType: "touch", pointerId: 61, clientX: 60, clientY: 180 };
        cell.dispatchEvent(new PointerEvent("pointerdown", options));
        await new Promise(resolve => setTimeout(resolve, 1250));
        window.dispatchEvent(new PointerEvent("pointerup", options));
        cell.click();
    })()');

    $page->assertVisible('[data-testid="ultimate-zoom-panel"]')
        ->assertAttribute('[data-testid="ultimate-cell-4-0"]', 'data-mark', '')
        ->assertNoJavaScriptErrors();
});

it('allows long press over an occupied cell of an available subboard', function () {
    $page = visit('/ultimate')->resize(320, 720)
        ->click('[data-testid="ultimate-cell-0-4"]')
        ->click('[data-testid="ultimate-cell-4-4"]')
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'data-available', 'true');

    $page->script('(async () => {
        const cell = document.querySelector("[data-testid=ultimate-cell-4-4]");
        const bounds = cell.getBoundingClientRect();
        const x = bounds.left + bounds.width / 2;
        const y = bounds.top + bounds.height / 2;
        const target = document.elementFromPoint(x, y);
        target.dispatchEvent(new PointerEvent("pointerdown", { bubbles: true, pointerType: "touch", pointerId: 51, clientX: x, clientY: y }));
        await new Promise(resolve => setTimeout(resolve, 500));
        window.dispatchEvent(new PointerEvent("pointerup", { bubbles: true, pointerType: "touch", pointerId: 51, clientX: x, clientY: y }));
    })()');

    $page->assertVisible('[data-testid="ultimate-zoom-panel"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-zoom-cell-4-4]").disabled', true)
        ->assertNoJavaScriptErrors();
});

it('blocks duplicate moves while a Livewire request is pending', function () {
    $page = visit('/ultimate')->resize(320, 720);
    $pending = $page->script('(async () => {
        const originalFetch = window.fetch;
        window.__ultimateRequests = 0;
        window.fetch = (...args) => {
            window.__ultimateRequests++;
            return originalFetch(...args).then(response => new Promise(resolve => { window.__releaseUltimate = () => resolve(response); }));
        };
        const cell = document.querySelector("[data-testid=ultimate-cell-0-4]");
        cell.click();
        cell.click();
        await new Promise(resolve => setTimeout(resolve, 150));
        return { requests: window.__ultimateRequests, disabled: cell.disabled, mark: cell.dataset.mark };
    })()');

    expect($pending)->toBe(['requests' => 1, 'disabled' => true, 'mark' => '']);
    $page->script('window.__releaseUltimate()');
    $page->assertScript('document.querySelector("[data-testid=ultimate-cell-0-4]").dataset.mark !== ""', true)
        ->assertScript('window.__ultimateRequests', 1)
        ->assertNoJavaScriptErrors();
});

it('keeps the enlargement open and allows retry after a failed request', function () {
    $page = visit('/ultimate')->resize(320, 720);
    ultimateLongPress($page, 4);
    $page->script('window.__ultimateOriginalFetch = window.fetch; window.fetch = () => Promise.reject(new TypeError("Network unavailable")); document.querySelector("[data-testid=ultimate-zoom-cell-4-0]").click()');

    expect($page->script('async () => {
        const deadline = performance.now() + 5000;
        while (performance.now() < deadline) {
            if (document.querySelector("[data-testid=ultimate-request-error]").textContent.trim()) return true;
            await new Promise(requestAnimationFrame);
        }
        return false;
    }'))->toBeTrue();

    $page->assertSeeIn('[data-testid="ultimate-request-error"]', 'Não foi possível concluir a jogada')
        ->assertVisible('[data-testid="ultimate-zoom-panel"]')
        ->assertAttribute('[data-testid="ultimate-cell-4-0"]', 'data-mark', '')
        ->assertScript('Alpine.$data(document.querySelector(".experimental-match")).busy', false)
        ->assertNoJavaScriptErrors();

    $page->script('window.fetch = window.__ultimateOriginalFetch');
    $page->click('[data-testid="ultimate-zoom-cell-4-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").dataset.mark !== ""', true)
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=ultimate-zoom-layer]")).display', 'none')
        ->assertNoJavaScriptErrors();
});

it('opens the enlargement with reduced motion enabled', function () {
    $page = visit('/ultimate', ['reducedMotion' => 'reduce'])->resize(320, 720)
        ->assertScript('matchMedia("(prefers-reduced-motion: reduce)").matches', true);

    ultimateLongPress($page, 4);

    $page->assertVisible('[data-testid="ultimate-zoom-panel"]')
        ->assertScript('window.scrollY', 0)
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").dataset.mark', '')
        ->assertNoJavaScriptErrors();
});

it('supports keyboard moves directly and preserves focus styling', function () {
    $page = visit('/ultimate')->resize(390, 844)
        ->keys('[data-testid="ultimate-cell-0-4"]', 'Enter')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-4]").dataset.mark !== ""', true)
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'data-target', 'true')
        ->assertNoJavaScriptErrors();
});
