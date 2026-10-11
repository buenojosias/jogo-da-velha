<?php

it('defaults to the system theme and exposes semantic colors', function () {
    visit('/experimental/3-basico')
        ->inDarkMode()
        ->assertScript('document.documentElement.dataset.themePreference', 'auto')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertSelected('[data-theme-selector]', 'auto')
        ->assertScript('getComputedStyle(document.body).backgroundColor', 'rgb(23, 22, 41)')
        ->assertNoJavaScriptErrors();
});

it('persists theme choices made with the selector across navigation', function () {
    $page = visit('/experimental/3-basico')
        ->assertSelected('[data-theme-selector]', 'auto');

    $page->select('[data-theme-selector]', 'dark');

    $page
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertScript('localStorage.getItem("jogo-da-velha.theme")', 'dark')
        ->navigate('/experimental/3-basico')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertSelected('[data-theme-selector]', 'dark')
        ->assertVisible('[data-testid="classic-board"]');

    $page->select('[data-theme-selector]', 'light');

    $page
        ->assertScript('document.documentElement.dataset.theme', 'light')
        ->assertScript('getComputedStyle(document.body).backgroundColor', 'rgb(245, 246, 255)')
        ->navigate('/experimental/3-basico')
        ->assertScript('document.documentElement.dataset.themePreference', 'light')
        ->assertSelected('[data-theme-selector]', 'light')
        ->keys('[data-theme-selector]', 'Home')
        ->assertSelected('[data-theme-selector]', 'auto')
        ->assertScript('localStorage.getItem("jogo-da-velha.theme")', null)
        ->assertNoJavaScriptErrors();
});

it('keeps the selector synchronized with the theme API and during a Livewire update', function () {
    $page = visit('/experimental/3-basico');

    $page->select('[data-theme-selector]', 'dark');
    $page->click('[data-testid="cell-0"]')
        ->assertSeeIn('[data-testid="cell-0"]', 'x')
        ->click('[data-testid="cell-1"]')
        ->assertSeeIn('[data-testid="cell-1"]', 'o')
        ->assertScript('getComputedStyle(document.querySelector("[data-testid=cell-1]")).color', 'rgb(255, 159, 180)')
        ->assertSelected('[data-theme-selector]', 'dark')
        ->assertScript('document.documentElement.dataset.theme', 'dark');

    $page->script('window.JogoDaVelhaTheme.setPreference("auto")');

    $page
        ->assertScript('document.documentElement.dataset.themePreference', 'auto')
        ->assertSelected('[data-theme-selector]', 'auto')
        ->assertScript('localStorage.getItem("jogo-da-velha.theme")', null)
        ->assertScript('document.documentElement.dataset.theme === (matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light")', true)
        ->assertNoJavaScriptErrors();
});
