<?php

it('keeps the classic board usable without horizontal page overflow at :dataset', function (int $width, int $height) {
    $page = visit('/3-basico')
        ->resize($width, $height)
        ->assertVisible('[data-testid="classic-board"]')
        ->assertVisible('[data-testid="match-actions"]')
        ->assertVisible('[data-testid="new-match-button"]')
        ->assertVisible('[data-testid="cell-0"]')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertScript('(() => { const cell = document.querySelector("[data-testid=cell-0]"); const box = cell.getBoundingClientRect(); return document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2) === cell; })()', true);

    $page
        ->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="move-count"]', 'Jogadas realizadas: 1')
        ->click('[data-testid="new-match-button"]')
        ->assertScript('document.querySelector("[data-testid=new-match-dialog]").open', true)
        ->click('[data-testid="new-match-dialog"] button:first-of-type')
        ->assertNoJavaScriptErrors();
})->with([
    'small phone (360px)' => [360, 800],
    'common phone (390px)' => [390, 844],
    'short landscape (740px)' => [740, 360],
    'short landscape (800px)' => [800, 360],
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
        'dice' => ['/dado', '[data-testid="dice-board-esquerdo"]', '[data-testid="roll-button"]'],
        'crazy' => ['/maluca', '[data-testid="crazy-board"]', '[data-testid="piece-selector"]'],
        'ultimate' => ['/ultimate', '[data-testid="ultimate-overview"]', '[data-testid="ultimate-focus-region"]'],
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
