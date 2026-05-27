<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Jogo da Velha 4x4</title>

<style>
    *{
        box-sizing:border-box;
        margin:0;
        padding:0;
        font-family: Arial, sans-serif;
    }

    body{
        background:#f2f2f2;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        min-height:100vh;
        padding:20px;
    }

    h1{
        margin-bottom:20px;
        color:#333;
    }

    #info-start{
        margin-bottom:10px;
        font-size:18px;
        font-weight:bold;
        color:#555;
    }

    #status{
        margin-bottom:20px;
        font-size:22px;
        font-weight:bold;
        color:#444;
        text-align:center;
        max-width:500px;
    }

    .board{
        display:grid;
        grid-template-columns:repeat(4, 100px);
        grid-template-rows:repeat(4, 100px);
        gap:8px;
    }

    .cell{
        width:100px;
        height:100px;
        background:white;
        border-radius:12px;
        display:flex;
        justify-content:center;
        align-items:center;
        font-size:48px;
        font-weight:bold;
        cursor:pointer;
        transition:0.2s;
        box-shadow:0 3px 8px rgba(0,0,0,0.15);
        user-select:none;
    }

    .cell:hover{
        background:#ececec;
    }

    .cell.x{
        color:#e63946;
    }

    .cell.o{
        color:#1d4ed8;
    }

    .cell.removable{
        background:#ffe082 !important;
        animation:pulse 1s infinite;
    }

    .cell.blocked{
        background:#ffcccc !important;
        cursor:not-allowed;
    }

    @keyframes pulse{
        0%{ transform:scale(1); }
        50%{ transform:scale(1.05); }
        100%{ transform:scale(1); }
    }

    #restart{
        margin-top:25px;
        padding:12px 24px;
        border:none;
        border-radius:10px;
        background:#333;
        color:white;
        font-size:16px;
        cursor:pointer;
        transition:0.2s;
    }

    #restart:hover{
        background:#111;
    }

    @media(max-width:600px){

        .board{
            grid-template-columns:repeat(4, 70px);
            grid-template-rows:repeat(4, 70px);
        }

        .cell{
            width:70px;
            height:70px;
            font-size:36px;
        }

    }
</style>
</head>

<body>

<h1>Jogo da Velha 4x4</h1>

<div id="info-start"></div>

<div id="status"></div>

<div class="board" id="board"></div>

<button id="restart">Nova Partida</button>

<script>

    const board = document.getElementById("board");
    const statusText = document.getElementById("status");
    const infoStart = document.getElementById("info-start");
    const restartBtn = document.getElementById("restart");

    let startingPlayer = localStorage.getItem("startingPlayer") || "X";

    let currentPlayer = startingPlayer;

    let gameActive = true;

    let removeMode = false;

    let removedIndex = null;

    let cells = Array(16).fill("");

    const MAX_PIECES = 5;

    const winningCombinations = [

        [0,1,2,3],
        [4,5,6,7],
        [8,9,10,11],
        [12,13,14,15],

        [0,4,8,12],
        [1,5,9,13],
        [2,6,10,14],
        [3,7,11,15],

        [0,5,10,15],
        [3,6,9,12]

    ];

    function createBoard(){

        board.innerHTML = "";

        cells.forEach((cell, index) => {

            const cellElement = document.createElement("div");

            cellElement.classList.add("cell");

            if(cell === "X"){
                cellElement.classList.add("x");
            }

            if(cell === "O"){
                cellElement.classList.add("o");
            }

            // Destaca peças removíveis
            if(removeMode && cell === currentPlayer){
                cellElement.classList.add("removable");
            }

            // Bloqueia posição recém removida
            if(
                !removeMode &&
                removedIndex === index &&
                cell === ""
            ){
                cellElement.classList.add("blocked");
            }

            cellElement.dataset.index = index;

            cellElement.textContent = cell;

            cellElement.addEventListener("click", handleCellClick);

            board.appendChild(cellElement);

        });

    }

    function handleCellClick(event){

        if(!gameActive) return;

        const index = Number(event.target.dataset.index);

        // =========================
        // MODO REMOVER PEÇA
        // =========================
        if(removeMode){

            if(cells[index] !== currentPlayer){
                return;
            }

            cells[index] = "";

            removedIndex = index;

            removeMode = false;

            statusText.textContent =
                `Jogador ${currentPlayer}: escolha outra posição vazia`;

            createBoard();

            return;

        }

        // =========================
        // CASA OCUPADA
        // =========================
        if(cells[index] !== ""){
            return;
        }

        // =========================
        // BLOQUEIA MESMA CASA
        // =========================
        if(index === removedIndex){
            return;
        }

        // =========================
        // JOGADA
        // =========================
        cells[index] = currentPlayer;

        removedIndex = null;

        createBoard();

        // =========================
        // VITÓRIA
        // =========================
        if(checkWinner()){

            statusText.textContent =
                `Jogador ${currentPlayer} venceu!`;

            gameActive = false;

            return;

        }

        // =========================
        // TROCA JOGADOR
        // =========================
        switchPlayer();

        // Verifica quantidade do NOVO jogador
        const nextPlayerPieces =
            cells.filter(c => c === currentPlayer).length;

        // Se já possui 5 peças -> deve remover
        if(nextPlayerPieces >= MAX_PIECES){

            removeMode = true;

            statusText.textContent =
                `Jogador ${currentPlayer}: remova uma de suas peças`;

        }else{

            statusText.textContent =
                `Vez do jogador ${currentPlayer}`;

        }

        createBoard();

    }

    function switchPlayer(){

        currentPlayer =
            currentPlayer === "X" ? "O" : "X";

    }

    function checkWinner(){

        return winningCombinations.some(combination => {

            return combination.every(index => {

                return cells[index] === currentPlayer;

            });

        });

    }

    function restartGame(){

        startingPlayer =
            startingPlayer === "X" ? "O" : "X";

        localStorage.setItem(
            "startingPlayer",
            startingPlayer
        );

        currentPlayer = startingPlayer;

        gameActive = true;

        removeMode = false;

        removedIndex = null;

        cells = Array(16).fill("");

        infoStart.textContent =
            `Quem começa esta partida: Jogador ${startingPlayer}`;

        statusText.textContent =
            `Vez do jogador ${currentPlayer}`;

        createBoard();

    }

    restartBtn.addEventListener(
        "click",
        restartGame
    );

    // Inicialização
    infoStart.textContent =
        `Quem começa esta partida: Jogador ${startingPlayer}`;

    statusText.textContent =
        `Vez do jogador ${currentPlayer}`;

    createBoard();

</script>

</body>
</html>