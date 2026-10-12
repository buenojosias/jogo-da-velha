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
    'rotation 3x3' => ['/3-rotacao', '3-rotation', 'Rotação 3x3', ['id="board"', 'cells-9', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'let placedPiecesOrder']],
    'classic 4x4 with lines of 3' => ['/4-3-basico', 'experimental-classic-4x3', 'Clássico 4×4', ['data-testid="classic-board"', 'data-testid="cell-0"', 'data-testid="cell-15"', 'data-testid="match-status"', 'data-testid="new-match-button"']],
    'movement 4x4 with lines of 3' => ['/4-3-mover', '4x3-move', 'Movimento 4x4 (3 peças)', ['id="board"', 'cells-16', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'let selectedPiece = null']],
    'rotation 4x4 with lines of 3' => ['/4-3-rotacao', '4x3-rotation', 'Rotação 4x4 (3 peças)', ['id="board"', 'cells-16', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'let placedPiecesOrder']],
    'blocking 4x4' => ['/4-3-bloqueio', '4x3-block', 'Bloqueio 4x4', ['id="board"', 'cells-16', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'function getBlockers']],
    'hidden 4x4' => ['/4-3-oculto', '4x3-hidden', 'Oculto 4x4', ['id="board"', 'cells-16', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'piece-hidden']],
    'movement 4x4 with lines of 4' => ['/4-mover', '4x4-move', 'Linha de 4 com Movimento', ['id="board"', 'cells-16', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'const [a, b, c, d]']],
    'two lines 5x5' => ['/2-linhas', '5x5-2-lines', '2 Linhas 5x5', ['id="board"', 'cells-25', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'winningCombinationsByDirection']],
    'score 5x5' => ['/5-cheia', '5-full', 'Pontuação 5x5 (Blade)', ['id="board"', 'cells-25', 'id="status"', 'id="restartBtn"', 'id="modal-win"', 'function calculateScore']],
    'dice' => ['/dado', 'dice', 'Dados da Velha', ['id="board1"', 'id="board2"', 'id="dice"', 'id="rollBtn"', 'id="restartBtn"', 'function rotateDice']],
    'crazy' => ['/maluca', 'crazy', 'Jogo da Velha Maluca', ['id="board"', 'id="piecesX"', 'id="piecesO"', 'id="status"', 'function sizeValue', 'function placePiece']],
    'ultimate' => ['/ultimate', 'ultimate', 'Jogo da Velha 2.0', ['id="game"', 'id="currentPlayer"', 'id="targetBoardText"', 'id="winnerOverlay"', 'function isBoardPlayable', 'function makeMove']],
]);

it('renders the expected number of cells for the :dataset shared-layout modality', function (string $uri, int $cellCount) {
    $response = $this->get($uri);

    $response->assertOk();

    $cellMarker = $uri === '/3-mover' ? 'data-testid="cell-' : 'class="cell"';
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
