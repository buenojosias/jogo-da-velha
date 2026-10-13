<?php

it('keeps an empty board cell boundary at least 3:1 in the :theme theme on :modality', function (string $modality, string $selector, string $theme) {
    $page = visit($modality)->select('[data-theme-selector]', $theme);

    $measurement = $page->script('async () => {
        const cell = document.querySelector('.json_encode($selector).');
        const luminance = value => {
            const channels = value.match(/[\d.]+/g).slice(0, 3).map(Number).map(channel => {
                const normalized = channel / 255;
                return normalized <= 0.04045 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
            });
            return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
        };
        const measure = () => {
            const style = getComputedStyle(cell);
            const border = style.borderTopColor;
            const background = style.backgroundColor;
            const lighter = Math.max(luminance(border), luminance(background));
            const darker = Math.min(luminance(border), luminance(background));
            return { border, background, ratio: (lighter + 0.05) / (darker + 0.05) };
        };
        const tokenColor = name => {
            const token = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return `rgb(${[1, 3, 5].map(start => parseInt(token.slice(start, start + 2), 16)).join(", ")})`;
        };
        const targetBorder = tokenColor("--ds-board-border");
        const targetBackground = tokenColor(cell.closest("[data-testid=ultimate-focus-region]") ? "--ds-elevated" : "--ds-surface");
        let result = measure();
        const deadline = performance.now() + 3000;
        while ((result.border !== targetBorder || result.background !== targetBackground) && performance.now() < deadline) {
            await new Promise(requestAnimationFrame);
            result = measure();
        }
        return result;
    }');

    expect($measurement['border'])->toBe($theme === 'light' ? 'rgb(133, 128, 152)' : 'rgb(150, 145, 170)');
    expect($measurement['background'])->toBe($modality === '/ultimate' && $theme === 'dark' ? 'rgb(57, 54, 82)' : ($theme === 'dark' ? 'rgb(36, 35, 58)' : 'rgb(255, 255, 255)'));
    expect($measurement['ratio'])->toBeGreaterThanOrEqual(3.0);
    $page->assertNoJavaScriptErrors();
})->with([
    '3x3 light' => ['/3-basico', '[data-testid="cell-0"]', 'light'],
    '3x3 dark' => ['/3-basico', '[data-testid="cell-0"]', 'dark'],
    '4x4 light' => ['/4-3-basico', '[data-testid="cell-0"]', 'light'],
    '4x4 dark' => ['/4-3-basico', '[data-testid="cell-0"]', 'dark'],
    '5x5 light' => ['/2-linhas', '[data-testid="cell-0"]', 'light'],
    '5x5 dark' => ['/2-linhas', '[data-testid="cell-0"]', 'dark'],
    'ultimate light' => ['/ultimate', '[data-testid="ultimate-cell-0-0"]', 'light'],
    'ultimate dark' => ['/ultimate', '[data-testid="ultimate-cell-0-0"]', 'dark'],
]);
