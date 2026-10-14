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
        $page->click('[data-testid="ultimate-sub-board-'.$board.'"]')
            ->click('[data-testid="ultimate-cell-'.$board.'-'.$cell.'"]')
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
        $page->click('[data-testid="ultimate-sub-board-'.$board.'"]')
            ->click('[data-testid="ultimate-cell-'.$board.'-'.$cell.'"]')
            ->assertScript('document.querySelector("[data-testid=ultimate-cell-'.$board.'-'.$cell.']").dataset.mark !== ""', true);
    }

    $page->assertSeeIn('[data-testid="match-status"]', 'Rodada empatada!')
        ->assertSeeIn('[data-testid="score-draws"]', 'Empates: 1')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertNoJavaScriptErrors();
});

it('retains the directed move and theme after reload, then asks before creating a new match', function () {
    $page = visit('/ultimate')
        ->select('[data-theme-selector]', 'dark')
        ->click('[data-testid="ultimate-cell-0-4"]')
        ->assertSeeIn('[data-testid="target-board-status"]', 'Subtabuleiro obrigatório: 5');
    $page->navigate('/ultimate')
        ->assertSelected('[data-theme-selector]', 'dark')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-4]").dataset.mark !== ""', true)
        ->assertSeeIn('[data-testid="target-board-status"]', 'Subtabuleiro obrigatório: 5')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertSeeIn('[data-testid="target-board-status"]', 'Subtabuleiro obrigatório: 5')
        ->click('[data-testid="new-match-button"]')
        ->click('[data-testid="confirm-new-match"]')
        ->assertSeeIn('[data-testid="target-board-status"]', 'qualquer subtabuleiro disponível')
        ->select('[data-theme-selector]', 'light')
        ->assertScript('document.documentElement.dataset.theme', 'light')
        ->assertNoJavaScriptErrors();
});

it('keeps the overview and enlarged board usable without horizontal overflow at :dataset', function (int $width, int $height) {
    $page = visit('/ultimate')->resize($width, $height)
        ->assertVisible('[data-testid="ultimate-overview"]')
        ->assertVisible('[data-testid="ultimate-focus-region"]')
        ->assertCount('[data-testid^="ultimate-sub-board-"]', 9)
        ->assertScript('Array.from(document.querySelectorAll("[data-testid^=ultimate-cell-]")).every(cell => cell.hasAttribute("aria-label"))', true)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth', true)
        ->assertScript('(() => { const cell = document.querySelector("[data-testid=ultimate-cell-0-0]"); return cell.getBoundingClientRect().width >= 44 && cell.getBoundingClientRect().height >= 44; })()', true)
        ->click('[data-testid="ultimate-cell-0-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-0]").dataset.mark !== ""', true)
        ->assertNoJavaScriptErrors();
})->with([
    'small mobile' => [360, 800],
    'landscape 740' => [740, 360],
    'landscape 800' => [800, 360],
    'desktop' => [1280, 900],
]);

it('keeps every overview cell square and aligned after several marks at :dataset', function (int $width, int $height) {
    $uniformOverview = <<<'JS'
        (() => {
            const boards = [...document.querySelectorAll('[data-testid^="ultimate-sub-board-"]')];
            if (boards.length !== 9) return false;
            const firstBoard = boards[0].getBoundingClientRect();

            return boards.every(board => {
                const bounds = board.getBoundingClientRect();
                const grid = board.firstElementChild;
                const gridBounds = grid.getBoundingClientRect();
                const cells = [...grid.children].map(cell => cell.getBoundingClientRect());
                const size = cells[0]?.width;

                return Math.abs(bounds.width - firstBoard.width) < 1
                    && Math.abs(bounds.height - firstBoard.height) < 1
                    && Math.abs(gridBounds.width - gridBounds.height) < 1
                    && cells.length === 9
                    && cells.every(cell => Math.abs(cell.width - size) < 1 && Math.abs(cell.height - size) < 1);
            });
        })()
        JS;

    $page = visit('/ultimate')->resize($width, $height)
        ->assertScript($uniformOverview, true)
        ->click('[data-testid="ultimate-cell-0-4"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-4]").dataset.mark !== ""', true);

    $page->click('[data-testid="ultimate-cell-4-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").dataset.mark !== ""', true)
        ->click('[data-testid="ultimate-cell-0-1"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-0-1]").dataset.mark !== ""', true)
        ->assertScript($uniformOverview, true)
        ->assertNoJavaScriptErrors();
})->with(['mobile' => [390, 844], 'desktop' => [1280, 900]]);

it('moves focus and the viewport to a selected playable subboard at :dataset', function (int $width, int $height) {
    $page = visit('/ultimate')->resize($width, $height)
        ->assertScript('window.scrollY', 0)
        ->click('[data-testid="ultimate-sub-board-4"]')
        ->assertNoJavaScriptErrors();

    expect($page->script('async () => {
        const expected = "ultimate-cell-4-0";
        const deadline = performance.now() + 3000;
        while (performance.now() < deadline) {
            const cell = document.activeElement;
            const rect = cell?.getBoundingClientRect();
            if (cell?.dataset.testid === expected && rect.top >= 0 && rect.bottom <= innerHeight
                && document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2) === cell) return true;
            await new Promise(requestAnimationFrame);
        }
        return false;
    }'))->toBeTrue();

    $page->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'aria-pressed', 'true')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth', true)
        ->click('[data-testid="ultimate-cell-4-0"]')
        ->assertScript('document.querySelector("[data-testid=ultimate-cell-4-0]").dataset.mark !== ""', true);

    expect($page->script('async () => {
        const deadline = performance.now() + 3000;
        while (performance.now() < deadline) {
            if (document.activeElement?.dataset.testid === "ultimate-cell-0-0") return true;
            await new Promise(requestAnimationFrame);
        }
        return false;
    }'))->toBeTrue();

    $page->assertSeeIn('[data-testid="target-board-status"]', 'Subtabuleiro obrigatório: 1')
        ->assertNoJavaScriptErrors();
})->with([
    'portrait 360' => [360, 800],
    'landscape 740' => [740, 360],
    'landscape 800' => [800, 360],
    'desktop 1280' => [1280, 900],
]);

it('lets a keyboard user go directly from the overview to a playable cell', function () {
    $page = visit('/ultimate')->resize(740, 360)
        ->keys('[data-testid="ultimate-sub-board-4"]', 'Enter');

    expect($page->script('async () => {
        const deadline = performance.now() + 3000;
        while (performance.now() < deadline) {
            if (document.activeElement?.dataset.testid === "ultimate-cell-4-0") return true;
            await new Promise(requestAnimationFrame);
        }
        return false;
    }'))->toBeTrue();

    $page->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();

    $page->keys('[data-testid="ultimate-cell-4-0"]', 'Escape')
        ->assertScript('document.activeElement?.dataset.testid', 'ultimate-sub-board-4')
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'aria-pressed', 'true');
});

it('keeps waiting subboards disabled while the required subboard remains playable', function () {
    $page = visit('/ultimate')
        ->click('[data-testid="ultimate-cell-0-4"]')
        ->assertAttribute('[data-testid="ultimate-sub-board-0"]', 'data-available', 'false')
        ->assertAttribute('[data-testid="ultimate-sub-board-4"]', 'aria-pressed', 'true')
        ->assertScript('(() => { const board = document.querySelector("[data-testid=ultimate-sub-board-0]"); const focus = document.activeElement; board.click(); board.focus(); return board.disabled && board.getAttribute("aria-pressed") === "false" && document.querySelector("[data-testid=ultimate-sub-board-4]").getAttribute("aria-pressed") === "true" && document.activeElement === focus && !document.querySelector("[data-testid=rejection-message]"); })()', true)
        ->click('[data-testid="ultimate-sub-board-4"]')
        ->assertScript('document.activeElement?.dataset.testid', 'ultimate-cell-4-0')
        ->assertNoJavaScriptErrors();
});

it('keeps conquered subboards disabled and moves selection to the next playable one', function () {
    $page = visit('/ultimate');
    foreach ([[0, 1], [1, 0], [0, 2], [2, 0], [0, 0]] as [$board, $cell]) {
        $page->click('[data-testid="ultimate-sub-board-'.$board.'"]')
            ->click('[data-testid="ultimate-cell-'.$board.'-'.$cell.'"]')
            ->assertScript('document.querySelector("[data-testid=ultimate-cell-'.$board.'-'.$cell.']").dataset.mark !== ""', true);
    }

    $page->assertAttribute('[data-testid="ultimate-sub-board-0"]', 'data-available', 'false')
        ->assertAttribute('[data-testid="ultimate-sub-board-0"]', 'aria-pressed', 'false')
        ->assertAttribute('[data-testid="ultimate-sub-board-1"]', 'aria-pressed', 'true')
        ->assertScript('(() => { const board = document.querySelector("[data-testid=ultimate-sub-board-0]"); const focus = document.activeElement; board.click(); return board.disabled && document.querySelector("[data-testid=ultimate-sub-board-1]").getAttribute("aria-pressed") === "true" && document.activeElement === focus && !!board.querySelector("[data-winner-overlay]") && !document.querySelector("[data-testid=rejection-message]"); })()', true)
        ->assertScript('Array.from(document.querySelectorAll("[data-testid=ultimate-focus-board-0] button")).every(cell => cell.disabled)', true)
        ->assertNoJavaScriptErrors();
});
