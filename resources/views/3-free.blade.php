<x-layout title="3 peças - mover">
    <x-board />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const winningCombinations = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        function startGame() {
            board = Array(9).fill("");
            gameOver = false;
            currentPlayer = startingPlayer;
            modalWinElement.style.display = 'none';

            placedPieces = { x: 0, o: 0 };
            selectedPiece = null;

            const cells = boardElement.querySelectorAll(".cell");
            cells.forEach(cell => {
                cell.classList.remove("winner-x", "winner-o", "cell-selected");
                cell.innerHTML = '';
            });

            renderBoard();
            updateStatus();
        }

        function renderBoard() {
            const cells = boardElement.querySelectorAll(".cell");
            board.forEach((cellContent, index) => {
                const cellElement = cells[index];
                cellElement.innerHTML = '';
                cellElement.classList.remove("cell-selected");

                if (cellContent === 'x') {
                    cellElement.innerHTML = xSVG(40);
                } else if (cellContent === 'o') {
                    cellElement.innerHTML = oSVG(40);
                }

                if (index === selectedPiece) {
                    cellElement.classList.add("cell-selected");
                }
            });
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

            if (currentPlayer === "x") {
                playerXIcon.style.display = "inline";
                playerOIcon.style.display = "none";
            } else {
                playerOIcon.style.display = "inline";
                playerXIcon.style.display = "none";
            }
        }
        
        boardElement.addEventListener('click', (event) => {
            if (gameOver) return;

            const cell = event.target.closest('.cell');
            if (cell) {
                const cellParent = cell.closest('.p-1');
                const parents = Array.from(boardElement.children);
                const index = parents.indexOf(cellParent);

                if (index > -1) {
                    handleMove(index);
                }
            }
        });

        restartBtn.addEventListener("click", () => {
            toggleStartingPlayer();
            startGame();
        });

        modalRestartBtn.addEventListener("click", () => {
            toggleStartingPlayer();
            startGame();
        });

        startGame();
    </script>
    @endsection
</x-layout>