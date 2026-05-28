<x-layout title="Testando">

    <div class="flex flex-col items-center space-y-2">
        <div id="status-2" class="mt-2 flex items-center py-1.5 px-4 space-x-1.5 bg-neutral-600/40 border border-neutral-600 rounded-full gap-x-1">
            <span id="status-text">Vez do jogador</span>
            <span id="player-x-icon" style="display: none;"><x-x size="22" /></span>
            <span id="player-o-icon" style="display: none;"><x-o size="22" /></span>
        </div>
    </div>

    <div class="px-6">
        <div id="board-2" class="board w-full bg-neutral-600/10 border border-neutral-600/40 rounded-xl grid grid-cols-3 p-0.5">
            @for ($i = 0; $i < 9; $i++)
            <div class="p-1">
                <div class="cell w-full aspect-square transition-all duration-400 bg-neutral-800/20 hover:bg-neutral-600/40 rounded-lg border-2 border-neutral-600/40 cursor-pointer flex items-center justify-center text-4xl font-bold">
                </div>
            </div>
            @endfor
        </div>
    </div>

    <div class="flex flex-col items-center space-y-6 py-4 px-6">
        <div class="flex w-full space-x-4 text-center">
            <div class="flex-1 p-3 rounded-xl bg-neutral-900/50 border border-neutral-600 border-t-2 space-y-2 border-t-green-500">
                <p class="text-xs font-semibold flex items-center justify-center gap-1">jogador <x-o size="16" /></p>
                <p id="player-o-score" class="text-3xl font-bold">0</p>
            </div>
            <div class="flex-1 p-3 rounded-xl bg-neutral-900/50 border border-neutral-600 border-t-2 space-y-2 border-t-blue-500">
                <p class="text-xs font-semibold flex items-center justify-center gap-1">jogador <x-x size="16" /></p>
                <p id="player-x-score" class="text-3xl font-bold">0</p>
            </div>
        </div>
        <div class="w-full flex flex-col gap-4 font-bold">
            <div class="flex gap-3">
                <button id="restartBtn" class="px-4 py-3 flex-1 bg-sky-600 hover:bg-sky-700 text-white cursor-pointer rounded-lg transition-all duration-400">Reiniciar</button>
                <button class="px-6 py-3 border border-neutral-600 hover:bg-neutral-600 text-white cursor-pointer rounded-lg transition-all duration-400">?</button>
            </div>
            <button class="px-4 py-2.5 flex-1 bg-red-600 hover:bg-red-700 text-white cursor-pointer rounded-lg transition-all duration-400">Sair</button>
        </div>
    </div>
    
    @section('scripts')
    <script>
        const boardElement = document.getElementById("board-2");
        const statusTextElement = document.getElementById("status-text");
        const restartBtn = document.getElementById("restartBtn");
        const playerXScoreElement = document.getElementById("player-x-score");
        const playerOScoreElement = document.getElementById("player-o-score");

        const oSVG = (size, color = 'oklch(79.5% 0.184 86.047)') => `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="${color}" viewBox="0 0 256 256"><path d="M128,20A108,108,0,1,0,236,128,108.12,108.12,0,0,0,128,20Zm0,192a84,84,0,1,1,84-84A84.09,84.09,0,0,1,128,212Z"></path></svg>`;
        const xSVG = (size, color = 'oklch(68.5% 0.169 237.323)') => `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="${color}" viewBox="0 0 256 256"><path d="M208.49,191.51a12,12,0,0,1-17,17L128,145,64.49,208.49a12,12,0,0,1-17-17L111,128,47.51,64.49a12,12,0,0,1,17-17L128,111l63.51-63.52a12,12,0,0,1,17,17L145,128Z"></path></svg>`;

        let board = [];
        let currentPlayer = "x";
        let gameOver = false;
        let score = { x: 0, o: 0 };
        let startingPlayer = Math.random() < 0.5 ? "X" : "O";

        const winningCombinations = [
            [0,1,2],
            [3,4,5],
            [6,7,8],
            [0,3,6],
            [1,4,7],
            [2,5,8],
            [0,4,8],
            [2,4,6]
        ];

        function getStartingPlayer() {
            return startingPlayer;
        }

        function toggleStartingPlayer() {
            startingPlayer = startingPlayer === 'x' ? 'o' : 'x';
        }

        function saveScores() {
            localStorage.setItem('tic-tac-toe-scores', JSON.stringify(score));
        }

        function loadScores() {
            const savedScores = localStorage.getItem('tic-tac-toe-scores');
            if (savedScores) {
                score = JSON.parse(savedScores);
            }
        }
        
        function updateScoreboard() {
            playerXScoreElement.textContent = score.x;
            playerOScoreElement.textContent = score.o;
        }



        // ===============================
        // INICIAR PARTIDA
        // ===============================

        function startGame() {
            board = ["","","","","","","","",""];
            gameOver = false;
            currentPlayer = getStartingPlayer();
            statusTextElement.textContent = "Vez do jogador";

            loadScores();
            updateScoreboard();

            const cells = boardElement.querySelectorAll(".cell");
            cells.forEach((cell, index) => {
                cell.classList.remove("winner"); // Remove winner class
                cell.addEventListener("click", () => handleMove(index));
            });

            renderBoard();
            updateStatus();
        }

        // ===============================
        // RENDERIZAÇÃO
        // ===============================

        function renderBoard() {
            const cells = boardElement.querySelectorAll(".cell");
            board.forEach((cellContent, index) => {
                const cellElement = cells[index];
                cellElement.innerHTML = ''; // Clear previous content

                if (cellContent === 'x') {
                    cellElement.innerHTML = xSVG(40);
                } else if (cellContent === 'o') {
                    cellElement.innerHTML = oSVG(40);
                }

                if (cellContent !== "" || gameOver) {
                    cellElement.classList.add("disabled");
                } else {
                    cellElement.classList.remove("disabled");
                }
            });
        }

        // ===============================
        // JOGADA
        // ===============================

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
                return;
            }

            currentPlayer = currentPlayer === "x" ? "o" : "x";

            updateStatus();
        }

        // ===============================
        // VERIFICAR VENCEDOR
        // ===============================

        function checkWinner() {
            for (const combo of winningCombinations) {
                const [a,b,c] = combo;

                if (
                board[a] &&
                board[a] === board[b] &&
                board[a] === board[c]
                ) {
                return {
                    player: board[a],
                    combo
                };
                }
            }

            return null;
        }

        // ===============================
        // FINALIZAR PARTIDA
        // ===============================

        function finishGame(winnerData) {
            gameOver = true;

            statusTextElement.textContent = `Jogador ${winnerData.player} venceu!`;
            
            score[winnerData.player]++;
            saveScores();
            updateScoreboard();

            const cells = boardElement.querySelectorAll(".cell");

            winnerData.combo.forEach(index => {
                cells[index].classList.add("winner");
            });
        }

        // ===============================
        // STATUS
        // ===============================

        function updateStatus() {
            const xIcon = document.getElementById("player-x-icon");
            const oIcon = document.getElementById("player-o-icon");

            if (currentPlayer === "x") {
                xIcon.style.display = "inline";
                oIcon.style.display = "none";
            } else {
                xIcon.style.display = "none";
                oIcon.style.display = "inline";
            }
        }

        // ===============================
        // NOVA PARTIDA
        // ===============================

        restartBtn.addEventListener("click", () => {
            toggleStartingPlayer();
            startGame();
        });

        // ===============================
        // INÍCIO
        // ===============================

        startGame();
    </script>
    @endsection
</x-layout>