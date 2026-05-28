<x-layout title="Tradicional - 3 peças">
    <x-board cells="9" />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        function startGame() {
            board = Array(9).fill("");
            gameOver = false;
            currentPlayer = startingPlayer;
            modalWinElement.style.display = 'none';

            resetBoardUI();

            renderBoard();
            updateStatus();
        }

        function onCellRender(cellElement, cellContent, index) {
            cellElement.classList.remove("disabled");

            if (cellContent !== '') {
                cellElement.classList.add("disabled");
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
            updatePlayerIcons();
        }

        startGame();
    </script>
    @endsection
</x-layout>