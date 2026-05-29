<x-layout title="3 peças - Modo oculto">
    <x-board cells="16" />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const winningCombinations = [
            // Horizontais
            [0,1,2], [1,2,3], [4,5,6], [5,6,7], [8,9,10], [9,10,11], [12,13,14], [13,14,15],
            // Verticais
            [0,4,8], [4,8,12], [1,5,9], [5,9,13], [2,6,10], [6,10,14], [3,7,11], [7,11,15],
            // Diagonais
            [0,5,10], [1,6,11], [4,9,14], [5,10,15], [2,5,8], [3,6,9], [6,9,12], [7,10,13]
        ];

        let placedPieces = { x: 0, o: 0 };
        let placedPiecesOrder = { x: [], o: [] };

        function startGame() {
            board = Array(16).fill("");
            gameOver = false;
            currentPlayer = startingPlayer;
            modalWinElement.style.display = 'none';

            placedPieces = { x: 0, o: 0 };
            placedPiecesOrder = { x: [], o: [] };

            resetBoardUI(["cell-selected", "cell-blocked", "cell-oldest"]);

            renderBoard();
            updateStatus();
        }

        function onCellRender(cellElement, cellContent, index) {
            // Hide the piece by adding a class to the SVG
            const svg = cellElement.querySelector('svg');
            if (svg) {
                svg.classList.add('piece-hidden');
            }
        }

        function handleMove(index) {
            if (gameOver) return;

            const cellContent = board[index];
            const opponent = currentPlayer === "x" ? "o" : "x";

            // Rule: If a player clicks on a cell that already has an opponent's piece, nothing happens and they lose the turn.
            if (cellContent === opponent) {
                switchPlayer();
                return;
            }

            // Rule: If a player clicks on a cell that already has their own piece, that piece moves to a random empty cell.
            if (cellContent === currentPlayer) {
                const emptyCells = board.map((c, i) => (c === "" ? i : null)).filter(i => i !== null);
                
                if (emptyCells.length > 0) {
                    const newIndex = emptyCells[Math.floor(Math.random() * emptyCells.length)];

                    // Move the piece
                    board[index] = "";
                    board[newIndex] = currentPlayer;

                    // Update the order of pieces
                    const orderIndex = placedPiecesOrder[currentPlayer].indexOf(index);
                    if (orderIndex > -1) {
                        placedPiecesOrder[currentPlayer].splice(orderIndex, 1);
                    }
                    placedPiecesOrder[currentPlayer].push(newIndex);
                    
                    renderBoard();
                    const winnerData = checkWinner();
                    if (winnerData) {
                        finishGame(winnerData);
                    } else {
                        switchPlayer();
                    }
                }
                // If no empty cells, the player can't move, so they can try another action. Turn is not lost.
                return;
            }

            // Rule: Clicking on an empty cell.
            if (cellContent === "") {
                // Rule: Each player can have 5 pieces.
                if (placedPieces[currentPlayer] < 5) {
                    // Still in placement phase
                    placedPieces[currentPlayer]++;
                } else {
                    // Rotation phase: remove the oldest piece
                    const oldestIndex = placedPiecesOrder[currentPlayer].shift();
                    if (oldestIndex !== undefined) {
                        board[oldestIndex] = "";
                    }
                }

                board[index] = currentPlayer;
                placedPiecesOrder[currentPlayer].push(index);

                renderBoard();

                const winnerData = checkWinner();
                if (winnerData) {
                    finishGame(winnerData);
                } else {
                    switchPlayer();
                }
            }
        }

        function switchPlayer() {
            currentPlayer = currentPlayer === "x" ? "o" : "x";
            updateStatus();
        }

        function updateStatus() {
            if (gameOver) return;

            statusTextElement.textContent = `Vez do jogador`;

            updatePlayerIcons();
        }

        startGame();
    </script>
    @endsection
</x-layout>