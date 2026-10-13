<?php

use Illuminate\Support\Facades\Blade;

it('describes the participant according to the round result', function (bool $active, bool $finished, ?string $winnerMark, string $mark, string $caption) {
    $html = Blade::render(
        '<x-design-system.participant-card label="Jogador" :mark="$mark" :active="$active" :finished="$finished" :winner-mark="$winnerMark" />',
        compact('active', 'finished', 'winnerMark', 'mark'),
    );

    preg_match('/<p class="ds-participant-caption[^\"]*">\s*([^<]+)\s*<\/p>/s', $html, $matches);

    expect(trim($matches[1] ?? ''))->toBe($caption);
})->with([
    'active player' => [true, false, null, 'X', 'É a vez'],
    'waiting player' => [false, false, null, 'O', 'Aguardando'],
    'winning player' => [false, true, 'x', 'X', 'Venceu'],
    'other player after victory' => [false, true, 'x', 'O', 'Rodada encerrada'],
    'player X after a draw' => [false, true, null, 'X', 'Empate'],
    'player O after a draw' => [false, true, null, 'O', 'Empate'],
]);

it('includes the modality in the main heading while keeping the visible title', function () {
    $html = Blade::render('<x-design-system.match-header modality="Clássico 3×3" title="Jogo da Velha" description="Partida local" />');

    preg_match('/<h1\b[^>]*>(.*?)<\/h1>/s', $html, $matches);

    expect($matches[1] ?? '')
        ->toContain('<span class="sr-only">Clássico 3×3 — </span>')
        ->toContain('Jogo da Velha');
    expect(strip_tags($matches[1]))->toBe('Clássico 3×3 — Jogo da Velha');
});
