<x-layout title="Linha de 4 com Movimento">
    <x-board cells="16" />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const winningCombinations = [
            [0,1,2,3], [4,5,6,7], [8,9,10,11], [12,13,14,15],
            [0,4,8,12], [1,5,9,13], [2,6,10,14], [3,7,11,15],
            [0,5,10,15], [3,6,9,12]
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
            if (placedPieces[currentPlayer] < 4) {
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

            if (placedPieces[currentPlayer] < 4) {
                statusTextElement.textContent = "Coloque uma peça";
            } else {
                statusTextElement.textContent = "Mova uma peça";
            }

            updatePlayerIcons();
        }

        function checkWinner() {
            for (const combo of winningCombinations) {
                const [a, b, c, d] = combo;
                if (board[a] && board[a] === board[b] && board[a] === board[c] && board[a] === board[d]) {
                    return { player: board[a], combo: combo };
                }
            }
            return null;
        }

        startGame();
    </script>
    @endsection
</x-layout>