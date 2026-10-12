<?php

it('renders the :dataset modality route with its expected view and structure', function (
    string $uri,
    string $view,
    string $title,
    array $structuralMarkers,
) {
    $response = $this->get($uri);

    $response
        ->assertOk()
        ->assertViewIs($view)
        ->assertSeeText($title);

    foreach ($structuralMarkers as $structuralMarker) {
        expect($response->getContent())->toContain($structuralMarker);
    }
})->with([
    'classic 3x3' => ['/3-basico', 'experimental-classic', 'Clássico 3×3', ['data-testid="classic-board"', 'data-testid="cell-0"', 'data-testid="cell-8"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'movement 3x3' => ['/3-mover', 'experimental-move-3x3', 'Movimento 3×3', ['data-testid="movement-board"', 'data-testid="cell-0"', 'data-testid="cell-8"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'rotation 3x3' => ['/3-rotacao', 'rotation-match', 'Rotação 3×3', ['data-testid="rotation-board"', 'data-testid="cell-0"', 'data-testid="cell-8"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'classic 4x4 with lines of 3' => ['/4-3-basico', 'experimental-classic-4x3', 'Clássico 4×4', ['data-testid="classic-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'movement 4x4 with lines of 3' => ['/4-3-mover', 'experimental-move-4x3', 'Movimento 4×4', ['data-testid="movement-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'rotation 4x4 with lines of 3' => ['/4-3-rotacao', 'rotation-match', 'Rotação 4×4 (3 peças)', ['data-testid="rotation-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'blocking 4x4' => ['/4-3-bloqueio', 'block-4x3', 'Bloqueio 4×4', ['data-testid="classic-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'hidden 4x4' => ['/4-3-oculto', 'hidden-4x3', 'Oculto 4×4', ['data-testid="hidden-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'movement 4x4 with lines of 4' => ['/4-mover', 'experimental-move-4x4', 'Linha de 4 com Movimento', ['data-testid="movement-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'two lines 5x5' => ['/2-linhas', 'two-lines-5x5', 'Duas Linhas 5×5', ['data-testid="classic-board"', 'data-testid="cell-0"', 'data-testid="cell-24"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'score 5x5' => ['/5-cheia', 'full-5x5', 'Pontuação 5×5', ['data-testid="classic-board"', 'data-testid="cell-0"', 'data-testid="cell-24"', 'data-testid="round-points-x"', 'data-testid="new-match-button"']],
    'dice' => ['/dado', 'dice', 'Dados da Velha', ['id="board1"', 'id="board2"', 'id="dice"', 'id="rollBtn"', 'id="restartBtn"', 'function rotateDice']],
    'crazy' => ['/maluca', 'crazy', 'Jogo da Velha Maluca', ['id="board"', 'id="piecesX"', 'id="piecesO"', 'id="status"', 'function sizeValue', 'function placePiece']],
    'ultimate' => ['/ultimate', 'ultimate', 'Jogo da Velha 2.0', ['id="game"', 'id="currentPlayer"', 'id="targetBoardText"', 'id="winnerOverlay"', 'function isBoardPlayable', 'function makeMove']],
]);

it('renders the expected number of cells for the :dataset shared-layout modality', function (string $uri, int $cellCount) {
    $response = $this->get($uri);

    $response->assertOk();

    $cellMarker = in_array($uri, ['/3-mover', '/4-3-mover', '/4-mover', '/3-rotacao', '/4-3-rotacao', '/4-3-bloqueio', '/4-3-oculto', '/2-linhas', '/5-cheia'], true) ? 'data-testid="cell-' : 'class="cell"';
    expect(substr_count($response->getContent(), $cellMarker))->toBe($cellCount);
})->with([
    'movement 3x3' => ['/3-mover', 9],
    'rotation 3x3' => ['/3-rotacao', 9],
    'movement 4x4 with lines of 3' => ['/4-3-mover', 16],
    'rotation 4x4 with lines of 3' => ['/4-3-rotacao', 16],
    'blocking 4x4' => ['/4-3-bloqueio', 16],
    'hidden 4x4' => ['/4-3-oculto', 16],
    'movement 4x4 with lines of 4' => ['/4-mover', 16],
    'two lines 5x5' => ['/2-linhas', 25],
    'score 5x5' => ['/5-cheia', 25],
]);

it('links every modality from the catalogue', function (string $routeName) {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee(route($routeName), escape: false);
})->with([
    '3-basic',
    '3-move',
    '3-rotation',
    '4x3-basic',
    '4x3-move',
    '4x3-rotation',
    '4x3-block',
    '4x3-hidden',
    '4x4-move',
    '5x5-2-lines',
    '5-full',
    'dice',
    'crazy',
    'ultimate',
]);
