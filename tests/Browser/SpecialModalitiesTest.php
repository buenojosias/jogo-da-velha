<?php

it('moves a placed piece after the movement placement phase', function () {
    $page = visit('/3-mover');

    foreach ([0, 1, 3, 4, 8, 6] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page
        ->assertSeeIn('[data-testid="phase-instruction"]', 'Selecione uma peça e depois uma casa vazia')
        ->click('[data-testid="cell-8"]')
        ->assertScript('document.querySelector("[data-testid=cell-8]").dataset.selected', 'true')
        ->click('[data-testid="cell-2"]')
        ->assertScript('document.querySelector("[data-testid=cell-8]").dataset.mark', '')
        ->assertScript('document.querySelector("[data-testid=cell-2]").dataset.mark !== ""', true)
        ->assertScript('document.querySelectorAll("[data-selected=true]").length', 0)
        ->assertNoJavaScriptErrors();
});

it('removes the oldest piece in rotation mode', function () {
    $page = visit('/3-rotacao');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 1, 2, 3, 4, 6] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }

    $page->assertScript('document.querySelector("[data-testid=cell-0]").dataset.oldestCell', 'true')
        ->click('[data-testid="cell-5"]');
    awaitBrowserCellMark($page, 5, $starter);
    $page->assertScript('document.querySelector("[data-testid=cell-0]").dataset.mark', '')
        ->assertScript('document.querySelector("[data-testid=cell-2]").dataset.oldestCell', 'true')
        ->assertNoJavaScriptErrors();
});

it('distinguishes a blocked horizontal line from a valid line', function () {
    $page = visit('/4-3-bloqueio');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 3, 1, 4, 2] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($other))
        ->assertScript('document.querySelector("[data-testid=result-dialog]")?.open ?? false', false);

    foreach ([6, 8, 12, 9, 15, 10] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $other : $starter);
    }
    $page->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertNoJavaScriptErrors();
});

it('hides pieces during play and reveals them after a hidden-mode win', function () {
    $page = visit('/4-3-oculto');

    foreach ([0, 4, 1, 5] as $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
    }

    $page->assertScript('Array.from(document.querySelectorAll("[data-testid^=cell-]")).every((cell) => !cell.hasAttribute("data-mark") && cell.textContent.trim() === "?" && !cell.disabled)', true);

    $page
        ->click('[data-testid="cell-2"]')
        ->assertSeeIn('[data-testid="match-status"]', 'venceu!')
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 3)
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertNoJavaScriptErrors();
});

it('recognizes four pieces in sequence', function () {
    $page = visit('/4-mover');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 4, 1, 5, 2, 6, 3] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertScript('document.querySelectorAll("[data-winning-cell=true]").length', 4)
        ->assertNoJavaScriptErrors();
});

it('requires two distinct lines in the 5x5 two-lines mode', function () {
    $page = visit('/2-linhas');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';

    foreach ([0, 5, 1, 6, 2] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
    }

    $page->assertScript('document.querySelector("[data-testid=result-dialog]")?.open ?? false', false);

    foreach ([9, 10, 14, 11, 19, 12] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $other : $starter);
    }

    $page
        ->assertSeeIn('[data-testid="match-status"]', 'venceu!')
        ->assertScript('document.querySelector("[data-testid=result-dialog]").open', true)
        ->assertNoJavaScriptErrors();
});

it('scores maximal sequences of three, four, and five pieces', function () {
    $page = visit('/5-cheia');
    $starter = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $other = $starter === 'x' ? 'o' : 'x';
    foreach ([0, 5, 1, 6, 2, 7, 3, 8, 4] as $turn => $cell) {
        $page->click('[data-testid="cell-'.$cell.'"]');
        awaitBrowserCellMark($page, $cell, $turn % 2 === 0 ? $starter : $other);
        if (in_array($turn, [4, 6, 8], true)) {
            $page->assertSeeIn('[data-testid="round-points-'.$starter.'"]', (string) ([4 => 3, 6 => 6, 8 => 10][$turn]));
        }
    }
    $page->assertNoJavaScriptErrors();
});

it('rolls the die, rejects another column, and accepts the allowed move', function () {
    $page = visit('/dado')
        ->assertCount('[data-testid^="cell-"]', 18);
    $initialPlayer = strtolower(substr($page->script('document.querySelector("[data-testid=match-status]").textContent.trim()'), -1));
    $page->click('[data-testid="roll-button"]');
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
                const timeout = setTimeout(() => { observer.disconnect(); reject(new Error('Dice roll did not finish')); }, 10000);
            });
        })()
        JS);
    $face = $page->script('document.querySelector("[data-testid=dice-face]").dataset.face');
    expect((int) $face)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(6);
    $board = (int) $face <= 3 ? 'esquerdo' : 'direito';
    $wrongBoard = $board === 'esquerdo' ? 'direito' : 'esquerdo';
    $column = ((int) $face - 1) % 3;
    $page->click('[data-testid="dice-board-'.$wrongBoard.'"] [data-testid="cell-'.$column.'"]')
        ->assertSeeIn('[data-testid="rejection-message"]', 'coluna indicada')
        ->click('[data-testid="dice-board-'.$board.'"] [data-testid="cell-'.$column.'"]');
    $page->assertScript('document.querySelector("[data-testid=dice-board-'.$board.'] [data-testid=cell-'.$column.']").dataset.mark', $initialPlayer)
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.($initialPlayer === 'x' ? 'O' : 'X'))
        ->assertNoJavaScriptErrors();
});

it('selects, places, and covers a smaller piece in crazy mode', function () {
    $page = visit('/maluca')
        ->assertCount('#board .cell', 9)
        ->assertCount('#piecesX .select-piece', 9)
        ->assertCount('#piecesO .select-piece', 9);

    $starter = $page->script('currentPlayer');
    $opponent = $starter === 'X' ? 'O' : 'X';

    $page
        ->click(sprintf('#pieces%s .select-piece:nth-child(1)', $starter))
        ->click('#board .cell:nth-child(1)')
        ->click(sprintf('#pieces%s .select-piece:nth-child(1)', $opponent))
        ->click('#board .cell:nth-child(2)')
        ->click(sprintf('#pieces%s .select-piece:nth-child(4)', $starter))
        ->click('#board .cell:nth-child(2)')
        ->assertScript('board[1].length', 2)
        ->assertScript('visibleOwner(1)', $starter)
        ->assertCount('#board .cell:nth-child(2) .piece', 2)
        ->assertNoJavaScriptErrors();
});

it('directs the next ultimate move to the matching sub-board', function () {
    $page = visit('/ultimate')
        ->assertCount('#game .sub-board', 9)
        ->assertCount('#game .cell', 81)
        ->assertCount('#game .sub-board.active', 9);

    $page
        ->click('#game .sub-board:nth-child(1) .cell:nth-child(5)')
        ->assertScript('targetBoard', 4)
        ->assertCount('#game .sub-board.active', 1)
        ->assertAttributeContains('#game .sub-board:nth-child(5)', 'class', 'active')
        ->assertSeeIn('#targetBoardText', 'subtabuleiro 5')
        ->assertNoJavaScriptErrors();
});
