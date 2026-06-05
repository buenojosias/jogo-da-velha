<x-layout title="2 Linhas 5x5">
    <x-board cells="25" />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const winningCombinationsByDirection = {
            horizontal: [
                [0, 1, 2], [1, 2, 3], [2, 3, 4],
                [5, 6, 7], [6, 7, 8], [7, 8, 9],
                [10, 11, 12], [11, 12, 13], [12, 13, 14],
                [15, 16, 17], [16, 17, 18], [17, 18, 19],
                [20, 21, 22], [21, 22, 23], [22, 23, 24],
            ],
            vertical: [
                [0, 5, 10], [5, 10, 15], [10, 15, 20],
                [1, 6, 11], [6, 11, 16], [11, 16, 21],
                [2, 7, 12], [7, 12, 17], [12, 17, 22],
                [3, 8, 13], [8, 13, 18], [13, 18, 23],
                [4, 9, 14], [9, 14, 19], [14, 19, 24],
            ],
            diag_tl_br: [
                [0, 6, 12], [1, 7, 13], [2, 8, 14],
                [5, 11, 17], [6, 12, 18], [7, 13, 19],
                [10, 16, 22], [11, 17, 23], [12, 18, 24],
            ],
            diag_tr_bl: [
                [2, 6, 10], [3, 7, 11], [4, 8, 12],
                [7, 11, 15], [8, 12, 16], [9, 13, 17],
                [12, 16, 20], [13, 17, 21], [14, 18, 22],
            ]
        };
        const winningCombinations = Object.values(winningCombinationsByDirection).flat();

        function checkWinner() {
            let lines = {
                x: 0,
                o: 0
            };
            let allWinningCombos = {
                x: [],
                o: []
            };

            for (const direction in winningCombinationsByDirection) {
                const combosInDirection = winningCombinationsByDirection[direction];

                let playerWinningCombos = {
                    x: [],
                    o: []
                };

                for (const combo of combosInDirection) {
                    const [a, b, c] = combo;
                    if (board[a] && board[a] === board[b] && board[a] === board[c]) {
                        playerWinningCombos[board[a]].push(combo);
                        allWinningCombos[board[a]].push(combo);
                    }
                }

                for (const player in playerWinningCombos) {
                    const combos = playerWinningCombos[player];
                    if (combos.length === 0) continue;

                    const remainingCombos = [...combos];
                    let numLines = 0;
                    while (remainingCombos.length > 0) {
                        numLines++;
                        const line = [remainingCombos.pop()];
                        let i = 0;
                        while (i < line.length) {
                            const currentCombo = line[i];
                            let j = remainingCombos.length - 1;
                            while (j >= 0) {
                                const otherCombo = remainingCombos[j];
                                const intersection = currentCombo.filter(c => otherCombo.includes(c));
                                if (intersection.length > 0) {
                                    line.push(otherCombo);
                                    remainingCombos.splice(j, 1);
                                }
                                j--;
                            }
                            i++;
                        }
                    }
                    lines[player] += numLines;
                }
            }

            if (lines.x >= 2) {
                return { player: 'x', combos: allWinningCombos.x };
            }

            if (lines.o >= 2) {
                return { player: 'o', combos: allWinningCombos.o };
            }

            return null;
        }

        function startGame() {
            board = Array(25).fill("");
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