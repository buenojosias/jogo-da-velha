<x-layout title="3 peças - mover">
    <x-board />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const boardElement = document.getElementById("board");
        const statusTextElement = document.getElementById("status-text");
        const restartBtn = document.getElementById("restartBtn");
        const playerXScoreElement = document.getElementById("player-x-score");
        const playerOScoreElement = document.getElementById("player-o-score");
        const playerXIcon = document.getElementById("player-x-icon");
        const playerOIcon = document.getElementById("player-o-icon");
        const modalWinElement = document.getElementById("modal-win");
        const winnerIconContainer = document.getElementById("winner-icon-container");
        const modalRestartBtn = document.getElementById("modal-restart-btn");

        const oSVG = (size, color = 'oklch(79.5% 0.184 86.047)') => `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="${color}" viewBox="0 0 256 256"><path d="M128,20A108,108,0,1,0,236,128,108.12,108.12,0,0,0,128,20Zm0,192a84,84,0,1,1,84-84A84.09,84.09,0,0,1,128,212Z"></path></svg>`;
        const xSVG = (size, color = 'oklch(68.5% 0.169 237.323)') => `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="${color}" viewBox="0 0 256 256"><path d="M208.49,191.51a12,12,0,0,1-17,17L128,145,64.49,208.49a12,12,0,0,1-17-17L111,128,47.51,64.49a12,12,0,0,1,17-17L128,111l63.51-63.52a12,12,0,0,1,17,17L145,128Z"></path></svg>`;

        let board = [];
        let currentPlayer = "x";
        let gameOver = false;
        let score = { x: 0, o: 0 }; // Initialize score
        let startingPlayer = Math.random() < 0.5 ? "x" : "o";

        // let placedPieces = { x: 0, o: 0 };
        // let selectedPiece = null;

        const winningCombinations = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        function getStartingPlayer() {
            startingPlayer = startingPlayer === 'x' ? 'o' : 'x';
        }

        function toggleStartingPlayer() {
            startingPlayer = startingPlayer === 'x' ? 'o' : 'x';
        }

        function updateScoreboard() {
            playerXScoreElement.textContent = score.x;
            playerOScoreElement.textContent = score.o;
        }

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

        function checkWinner() {
            for (const combo of winningCombinations) {
                const [a, b, c] = combo;
                if (board[a] && board[a] === board[b] && board[a] === board[c]) {
                    return { player: board[a], combo };
                }
            }
            return null;
        }

        function finishGame(winnerData) {
            gameOver = true;

            score[winnerData.player]++; // Increment score
            updateScoreboard(); // Update scoreboard here

            const cells = boardElement.querySelectorAll(".cell");
            winnerData.combo.forEach(index => {
                cells[index].classList.add(`winner-${winnerData.player}`);
            });

            winnerIconContainer.innerHTML = winnerData.player === 'x' ? xSVG(28) : oSVG(28);
            modalWinElement.style.display = 'flex';
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