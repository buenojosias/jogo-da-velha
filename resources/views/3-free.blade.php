<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Jogo da Velha - 3 Peças</title>

<style>
    *{
        box-sizing:border-box;
        font-family: Arial, sans-serif;
    }

    body{
        margin:0;
        min-height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        background:#1e1e1e;
        color:white;
    }

    .container{
        text-align:center;
    }

    h1{
        margin-bottom:10px;
    }

    #status{
        margin-bottom:20px;
        font-size:18px;
        min-height:24px;
    }

    .board{
        display:grid;
        grid-template-columns:repeat(3, 100px);
        grid-template-rows:repeat(3, 100px);
        gap:8px;
        justify-content:center;
        margin:auto;
    }

    .cell{
        background:#2f2f2f;
        border:2px solid #555;
        display:flex;
        justify-content:center;
        align-items:center;
        font-size:42px;
        font-weight:bold;
        cursor:pointer;
        transition:0.2s;
    }

    .cell:hover{
        background:#3d3d3d;
    }

    .selected{
        outline:4px solid #ffd54f;
    }

    .winner{
        background:#2e7d32 !important;
        border-color:#81c784;
    }

    button{
        margin-top:20px;
        padding:10px 18px;
        border:none;
        border-radius:8px;
        background:#1976d2;
        color:white;
        cursor:pointer;
        font-size:16px;
    }

    button:hover{
        background:#1565c0;
    }

    .info{
        margin-top:15px;
        color:#ccc;
        font-size:14px;
    }
</style>
</head>
<body>

<div class="container">
    <h1>Jogo da Velha - 3 Peças</h1>

    <div id="status"></div>

    <div class="board" id="board"></div>

    <button onclick="resetGame()">Nova Partida</button>

    <div class="info">
        Cada jogador possui apenas 3 peças.<br>
        Depois de colocar todas, será possível mover uma peça por vez.
    </div>
</div>

<script>
const boardElement = document.getElementById("board");
const statusElement = document.getElementById("status");

let board = Array(9).fill("");
let currentPlayer = localStorage.getItem("nextStarter") || "X";

let placedPieces = {
    X: 0,
    O: 0
};

let selectedPiece = null;
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

function createBoard(){
    boardElement.innerHTML = "";

    board.forEach((cell, index)=>{
        const div = document.createElement("div");
        div.classList.add("cell");
        div.dataset.index = index;
        div.textContent = cell;

        div.addEventListener("click", ()=>handleClick(index));

        boardElement.appendChild(div);
    });

    updateStatus();
}

function updateStatus(text = ""){
    if(text){
        statusElement.textContent = text;
    } else {
        if(placedPieces[currentPlayer] < 3){
            statusElement.textContent =
                `Vez do jogador ${currentPlayer} — coloque uma peça`;
        } else {
            statusElement.textContent =
                `Vez do jogador ${currentPlayer} — mova uma peça`;
        }
    }
}

function handleClick(index){
    if(gameOver) return;

    // FASE DE COLOCAR PEÇAS
    if(placedPieces[currentPlayer] < 3){

        if(board[index] !== "") return;

        board[index] = currentPlayer;
        placedPieces[currentPlayer]++;

        render();

        if(checkWinner()){
            finishGame();
            return;
        }

        switchPlayer();
        return;
    }

    // FASE DE MOVIMENTAÇÃO
    if(selectedPiece === null){

        // Selecionar peça própria
        if(board[index] === currentPlayer){
            selectedPiece = index;
            render();
        }

    } else {

        // Cancelar seleção
        if(index === selectedPiece){
            selectedPiece = null;
            render();
            return;
        }

        // Trocar seleção
        if(board[index] === currentPlayer){
            selectedPiece = index;
            render();
            return;
        }

        // Mover para espaço vazio
        if(board[index] === ""){

            board[index] = currentPlayer;
            board[selectedPiece] = "";

            selectedPiece = null;

            render();

            if(checkWinner()){
                finishGame();
                return;
            }

            switchPlayer();
        }
    }
}

function switchPlayer(){
    currentPlayer = currentPlayer === "X" ? "O" : "X";
    updateStatus();
}

function render(){

    document.querySelectorAll(".cell").forEach((cell, index)=>{
        cell.textContent = board[index];

        cell.classList.remove("selected");
        cell.classList.remove("winner");

        if(index === selectedPiece){
            cell.classList.add("selected");
        }
    });
}

function checkWinner(){

    for(const combo of winningCombinations){

        const [a,b,c] = combo;

        if(
            board[a] &&
            board[a] === board[b] &&
            board[a] === board[c]
        ){

            document.querySelectorAll(".cell")[a].classList.add("winner");
            document.querySelectorAll(".cell")[b].classList.add("winner");
            document.querySelectorAll(".cell")[c].classList.add("winner");

            return true;
        }
    }

    return false;
}

function finishGame(){

    gameOver = true;

    updateStatus(`Jogador ${currentPlayer} venceu!`);

    // Alterna quem começa a próxima partida
    const nextStarter = currentPlayer === "X" ? "O" : "X";
    localStorage.setItem("nextStarter", nextStarter);
}

function resetGame(){

    const starter = localStorage.getItem("nextStarter") || "X";

    board = Array(9).fill("");
    currentPlayer = starter;

    placedPieces = {
        X: 0,
        O: 0
    };

    selectedPiece = null;
    gameOver = false;

    createBoard();
}

createBoard();
</script>

</body>
</html>