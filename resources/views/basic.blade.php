<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Jogo da Velha</title>

  <style>
    *{
      box-sizing:border-box;
      margin:0;
      padding:0;
    }

    body{
      font-family: Arial, sans-serif;
      background:#111827;
      color:white;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:20px;
    }

    .game{
      width:100%;
      max-width:420px;
      text-align:center;
    }

    h1{
      margin-bottom:20px;
      font-size:2rem;
    }

    .status{
      margin-bottom:20px;
      font-size:1.2rem;
      font-weight:bold;
      min-height:30px;
    }

    .board{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:10px;
      margin-bottom:20px;
    }

    .cell{
      aspect-ratio:1/1;
      background:#1f2937;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:3rem;
      cursor:pointer;
      transition:0.2s;
      user-select:none;
    }

    .cell:hover{
      background:#374151;
      transform:scale(1.03);
    }

    .cell.disabled{
      cursor:default;
    }

    .winner{
      background:#16a34a !important;
      color:white;
    }

    button{
      background:#2563eb;
      color:white;
      border:none;
      padding:12px 18px;
      border-radius:10px;
      cursor:pointer;
      font-size:1rem;
      transition:0.2s;
    }

    button:hover{
      background:#1d4ed8;
    }

    .info{
      margin-top:15px;
      color:#cbd5e1;
      font-size:0.95rem;
      line-height:1.5;
    }
  </style>
</head>
<body>

  <div class="game">
    <h1>Jogo da Velha</h1>

    <div class="status" id="status"></div>

    <div class="board" id="board"></div>

    <button id="restartBtn">Nova Partida</button>

    <div class="info">
      O jogador inicial alterna automaticamente a cada nova partida.
    </div>
  </div>

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

</body>
</html>