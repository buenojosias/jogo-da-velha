<?php

it('keeps the classic board usable without horizontal page overflow at :dataset', function (int $width, int $height) {
    $page = visit('/3-basico')
        ->resize($width, $height)
        ->assertVisible('#board')
        ->assertVisible('#restartBtn')
        ->assertVisible(browserCellSelector(0))
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertScript('function() { const cell = document.querySelector("#board .cell"); const box = cell.getBoundingClientRect(); const target = document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2); return cell === target || cell.contains(target); }');

    $page
        ->click(browserCellSelector(0))
        ->assertScript('board[0] !== ""')
        ->assertNoJavaScriptErrors();
})->with([
    'small phone (360px)' => [360, 800],
    'common phone (390px)' => [390, 844],
    'tablet (768px)' => [768, 1024],
    'desktop (1280px)' => [1280, 900],
]);

it('renders special modality controls at :dataset', function (
    string $uri,
    string $board,
    string $control,
    int $width,
    int $height,
) {
    visit($uri)
        ->resize($width, $height)
        ->assertVisible($board)
        ->assertVisible($control)
        ->assertNoJavaScriptErrors();
})->with(specialResponsiveCases());

/**
 * @return array<string, array{string, string, string, int, int}>
 */
function specialResponsiveCases(): array
{
    $modalities = [
        'dice' => ['/dado', '#board1', '#rollBtn'],
        'crazy' => ['/maluca', '#board', '#piecesX .select-piece:nth-child(1)'],
        'ultimate' => ['/ultimate', '#game', '#game .sub-board:nth-child(1) .cell:nth-child(1)'],
    ];
    $viewports = [
        '360px' => [360, 800],
        '390px' => [390, 844],
        '768px' => [768, 1024],
        '1280px' => [1280, 900],
    ];
    $cases = [];

    foreach ($modalities as $modality => [$uri, $board, $control]) {
        foreach ($viewports as $viewport => [$width, $height]) {
            $cases[sprintf('%s at %s', $modality, $viewport)] = [$uri, $board, $control, $width, $height];
        }
    }

    return $cases;
}
