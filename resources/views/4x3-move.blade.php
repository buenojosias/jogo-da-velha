<x-layout title="4x3 peças - mover">
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
        let selectedPiece = null;

        function startGame() {
            board = Array(16).fill("");
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