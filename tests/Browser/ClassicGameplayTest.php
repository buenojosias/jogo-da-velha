<?php

it('plays, wins, scores, and restarts a classic 3x3 match', function () {
    $page = visit('/3-basico')
        ->assertCount('#board .cell', 9)
        ->assertScript('board.every((cell) => cell === "")')
        ->assertScript('currentPlayer === startingPlayer')
        ->assertScript('currentPlayer === "x" ? playerXIcon.style.display === "inline" : playerOIcon.style.display === "inline"');

    $initialPlayer = $page->script('currentPlayer');
    $otherPlayer = $initialPlayer === 'x' ? 'o' : 'x';

    $page
        ->click(browserCellSelector(0))
        ->assertScript('board[0]', $initialPlayer)
        ->assertScript('currentPlayer', $otherPlayer)
        ->click(browserCellSelector(0))
        ->assertScript('board[0]', $initialPlayer)
        ->assertScript('currentPlayer', $otherPlayer);

    clickBrowserCells($page, [3, 1, 4, 2]);

    $page
        ->assertScript('gameOver')
        ->assertScript('modalWinElement.style.display', 'flex')
        ->assertVisible('#modal-win')
        ->assertScript(sprintf('score.%s', $initialPlayer), 1)
        ->assertScript(sprintf('score.%s', $otherPlayer), 0)
        ->assertScript('document.querySelectorAll(".winner-" + startingPlayer).length', 3)
        ->click('#modal-restart-btn')
        ->assertScript('board.every((cell) => cell === "")')
        ->assertScript('gameOver', false)
        ->assertScript('startingPlayer', $otherPlayer)
        ->assertScript('currentPlayer', $otherPlayer)
        ->assertScript('modalWinElement.style.display', 'none')
        ->assertNoJavaScriptErrors();
});

it('alternates the classic starting player on the regular restart button', function () {
    $page = visit('/3-basico');
    $initialPlayer = $page->script('startingPlayer');
    $expectedPlayer = $initialPlayer === 'x' ? 'o' : 'x';

    $page
        ->click('#restartBtn')
        ->assertScript('startingPlayer', $expectedPlayer)
        ->assertScript('currentPlayer', $expectedPlayer)
        ->assertScript('board.every((cell) => cell === "")')
        ->assertNoJavaScriptErrors();
});
