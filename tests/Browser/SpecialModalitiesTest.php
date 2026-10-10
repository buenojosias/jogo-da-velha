<?php

it('moves a placed piece after the movement placement phase', function () {
    $page = visit('/3-mover');

    clickBrowserCells($page, [0, 1, 3, 4, 8, 6]);

    $page
        ->assertScript('placedPieces.x === 3 && placedPieces.o === 3')
        ->click(browserCellSelector(8))
        ->assertScript('selectedPiece', 8)
        ->click(browserCellSelector(2))
        ->assertScript('board[8]', '')
        ->assertScript('board[2] !== ""')
        ->assertScript('selectedPiece', null)
        ->assertNoJavaScriptErrors();
});

it('removes the oldest piece in rotation mode', function () {
    $page = visit('/3-rotacao');

    clickBrowserCells($page, [0, 1, 3, 4, 8, 6]);

    $page
        ->assertScript('placedPiecesOrder[currentPlayer][0]', 0)
        ->click(browserCellSelector(2))
        ->assertScript('board[0]', '')
        ->assertScript('board[2] !== ""')
        ->assertNoJavaScriptErrors();
});

it('distinguishes a blocked horizontal line from a valid line', function () {
    $page = visit('/4-3-bloqueio');

    clickBrowserCells($page, [0, 3, 1, 4, 2]);

    $page
        ->assertScript('gameOver', false)
        ->assertScript('modalWinElement.style.display', 'none');

    clickBrowserCells($page, [6, 8, 12, 9, 15, 10]);

    $page
        ->assertScript('gameOver')
        ->assertVisible('#modal-win')
        ->assertNoJavaScriptErrors();
});

it('hides pieces during play and reveals them after a hidden-mode win', function () {
    $page = visit('/4-3-oculto');

    clickBrowserCells($page, [0, 3, 1, 4]);

    $page->assertScript('Array.from(document.querySelectorAll("#board .cell svg")).every((svg) => svg.classList.contains("piece-hidden"))');

    $page
        ->click(browserCellSelector(2))
        ->assertScript('gameOver')
        ->assertScript('document.querySelectorAll("#board .piece-hidden").length', 0)
        ->assertVisible('#modal-win')
        ->assertNoJavaScriptErrors();
});

it('recognizes four pieces in sequence', function () {
    $page = visit('/4-mover');

    clickBrowserCells($page, [0, 4, 1, 5, 2, 8, 3]);

    $page
        ->assertScript('gameOver')
        ->assertVisible('#modal-win')
        ->assertScript('document.querySelectorAll("#board .winner-" + startingPlayer).length', 4)
        ->assertNoJavaScriptErrors();
});

it('requires two distinct lines in the 5x5 two-lines mode', function () {
    $page = visit('/2-linhas');

    clickBrowserCells($page, [0, 5, 1, 6, 2]);

    $page->assertScript('gameOver', false);

    clickBrowserCells($page, [9, 10, 14, 11, 19, 12]);

    $page
        ->assertScript('gameOver')
        ->assertVisible('#modal-win')
        ->assertNoJavaScriptErrors();
});

it('scores maximal sequences of three, four, and five pieces', function () {
    $page = visit('/5-cheia');

    $page
        ->assertScript('function() { board = ["x", "x", "x", "", "", ...Array(20).fill("")]; return calculateScore("x"); }', 3)
        ->assertScript('function() { board = ["x", "x", "x", "x", "", ...Array(20).fill("")]; return calculateScore("x"); }', 6)
        ->assertScript('function() { board = ["x", "x", "x", "x", "x", ...Array(20).fill("")]; return calculateScore("x"); }', 10)
        ->assertNoJavaScriptErrors();
});

it('rolls the die, rejects another column, and accepts the allowed move', function () {
    $page = visit('/dado')
        ->assertCount('.boards .cell', 18);

    $initialPlayer = $page->script('currentPlayer');
    $page->script('window.__browserTestAlerts = []; window.alert = (message) => window.__browserTestAlerts.push(message);');

    $page
        ->click('#rollBtn')
        ->assertScript('currentColumn !== null');

    $column = $page->script('currentColumn');
    $allowedBoard = $column <= 3 ? 1 : 2;
    $allowedLocalColumn = $column <= 3 ? $column : $column - 3;
    $wrongBoard = $allowedBoard === 1 ? 2 : 1;

    $page
        ->click(sprintf('#board%d .cell:nth-child(1)', $wrongBoard))
        ->assertScript('boardState.flat().every((cell) => cell === "")')
        ->assertScript('window.__browserTestAlerts.some((message) => typeof message === "string" && message.includes("coluna"))')
        ->click(sprintf('#board%d .cell:nth-child(%d)', $allowedBoard, $allowedLocalColumn))
        ->assertScript(sprintf('boardState[%d][%d]', $allowedBoard - 1, $allowedLocalColumn - 1), $initialPlayer)
        ->assertScript('currentColumn', null)
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
