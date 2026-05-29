<x-layout title="5x5 cheia">
    <x-board cells="25" />
    <x-footer />
    <x-modal-win />
    @section('scripts')
    <script>
        const SIZE = 5;
        let localScore = { x: 0, o: 0 };

        function calculateScore(player) {
            let total = 0;
            const sequences = getSequences(player);
            for (const seq of sequences) {
                if (seq.length === 3) total += 3;
                else if (seq.length === 4) total += 6;
                else if (seq.length >= 5) total += 10;
            }
            return total;
        }

        function updateScoreboard() {
            localScore.x = calculateScore('x');
            localScore.o = calculateScore('o');
            playerXScoreElement.textContent = localScore.x;
            playerOScoreElement.textContent = localScore.o;
        }

        function getSequences(player) {
            const sequences = [];
            const directions = [
                { dr: 0, dc: 1 },  // Horizontal
                { dr: 1, dc: 0 },  // Vertical
                { dr: 1, dc: 1 },  // Diagonal \
                { dr: 1, dc: -1 }  // Diagonal /
            ];

            for (let r_start = 0; r_start < SIZE; r_start++) {
                for (let c_start = 0; c_start < SIZE; c_start++) {
                    if (board[r_start * SIZE + c_start] !== player) continue;

                    for (const { dr, dc } of directions) {
                        const pr = r_start - dr;
                        const pc = c_start - dc;

                        if (pr >= 0 && pr < SIZE && pc >= 0 && pc < SIZE && board[pr * SIZE + pc] === player) {
                            continue;
                        }

                        let cells = [];
                        let r = r_start, c = c_start;
                        while (r >= 0 && r < SIZE && c >= 0 && c < SIZE && board[r * SIZE + c] === player) {
                            cells.push(r * SIZE + c);
                            r += dr;
                            c += dc;
                        }

                        if (cells.length >= 3) {
                            sequences.push(cells);
                        }
                    }
                }
            }
            return sequences;
        }

        function startGame() {
            board = Array(SIZE * SIZE).fill("");
            gameOver = false;
            currentPlayer = startingPlayer;
            localScore = { x: 0, o: 0 };

            modalWinElement.style.display = 'none';

            let winMessage = modalWinElement.querySelector('#win-message');
            if (winMessage) {
                winMessage.textContent = '';
            }

            resetBoardUI();
            renderBoard();
            updateStatus();
            updateScoreboard();
        }

        function handleMove(index) {
            if (gameOver || board[index] !== "") return;

            board[index] = currentPlayer;
            renderBoard();
            
            highlightNewSequences(currentPlayer, index);
            updateScoreboard();

            if (board.every(cell => cell !== "")) {
                finishGame();
                return;
            }

            switchPlayer();
        }
        
        function highlightNewSequences(player, newPieceIndex) {
            const rowPlayed = Math.floor(newPieceIndex / SIZE);
            const colPlayed = newPieceIndex % SIZE;
             const directions = [
                { dr: 0, dc: 1 }, { dr: 1, dc: 0 }, { dr: 1, dc: 1 }, { dr: 1, dc: -1 }
            ];

            for(const {dr, dc} of directions){
                let sequence = [newPieceIndex];

                let r = rowPlayed - dr, c = colPlayed - dc;
                while(r >= 0 && r < SIZE && c >= 0 && c < SIZE && board[r*SIZE+c] === player) {
                    sequence.unshift(r*SIZE+c);
                    r -= dr; c -= dc;
                }
                
                r = rowPlayed + dr, c = colPlayed + dc;
                while(r >= 0 && r < SIZE && c >= 0 && c < SIZE && board[r*SIZE+c] === player) {
                    sequence.push(r*SIZE+c);
                    r += dr; c += dc;
                }

                if(sequence.length >= 3) {
                    const cells = boardElement.querySelectorAll(".cell");
                    sequence.forEach(idx => {
                        cells[idx].classList.add('cell-selected');
                        setTimeout(() => cells[idx].classList.remove('cell-selected'), 600);
                    });
                }
            }
        }

        function switchPlayer() {
            currentPlayer = currentPlayer === "x" ? "o" : "x";
            updateStatus();
        }

        function updateStatus() {
            if (gameOver) return;
            statusTextElement.textContent = "Faça a sua jogada";
            updatePlayerIcons();
        }

        function finishGame() {
            gameOver = true;
            let finalMessage = "";
            if (localScore.x > localScore.o) {
                finalMessage = `Jogador X venceu!`;
                winnerIconContainer.innerHTML = xSVG(28);
            } else if (localScore.o > localScore.x) {
                finalMessage = `Jogador O venceu!`;
                winnerIconContainer.innerHTML = oSVG(28);
            } else {
                finalMessage = "Empate!";
                winnerIconContainer.innerHTML = xSVG(28) + oSVG(28);
            }
            
            let messageContainer = modalWinElement.querySelector('#win-message');
            if(!messageContainer) {
                messageContainer = document.createElement('span');
                messageContainer.id = 'win-message';
                messageContainer.className = 'text-xl font-bold';
                winnerIconContainer.parentNode.insertBefore(messageContainer, winnerIconContainer.nextSibling);
            }
            messageContainer.textContent = finalMessage;

            modalWinElement.style.display = 'flex';
        }
        
        function onCellRender(cellElement, cellContent) {
            if (cellContent !== '') {
                cellElement.classList.add("disabled");
            } else {
                cellElement.classList.remove("disabled");
            }
        }

        startGame();
    </script>
    @endsection
</x-layout>