<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser', '../resources/views');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function browserCellSelector(int $index): string
{
    return sprintf('#board > .p-1:nth-child(%d) > .cell', $index + 1);
}

/**
 * @param  array<int, int>  $indices
 */
function clickBrowserCells(AwaitableWebpage|PendingAwaitablePage $page, array $indices): void
{
    foreach ($indices as $index) {
        $page->click(browserCellSelector($index));
    }
}

function awaitBrowserCellMark(AwaitableWebpage|PendingAwaitablePage $page, int $cell, string $mark): void
{
    $selector = json_encode('[data-testid=cell-'.$cell.']', JSON_THROW_ON_ERROR);
    $expected = json_encode($mark, JSON_THROW_ON_ERROR);
    $expression = <<<JS
        (async () => {
            const selector = {$selector};
            const expected = {$expected};
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
                const timeout = setTimeout(() => {
                    observer.disconnect();
                    reject(new Error('Livewire did not confirm the selected cell'));
                }, 10000);
            });
        })()
        JS;

    expect($page->script($expression))->toBeTrue();
}
