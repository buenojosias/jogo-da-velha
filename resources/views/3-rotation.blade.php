<x-layout title="3 peças - Rotação">
    <x-board cells="9" />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const winningCombinations = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        let placedPieces = { x: 0, o: 0 };
        let placedPiecesOrder = { x: [], o: [] };

        function startGame() {
            board = Array(9).fill("");
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

            // Add .cell-oldest for player X
            if (placedPieces.o >= 2 && placedPiecesOrder.x.length > 0 && index === placedPiecesOrder.x[0]) {
                cellElement.classList.add('cell-oldest');
            }

            // Add .cell-oldest for player O
            if (placedPieces.x >= 3 && placedPiecesOrder.o.length > 0 && index === placedPiecesOrder.o[0]) {
                cellElement.classList.add('cell-oldest');
            }

            const inRotationPhase = placedPieces.x === 3 && placedPieces.o === 3;

            if (inRotationPhase) {
                // Block non-empty cells
                if (cellContent !== "") {
                    cellElement.classList.add("cell-blocked");
                }
            }
        }

        function handleMove(index) {
            if (gameOver) return;
            
            const cellElement = boardElement.querySelectorAll(".cell")[index];
            if (cellElement.classList.contains('cell-blocked')) return;

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