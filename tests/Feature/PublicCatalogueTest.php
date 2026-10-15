<?php

it('explains the public offer and presents fourteen playable cards', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Plataforma Jogo da Velha')
        ->assertSeeText('Grátis e sem cadastro')
        ->assertSeeText('Duas pessoas no mesmo dispositivo')
        ->assertSeeText('Para começar')
        ->assertSeeText('Peças em movimento')
        ->assertSeeText('Estratégia e desafios')
        ->assertSeeText('Jeitos especiais de jogar');

    expect(substr_count($response->getContent(), 'aria-label="Jogar '))->toBe(14);
});

it('provides a catalogue return and game instructions on every public modality', function (string $uri, string $instruction) {
    $response = $this->get($uri);

    $response
        ->assertOk()
        ->assertSee(route('welcome'), escape: false)
        ->assertSeeText('Catálogo')
        ->assertSeeText('Como jogar')
        ->assertSeeText($instruction);
})->with([
    'classic 3x3' => ['/3-basico', 'Faça três marcas em linha'],
    'movement 3x3' => ['/3-mover', 'selecione uma das suas peças'],
    'rotation 3x3' => ['/3-rotacao', 'sua peça mais antiga sai'],
    'classic 4x4' => ['/4-3-basico', 'A primeira jogada de cada jogador é livre'],
    'movement 4x4' => ['/4-3-mover', 'Faça três em linha'],
    'rotation 4x4' => ['/4-3-rotacao', 'sua peça mais antiga sai'],
    'blocking' => ['/4-3-bloqueio', 'casa imediatamente antes ou depois'],
    'hidden' => ['/4-3-oculto', 'Casa adversária não muda'],
    'movement with four' => ['/4-mover', 'Faça quatro em linha'],
    'two lines' => ['/2-linhas', 'Sequências sobrepostas na mesma direção'],
    'score' => ['/5-cheia', '3, 6 e 10 pontos'],
    'dice' => ['/dado', 'Se a coluna estiver cheia'],
    'crazy' => ['/maluca', 'Peças maiores podem cobrir'],
    'ultimate' => ['/ultimate', 'subtabuleiro obrigatório do rival'],
]);
