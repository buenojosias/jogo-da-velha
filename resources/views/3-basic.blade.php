<x-layout title="Testando">
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

            const cells = boardElement.querySelectorAll(".cell");
            cells.forEach(cell => {
                cell.classList.remove("winner-x", "winner-o", "disabled");
                cell.innerHTML = '';
            });

            renderBoard();
            updateStatus();
        }

        function renderBoard() {
            const cells = boardElement.querySelectorAll(".cell");
            board.forEach((cellContent, index) => {
                const cellElement = cells[index];
                if(cellContent === '') {
                    cellElement.innerHTML = '';
                }

                if (cellContent === 'x') {
                    cellElement.innerHTML = xSVG(40);
                    cellElement.classList.add("disabled");
                } else if (cellContent === 'o') {
                    cellElement.innerHTML = oSVG(40);
                    cellElement.classList.add("disabled");
                } else {
                    cellElement.classList.remove("disabled"); // Ensure empty cells are not disabled
                }
            });

            if (gameOver) {
                cells.forEach(cell => cell.classList.add('disabled'));
            }
        }

        function handleMove(index) {
            if (board[index] !== "" || gameOver) return;

            board[index] = currentPlayer;
            renderBoard();

            const winnerData = checkWinner();
            if (winnerData) {
                finishGame(winnerData);
                return;
            }

            if (board.every(cell => cell !== "")) {
                gameOver = true;
                statusTextElement.textContent = "Empate!";
                renderBoard(); // To disable all cells
                return;
            }

            currentPlayer = currentPlayer === "x" ? "o" : "x";
            updateStatus();
        }

        function updateStatus() {
            if (gameOver) return;
            statusTextElement.textContent = "Vez do jogador";

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
                const parentDivs = Array.from(boardElement.querySelectorAll('.p-1'));
                const clickedParent = cell.closest('.p-1');
                const index = parentDivs.indexOf(clickedParent);

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