<x-layout title="Testando">

    <div class="flex flex-col items-center space-y-2">
        <div class="mt-2 flex items-center py-1.5 px-4 space-x-1.5 bg-neutral-600/40 border border-neutral-600 rounded-full gap-x-1">Vez do jogador
            <x-o size="22" />
        </div>
    </div>

    <div class="px-6">
        <div class="board w-full bg-neutral-600/10 border border-neutral-600/40 rounded-xl grid grid-cols-3 p-0.5">
            @for ($i = 0; $i < 9; $i++)
            <div class="p-1">
                @php
                    $symbol = array_rand(['x', 'o', '']);
                @endphp
                <div class="cell w-full aspect-square transition-all duration-400 bg-neutral-800/20 hover:bg-neutral-600/40 rounded-lg border-2 border-neutral-600/40 cursor-pointer flex items-center justify-center text-4xl font-bold">
                    @if ($symbol === 1)
                        <x-x size="44" />
                    @elseif ($symbol === 2)
                        <x-o size="44" />
                    @endif
                </div>
            </div>
            @endfor
        </div>
    </div>

    <div class="flex flex-col items-center space-y-6 py-4 px-6">
        <div class="flex w-full space-x-4 text-center">
            <div class="flex-1 p-3 rounded-xl bg-neutral-900/50 border border-neutral-600 border-t-2 space-y-2 border-t-green-500">
                <p class="text-xs font-semibold flex items-center justify-center gap-1">JOGADOR <x-o size="16" /></p>
                <p class="text-3xl font-bold">2</p>
            </div>
            <div class="flex-1 p-3 rounded-xl bg-neutral-900/50 border border-neutral-600 border-t-2 space-y-2 border-t-blue-500">
                <p class="text-xs font-semibold flex items-center justify-center gap-1">JOGADOR <x-x size="16" /></p>
                <p class="text-3xl font-bold">1</p>
            </div>
        </div>
        <div class="w-full flex flex-col gap-4 font-bold">
            <div class="flex gap-3">
                <button id="restartBtn" class="px-4 py-3 flex-1 bg-sky-600 hover:bg-sky-700 text-white cursor-pointer rounded-lg transition-all duration-400">REINICIAR</button>
                <button class="px-6 py-3 border border-neutral-600 hover:bg-neutral-600 text-white cursor-pointer rounded-lg transition-all duration-400">?</button>
            </div>
            <button class="px-4 py-2.5 flex-1 bg-red-600 hover:bg-red-700 text-white cursor-pointer rounded-lg transition-all duration-400">SAIR</button>
        </div>
            <div class="status bg-red-500" id="status"></div>
            <div class="board bg-amber-600" id="board"></div>
    </div>
    
    @section('scripts')
    <script>
        const boardElement = document.getElementById("board");
        const statusElement = document.getElementById("status");
        const restartBtn = document.getElementById("restartBtn");

        let board = [];
        let currentPlayer = "X";
        let gameOver = false;

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

        // ===============================
        // DEFINIÇÃO DO PRIMEIRO JOGADOR
        // ===============================

        function getStartingPlayer() {
        const saved = localStorage.getItem("ticTacToeStarter");

        // Primeira vez => sorteia aleatoriamente
        if (!saved) {
            const randomStarter = Math.random() < 0.5 ? "X" : "O";
            localStorage.setItem("ticTacToeStarter", randomStarter);
            return randomStarter;
        }

        return saved;
        }

        function toggleStartingPlayer() {
            const currentStarter = localStorage.getItem("ticTacToeStarter");
            const nextStarter = currentStarter === "X" ? "O" : "X";
            localStorage.setItem("ticTacToeStarter", nextStarter);
        }

        // ===============================
        // INICIAR PARTIDA
        // ===============================

        function startGame() {
        board = ["","","","","","","","",""];
        gameOver = false;

        currentPlayer = getStartingPlayer();

        renderBoard();
        updateStatus();
        }

        // ===============================
        // RENDERIZAÇÃO
        // ===============================

        function renderBoard() {
        boardElement.innerHTML = "";

        board.forEach((cell, index) => {
            const cellElement = document.createElement("div");

            cellElement.classList.add("cell");

            if (cell !== "" || gameOver) {
            cellElement.classList.add("disabled");
            }

            cellElement.textContent = cell;

            cellElement.addEventListener("click", () => handleMove(index));

            boardElement.appendChild(cellElement);
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
            statusElement.textContent = "Empate!";
            return;
        }

        currentPlayer = currentPlayer === "X" ? "O" : "X";

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

        statusElement.textContent =
            `Jogador ${winnerData.player} venceu!`;

        const cells = document.querySelectorAll(".cell");

        winnerData.combo.forEach(index => {
            cells[index].classList.add("winner");
        });
        }

        // ===============================
        // STATUS
        // ===============================

        function updateStatus() {
        statusElement.textContent =
            `Vez do jogador ${currentPlayer}`;
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