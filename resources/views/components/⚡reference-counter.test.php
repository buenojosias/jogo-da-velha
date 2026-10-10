<?php

use Livewire\Livewire;

it('renders the reference counter', function () {
    Livewire::test('reference-counter')
        ->assertStatus(200)
        ->assertSee('Incrementar');
});

it('starts with a count of zero', function () {
    Livewire::test('reference-counter')
        ->assertSet('count', 0)
        ->assertSee('0');
});

it('increments the count through its action', function () {
    Livewire::test('reference-counter')
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee('1');
});

it('reacts when its public count property changes', function () {
    Livewire::test('reference-counter')
        ->set('count', 7)
        ->assertSet('count', 7)
        ->assertSee('7');
});
