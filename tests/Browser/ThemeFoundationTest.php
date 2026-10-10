<?php

it('defaults to the system theme and exposes semantic colors', function () {
    visit('/experimental/3-basico')
        ->inDarkMode()
        ->assertScript('document.documentElement.dataset.themePreference', 'auto')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertScript('getComputedStyle(document.querySelector("main")).backgroundColor', 'rgb(23, 22, 41)')
        ->assertNoJavaScriptErrors();
});

it('persists manual light and dark preferences across navigation', function () {
    $page = visit('/experimental/3-basico')
        ->assertScript('typeof window.JogoDaVelhaTheme.setPreference', 'function');

    $page->script('window.JogoDaVelhaTheme.setPreference("dark")');

    $page
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertScript('localStorage.getItem("jogo-da-velha.theme")', 'dark')
        ->navigate('/experimental/3-basico')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertVisible('[data-testid="classic-board"]');

    $page->script('window.JogoDaVelhaTheme.setPreference("light")');

    $page
        ->assertScript('document.documentElement.dataset.theme', 'light')
        ->assertScript('getComputedStyle(document.querySelector("main")).backgroundColor', 'rgb(245, 246, 255)')
        ->navigate('/experimental/3-basico')
        ->assertScript('document.documentElement.dataset.themePreference', 'light')
        ->assertNoJavaScriptErrors();
});

it('returns to automatic mode and preserves the theme during a Livewire update', function () {
    $page = visit('/experimental/3-basico');

    $page->script('window.JogoDaVelhaTheme.setPreference("dark")');
    $page->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="cell-0"]', 'x')
        ->click('[data-testid="cell-1"]')
        ->assertSeeIn('[data-testid="cell-1"]', 'o')
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=cell-1]")).color', 'rgb(255, 159, 180)')
        ->assertScript('document.documentElement.dataset.theme', 'dark');

    $page->script('window.JogoDaVelhaTheme.setPreference("auto")');

    $page
        ->assertScript('document.documentElement.dataset.themePreference', 'auto')
        ->assertScript('localStorage.getItem("jogo-da-velha.theme")', null)
        ->assertScript('document.documentElement.dataset.theme === (matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light")', true)
        ->assertNoJavaScriptErrors();
});
