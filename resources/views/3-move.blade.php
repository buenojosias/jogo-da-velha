<x-layout title="Movimento 3x3">
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
        let selectedPiece = null;

        function startGame() {
            board = Array(9).fill("");
            gameOver = false;
            currentPlayer = startingPlayer;
            modalWinElement.style.display = 'none';

            placedPieces = { x: 0, o: 0 };
            selectedPiece = null;

            resetBoardUI(["cell-selected"]);

            renderBoard();
            updateStatus();
        }

        function onCellRender(cellElement, cellContent, index) {
            cellElement.classList.remove("cell-selected");
            if (index === selectedPiece) {
                cellElement.classList.add("cell-selected");
            }
        }

        function handleMove(index) {
            if (gameOver) return;
            let winnerData;

            // --- PLACEMENT PHASE ---
            if (placedPieces[currentPlayer] < 3) {
                if (board[index] !== "") return;

                board[index] = currentPlayer;
                placedPieces[currentPlayer]++;

                renderBoard();

                winnerData = checkWinner();
                if (winnerData) {
                    finishGame(winnerData);
                    return;
                }

                switchPlayer();
                return;
            }

            // --- MOVEMENT PHASE ---
            if (selectedPiece === null) {
                if (board[index] === currentPlayer) {
                    selectedPiece = index;
                    renderBoard();
                }
            } else {
                if (index === selectedPiece) {
                    selectedPiece = null;
                    renderBoard();
                    return;
                }

                if (board[index] === currentPlayer) {
                    selectedPiece = index;
                    renderBoard();
                    return;
                }

                if (board[index] === "") {
                    board[index] = currentPlayer;
                    board[selectedPiece] = "";
                    selectedPiece = null;

                    renderBoard();

                    winnerData = checkWinner();
                    if (winnerData) {
                        finishGame(winnerData);
                        return;
                    }

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

            if (placedPieces[currentPlayer] < 3) {
                statusTextElement.textContent = "Coloque uma peça";
            } else {
                statusTextElement.textContent = "Mova uma peça";
            }

            updatePlayerIcons();
        }

        startGame();
    </script>
    @endsection
</x-layout>