<x-layout title="Jogo da Velha 4x4 - Modo básico">
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

        function startGame() {
            board = Array(16).fill("");
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