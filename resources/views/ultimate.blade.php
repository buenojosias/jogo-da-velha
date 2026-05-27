<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jogo da Velha 2.0</title>

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #0b0b0f;
      color: white;
      font-family: Arial, Helvetica, sans-serif;
      padding: 24px;
    }

    h1 {
      margin-bottom: 8px;
      text-align: center;
    }

    .info {
      margin-bottom: 24px;
      text-align: center;
      line-height: 1.6;
    }

    .current-board {
      color: #5fd3ff;
      font-weight: bold;
    }

    .top-actions {
      margin-top: 14px;
    }

    .game {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      background: #8f8f95;
      padding: 8px;
      border-radius: 12px;
      width: min(92vw, 820px);
      aspect-ratio: 1;
    }

    .sub-board {
      background: #111;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-template-rows: repeat(3, 1fr);
      gap: 3px;
      padding: 3px;
      position: relative;
      border-radius: 8px;
      overflow: hidden;
      transition: all 0.2s ease;
    }

    .sub-board.active {
      box-shadow: 0 0 0 4px #42cfff;
      transform: scale(1.01);
    }

    .sub-board.locked {
      opacity: 0.45;
    }

    .cell {
      background: #000;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      user-select: none;
      font-size: clamp(20px, 3vw, 34px);
      font-weight: bold;
      aspect-ratio: 1;
      transition: background 0.15s ease;
    }

    .cell:hover {
      background: #161616;
    }

    .cell.x {
      color: #58c4ff;
    }

    .cell.o {
      color: #ff7ed1;
    }

    .sub-board-winner {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(80px, 10vw, 140px);
      font-weight: bold;
      background: rgba(0, 0, 0, 0.82);
      z-index: 5;
    }

    .sub-board-winner.x {
      color: #58c4ff;
    }

    .sub-board-winner.o {
      color: #ff7ed1;
    }

    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
      padding: 24px;
    }

    .modal {
      background: #13131a;
      border: 2px solid #444;
      border-radius: 18px;
      padding: 32px;
      text-align: center;
      max-width: 500px;
      width: 100%;
    }

    .modal h2 {
      font-size: 2rem;
      margin-top: 0;
    }

    button {
      border: none;
      background: #42cfff;
      color: black;
      font-weight: bold;
      padding: 12px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 1rem;
    }

    button:hover {
      opacity: 0.9;
    }

    @media (max-width: 700px) {
      .game {
        width: 96vw;
      }
    }
  </style>
</head>
<body>
  <h1>Jogo da Velha 2.0</h1>

  <div class="info">
    <div>
      <strong>Jogador da vez:</strong>
      <span id="currentPlayer">X</span>
    </div>

    <div class="current-board" id="targetBoardText">
      Pode jogar em qualquer subtabuleiro.
    </div>

    <div class="top-actions">
      <button onclick="restartGame()">Recomeçar</button>
    </div>
  </div>

  <div class="game" id="game"></div>

  <div class="overlay" id="winnerOverlay" style="display: none;">
    <div class="modal">
      <h2 id="winnerText"></h2>
      <button onclick="restartGame()">Jogar novamente</button>
    </div>
  </div>

  <script>
    const gameElement = document.getElementById('game');
    const currentPlayerElement = document.getElementById('currentPlayer');
    const targetBoardText = document.getElementById('targetBoardText');
    const winnerOverlay = document.getElementById('winnerOverlay');
    const winnerText = document.getElementById('winnerText');

    const WIN_PATTERNS = [
      [0,1,2],
      [3,4,5],
      [6,7,8],
      [0,3,6],
      [1,4,7],
      [2,5,8],
      [0,4,8],
      [2,4,6]
    ];

    let boards = [];
    let mainBoard = Array(9).fill(null);

    let currentPlayer = 'X';
    let startingPlayer = 'X';
    let targetBoard = null;
    let gameOver = false;

    function createEmptyBoard() {
      return {
        cells: Array(9).fill(null),
        winner: null,
        draw: false
      };
    }

    function getSavedStarter() {
      const saved = localStorage.getItem('ultimateTicTacToeStarter');

      if (saved === 'X' || saved === 'O') {
        return saved;
      }

      return Math.random() < 0.5 ? 'X' : 'O';
    }

    function saveNextStarter(currentStarter) {
      const nextStarter = currentStarter === 'X' ? 'O' : 'X';
      localStorage.setItem('ultimateTicTacToeStarter', nextStarter);
    }

    function initializeGame() {
      boards = Array.from({ length: 9 }, createEmptyBoard);
      mainBoard = Array(9).fill(null);

      startingPlayer = getSavedStarter();
      currentPlayer = startingPlayer;

      targetBoard = null;
      gameOver = false;

      winnerOverlay.style.display = 'none';

      saveNextStarter(startingPlayer);

      render();
    }

    function render() {
      gameElement.innerHTML = '';

      boards.forEach((board, boardIndex) => {
        const subBoard = document.createElement('div');
        subBoard.className = 'sub-board';

        const boardAvailable = isBoardPlayable(boardIndex);

        if (boardAvailable && !gameOver) {
          subBoard.classList.add('active');
        } else {
          subBoard.classList.add('locked');
        }

        board.cells.forEach((cell, cellIndex) => {
          const cellElement = document.createElement('div');
          cellElement.className = 'cell';

          if (cell) {
            cellElement.textContent = cell;
            cellElement.classList.add(cell.toLowerCase());
          }

          cellElement.addEventListener('click', () => {
            makeMove(boardIndex, cellIndex);
          });

          subBoard.appendChild(cellElement);
        });

        if (board.winner) {
          const overlay = document.createElement('div');
          overlay.className = `sub-board-winner ${board.winner.toLowerCase()}`;
          overlay.textContent = board.winner;
          subBoard.appendChild(overlay);
        }

        gameElement.appendChild(subBoard);
      });

      currentPlayerElement.textContent = currentPlayer;

      if (targetBoard === null) {
        targetBoardText.textContent =
          'Pode jogar em qualquer subtabuleiro disponível.';
      } else {
        targetBoardText.textContent =
          `O próximo jogador deve jogar no subtabuleiro ${targetBoard + 1}.`;
      }
    }

    function isBoardPlayable(index) {
      const board = boards[index];

      if (board.winner || board.draw) {
        return false;
      }

      if (targetBoard === null) {
        return true;
      }

      return index === targetBoard;
    }

    function makeMove(boardIndex, cellIndex) {
      if (gameOver) return;

      const board = boards[boardIndex];

      if (!isBoardPlayable(boardIndex)) {
        return;
      }

      if (board.cells[cellIndex]) {
        return;
      }

      board.cells[cellIndex] = currentPlayer;

      const subWinner = checkWinner(board.cells);

      if (subWinner) {
        board.winner = subWinner;
        mainBoard[boardIndex] = subWinner;
      } else if (board.cells.every(cell => cell !== null)) {
        board.draw = true;
      }

      const globalWinner = checkWinner(mainBoard);

      if (globalWinner) {
        gameOver = true;
        showWinner(globalWinner);
      } else if (
        mainBoard.every((cell, index) => cell || boards[index].draw)
      ) {
        gameOver = true;
        showDraw();
      }

      const nextBoard = boards[cellIndex];

      if (nextBoard.winner || nextBoard.draw) {
        targetBoard = null;
      } else {
        targetBoard = cellIndex;
      }

      if (!gameOver) {
        currentPlayer = currentPlayer === 'X' ? 'O' : 'X';
      }

      render();
    }

    function checkWinner(cells) {
      for (const pattern of WIN_PATTERNS) {
        const [a, b, c] = pattern;

        if (
          cells[a] &&
          cells[a] === cells[b] &&
          cells[a] === cells[c]
        ) {
          return cells[a];
        }
      }

      return null;
    }

    function showWinner(player) {
      winnerText.textContent = `Jogador ${player} venceu o jogo!`;
      winnerOverlay.style.display = 'flex';
    }

    function showDraw() {
      winnerText.textContent = 'Empate!';
      winnerOverlay.style.display = 'flex';
    }

    function restartGame() {
      initializeGame();
    }

    initializeGame();
  </script>
</body>
</html>