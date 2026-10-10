<?php

use App\Games\Modality;

test('a modality retains its key and configuration', function () {
    $modality = new Modality('classic-3x3', ['rows' => 3, 'columns' => 3, 'line_length' => 3]);

    expect($modality->key)->toBe('classic-3x3');
    expect($modality->configuration)->toBe(['rows' => 3, 'columns' => 3, 'line_length' => 3]);
});

test('a modality requires a nonblank key', function () {
    expect(fn () => new Modality(' '))->toThrow(InvalidArgumentException::class);
});
