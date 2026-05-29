<x-layout title="Rotação 4x4 (3 peças)">
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
            cellElement.classList.remove("cell-selected", "cell-blocked", "cell-oldest");

            if (placedPieces.x === 3 && placedPiecesOrder.x.length > 0 && index === placedPiecesOrder.x[0]) {
                cellElement.classList.add('cell-oldest');
            }

            if (placedPieces.o === 3 && placedPiecesOrder.o.length > 0 && index === placedPiecesOrder.o[0]) {
                cellElement.classList.add('cell-oldest');
            }
        }

        function handleMove(index) {
            if (gameOver) return;
            
            const cellElement = boardElement.querySelectorAll(".cell")[index];

            let winnerData;

            // --- PLACEMENT PHASE ---
            if (placedPieces[currentPlayer] < 3) {
                if (board[index] !== "") return;

                board[index] = currentPlayer;
                placedPieces[currentPlayer]++;
                placedPiecesOrder[currentPlayer].push(index);

                renderBoard();

                winnerData = checkWinner();
                if (winnerData) {
                    finishGame(winnerData);
                    return;
                }

                switchPlayer();
                return;
            }

            // --- ROTATION PHASE ---
            if (board[index] !== "") return; // Can only place on empty cells

            // Remove oldest piece
            const oldestIndex = placedPiecesOrder[currentPlayer].shift();
            board[oldestIndex] = "";

            // Add new piece
            board[index] = currentPlayer;
            placedPiecesOrder[currentPlayer].push(index);

            renderBoard();

            winnerData = checkWinner();
            if (winnerData) {
                finishGame(winnerData);
                return;
            }

            switchPlayer();
        }

        function switchPlayer() {
            currentPlayer = currentPlayer === "x" ? "o" : "x";
            updateStatus();
        }

        function updateStatus() {
            if (gameOver) return;

            if (placedPieces[currentPlayer] < 3) {
                statusTextElement.textContent = "Coloque uma peça";
            } else {
                statusTextElement.textContent = "Sua peça mais antiga será removida";
            }

            updatePlayerIcons();
        }

        startGame();
    </script>
    @endsection
</x-layout>