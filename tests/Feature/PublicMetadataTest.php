<?php

it('renders distinct identity and sharing metadata for :dataset', function (string $path, string $title, string $descriptionPhrase) {
    config()->set('app.url', 'https://preview.example.test');

    $response = $this->get($path);

    $response
        ->assertOk()
        ->assertSee('<html lang="pt-BR"', escape: false)
        ->assertSee('<title>'.$title.'</title>', escape: false)
        ->assertSee('<link rel="canonical" href="https://preview.example.test'.$path.'">', escape: false)
        ->assertSee('<meta property="og:title" content="'.$title.'">', escape: false)
        ->assertSee('<meta property="og:url" content="https://preview.example.test'.$path.'">', escape: false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false)
        ->assertSee('https://preview.example.test/share-card.png', escape: false)
        ->assertSee('https://preview.example.test/icon.svg', escape: false)
        ->assertSee('https://preview.example.test/sitemap.xml', escape: false);

    expect($response->getContent())->toContain($descriptionPhrase);
    expect(substr_count($response->getContent(), '<meta name="description"'))->toBe(1);
    expect(substr_count($response->getContent(), '<link rel="canonical"'))->toBe(1);
})->with([
    'catalogue' => ['/', 'Plataforma Jogo da Velha', '14 modalidades'],
    'classic 3x3' => ['/3-basico', 'Clássico 3×3 | Plataforma Jogo da Velha', 'versão clássica 3×3'],
    'movement 3x3' => ['/3-mover', 'Movimento 3×3 | Plataforma Jogo da Velha', 'mova as suas pelo tabuleiro 3×3'],
    'rotation 3x3' => ['/3-rotacao', 'Rotação 3×3 | Plataforma Jogo da Velha', 'remove a sua mais antiga'],
    'classic 4x4' => ['/4-3-basico', 'Clássico 4×4 | Plataforma Jogo da Velha', 'última jogada adversária'],
    'movement 4x4' => ['/4-3-mover', 'Movimento 4×4 | Plataforma Jogo da Velha', 'Coloque três peças'],
    'rotation 4x4' => ['/4-3-rotacao', 'Rotação 4×4 | Plataforma Jogo da Velha', 'substitui a sua mais antiga'],
    'blocking' => ['/4-3-bloqueio', 'Bloqueio 4×4 | Plataforma Jogo da Velha', 'não bloqueia uma das pontas'],
    'hidden' => ['/4-3-oculto', 'Oculto 4×4 | Plataforma Jogo da Velha', 'marcas escondidas'],
    'movement four' => ['/4-mover', 'Linha de 4 com Movimento | Plataforma Jogo da Velha', 'formar uma linha de quatro'],
    'two lines' => ['/2-linhas', 'Duas Linhas 5×5 | Plataforma Jogo da Velha', 'duas linhas de três'],
    'score' => ['/5-cheia', 'Pontuação 5×5 | Plataforma Jogo da Velha', 'Some pontos'],
    'dice' => ['/dado', 'Dados da Velha | Plataforma Jogo da Velha', 'tabuleiro e a coluna'],
    'crazy' => ['/maluca', 'Conquista Maluca | Plataforma Jogo da Velha', 'peças de tamanhos diferentes'],
    'ultimate' => ['/ultimate', 'Ultimate Tic-Tac-Toe | Plataforma Jogo da Velha', 'Conquiste subtabuleiros'],
]);

it('publishes a sitemap containing only the catalogue and fourteen game routes', function () {
    config()->set('app.url', 'https://preview.example.test');

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();
    $locations = [];
    foreach ($xml->url as $url) {
        $locations[] = (string) $url->loc;
    }
    expect($locations)->toEqualCanonicalizing(array_map(
        fn (string $path): string => 'https://preview.example.test'.$path,
        ['/', '/3-basico', '/3-mover', '/3-rotacao', '/4-3-basico', '/4-3-mover', '/4-3-rotacao', '/4-3-bloqueio', '/4-3-oculto', '/4-mover', '/2-linhas', '/5-cheia', '/dado', '/maluca', '/ultimate'],
    ));
    expect($locations)->not->toContain('https://preview.example.test/privacidade');
    expect($response->getContent())->not->toContain('livewire', 'privacidade', 'session', 'sitemap.xml');
});

it('serves indexable robots with the configured sitemap and valid local sharing assets', function () {
    config()->set('app.url', 'https://preview.example.test');

    $robots = $this->get('/robots.txt');
    $image = getimagesize(public_path('share-card.png'));

    $robots
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSeeText('User-agent: *')
        ->assertSeeText('Sitemap: https://preview.example.test/sitemap.xml');
    expect($robots->getContent())->not->toContain('Disallow: /', 'livewire', 'privacidade');
    expect($image[0])->toBe(1200);
    expect($image[1])->toBe(630);
    expect($image['mime'])->toBe('image/png');
    expect(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);
    expect(filesize(public_path('icon.svg')))->toBeGreaterThan(0);
});

it('keeps the privacy draft visible from the catalogue and out of indexing', function () {
    config()->set('app.url', 'https://preview.example.test');

    $this->get('/')->assertOk()->assertSee(route('privacy'), escape: false);
    $this->get('/privacidade')
        ->assertOk()
        ->assertSeeText('Rascunho pendente de revisão')
        ->assertSeeText('Josias Pereira Bueno')
        ->assertSee('href="mailto:velha@jwd.com.br"', escape: false)
        ->assertSee('href="'.route('welcome').'"', escape: false)
        ->assertSeeText('1. Sobre a plataforma e o responsável')
        ->assertSeeText('2. Informações utilizadas')
        ->assertSeeText('3. Finalidades do tratamento')
        ->assertSeeText('4. Cookies e armazenamento local')
        ->assertSeeText('5. Retenção das informações')
        ->assertSeeText('6. Compartilhamento e serviços de infraestrutura')
        ->assertSeeText('7. Privacidade de crianças e adolescentes')
        ->assertSeeText('8. Segurança')
        ->assertSeeText('9. Direitos dos titulares')
        ->assertSeeText('10. Atualizações e contato')
        ->assertSeeText('cookie de sessão')
        ->assertSeeText('Estado temporário da partida')
        ->assertSeeText('armazenamento local')
        ->assertSeeText('endereço IP')
        ->assertSeeText('Pendente de confirmação operacional')
        ->assertSeeText('EasyPanel')
        ->assertSeeText('a informar na publicação da versão definitiva')
        ->assertSee('<meta name="robots" content="noindex, follow">', escape: false)
        ->assertSee('<link rel="canonical" href="https://preview.example.test/privacidade">', escape: false);
});
