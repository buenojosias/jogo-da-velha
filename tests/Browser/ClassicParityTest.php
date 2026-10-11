<?php

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

it('produces the same winner for equivalent classic moves at :dataset', function (string $starter, array $moves) {
    [$original, $experimental] = parityPagesStartingWith($starter);

    parityPlayMoves($original, $experimental, $starter, $moves);

    $scoreSelector = $starter === 'x' ? '#player-x-score' : '#player-o-score';

    $original
        ->assertVisible('#modal-win')
        ->assertSeeIn($scoreSelector, '1')
        ->assertNoJavaScriptErrors();

    $experimental
        ->assertSeeIn('[data-testid="match-status"]', 'Jogador '.strtoupper($starter).' venceu!')
        ->assertNoJavaScriptErrors();
})->with([
    'X on a horizontal line' => ['x', [0, 3, 1, 4, 2]],
    'O on a vertical line' => ['o', [0, 1, 3, 2, 6]],
    'X on a diagonal' => ['x', [0, 1, 4, 2, 8]],
    'X on the ninth move' => ['x', [0, 3, 1, 5, 4, 7, 6, 8, 2]],
]);

it('produces a draw for the same nine moves', function () {
    [$original, $experimental] = parityPagesStartingWith('x');

    parityPlayMoves($original, $experimental, 'x', [0, 1, 2, 4, 3, 5, 7, 6, 8]);

    $original
        ->assertSeeIn('#status-text', 'Empate!')
        ->assertScript('document.querySelectorAll("#board .cell svg").length', 9)
        ->assertNoJavaScriptErrors();

    $experimental
        ->assertSeeIn('[data-testid="match-status"]', 'Empate!')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 9')
        ->assertNoJavaScriptErrors();
});

it('does not accept another move after a victory', function () {
    [$original, $experimental] = parityPagesStartingWith('x');

    parityPlayMoves($original, $experimental, 'x', [0, 3, 1, 4, 2]);

    $original->script('document.querySelectorAll("#board .cell")[5].click()');
    $experimental
        ->assertDisabled(parityExperimentalCell(5))
        ->script('document.querySelector("[data-testid=cell-5]").click()');

    parityAssertMoveCount($original, $experimental, 5);

    $original->assertVisible('#modal-win')->assertSeeIn('#player-x-score', '1')->assertNoJavaScriptErrors();
    $experimental->assertSeeIn('[data-testid="match-status"]', 'Jogador X venceu!')->assertNoJavaScriptErrors();
});

it('keeps an occupied cell unchanged and alternates the starter on restart', function () {
    [$original, $experimental] = parityPagesStartingWith('x');

    $original->click(browserCellSelector(0));
    $experimental->click(parityExperimentalCell(0))
        ->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador O');

    $original->click(browserCellSelector(0));
    $experimental
        ->assertDisabled(parityExperimentalCell(0))
        ->script('document.querySelector("[data-testid=cell-0]").click()');

    parityAssertMoveCount($original, $experimental, 1);
    parityAssertTurn($original, $experimental, 'o');

    $original->click('#restartBtn');
    $experimental->click('Reiniciar partida');

    parityAssertMoveCount($original, $experimental, 0);
    parityAssertTurn($original, $experimental, 'o');

    $original->click('#restartBtn')->assertNoJavaScriptErrors();
    $experimental->click('Reiniciar partida')->assertNoJavaScriptErrors();

    parityAssertTurn($original, $experimental, 'x');
});

/**
 * @return array{AwaitableWebpage|PendingAwaitablePage, AwaitableWebpage|PendingAwaitablePage}
 */
function parityPagesStartingWith(string $starter): array
{
    $original = visit('/3-basico')->assertCount('#board .cell', 9);
    $experimental = visit('/experimental/3-basico')->assertCount('[data-testid^="cell-"]', 9);
    $observedStarter = parityOriginalTurn($original);
    $experimentalStarter = $experimental->script('document.querySelector("[data-testid=match-status]").textContent.trim()');

    expect(['x', 'o'])->toContain($observedStarter);
    expect(['Vez do jogador X', 'Vez do jogador O'])->toContain($experimentalStarter);

    if ($observedStarter !== $starter) {
        $original->click('#restartBtn');
    }

    if ($experimentalStarter !== 'Vez do jogador '.strtoupper($starter)) {
        $experimental->click('Reiniciar partida');
    }

    parityAssertTurn($original, $experimental, $starter);

    return [$original, $experimental];
}

/**
 * @param  list<int>  $moves
 */
function parityPlayMoves(
    AwaitableWebpage|PendingAwaitablePage $original,
    AwaitableWebpage|PendingAwaitablePage $experimental,
    string $starter,
    array $moves,
): void {
    foreach ($moves as $turn => $cell) {
        $original->click(browserCellSelector($cell));
        $experimental->click(parityExperimentalCell($cell));

        parityAssertMoveCount($original, $experimental, $turn + 1);

        if ($turn < count($moves) - 1) {
            $next = ($turn + 1) % 2 === 0 ? $starter : ($starter === 'x' ? 'o' : 'x');
            parityAssertTurn($original, $experimental, $next);
        }
    }
}

function parityAssertMoveCount(
    AwaitableWebpage|PendingAwaitablePage $original,
    AwaitableWebpage|PendingAwaitablePage $experimental,
    int $count,
): void {
    $original->assertScript('document.querySelectorAll("#board .cell svg").length', $count);
    $experimental->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: '.$count);
}

function parityAssertTurn(
    AwaitableWebpage|PendingAwaitablePage $original,
    AwaitableWebpage|PendingAwaitablePage $experimental,
    string $mark,
): void {
    expect(parityOriginalTurn($original))->toBe($mark);
    $experimental->assertSeeIn('[data-testid="match-status"]', 'Vez do jogador '.strtoupper($mark));
}

function parityOriginalTurn(AwaitableWebpage|PendingAwaitablePage $original): string
{
    return $original->script('(() => { const x = document.querySelector("#player-x-icon").style.display; const o = document.querySelector("#player-o-icon").style.display; return x === "inline" && o === "none" ? "x" : o === "inline" && x === "none" ? "o" : "invalid"; })()');
}

function parityExperimentalCell(int $index): string
{
    return sprintf('[data-testid="cell-%d"]', $index);
}
