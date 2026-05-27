<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Jogo da Velha - Peça Mais Antiga Some</title>

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
        position:relative;
    }

    .cell:hover{
        background:#3d3d3d;
    }

    .winner{
        background:#2e7d32 !important;
        border-color:#81c784;
    }

    .oldest{
        outline:4px dashed #ffd54f;
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
        max-width:500px;
        line-height:1.5;
    }
</style>
</head>
<body>

<div class="container">
    <h1>Jogo da Velha - Peça Mais Antiga</h1>

    <div id="status"></div>

    <div class="board" id="board"></div>

    <button onclick="resetGame()">Nova Partida</button>

    <div class="info">
        Cada jogador pode manter apenas 3 peças no tabuleiro.<br><br>

        Ao colocar a 4ª peça, a peça mais antiga daquele jogador desaparece automaticamente.<br><br>

        O quadrado destacado com borda amarela mostra qual peça será removida na próxima jogada daquele jogador.
    </div>
</div>

<script>
const boardElement = document.getElementById("board");
const statusElement = document.getElementById("status");

let board = Array(9).fill("");
let currentPlayer = localStorage.getItem("nextStarterAging") || "X";
let gameOver = false;

// Guarda a ordem das peças
let playerPieces = {
    X: [],
    O: []
};

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

    render();
}

function updateStatus(text=""){

    if(text){
        statusElement.textContent = text;
        return;
    }

    if(playerPieces[currentPlayer].length < 3){
        statusElement.textContent =
            `Vez do jogador ${currentPlayer} — coloque uma peça`;
    }else{
        statusElement.textContent =
            `Vez do jogador ${currentPlayer} — ao jogar, sua peça mais antiga desaparecerá`;
    }
}

function handleClick(index){

    if(gameOver) return;

    if(board[index] !== "") return;

    // Se já possui 3 peças, remove a mais antiga
    if(playerPieces[currentPlayer].length >= 3){

        const oldestIndex = playerPieces[currentPlayer].shift();

        board[oldestIndex] = "";
    }

    // Adiciona nova peça
    board[index] = currentPlayer;
    playerPieces[currentPlayer].push(index);

    render();

    if(checkWinner()){
        finishGame();
        return;
    }

    switchPlayer();
}

function switchPlayer(){

    currentPlayer = currentPlayer === "X" ? "O" : "X";

    updateStatus();
}

function render(){

    document.querySelectorAll(".cell").forEach((cell, index)=>{

        cell.textContent = board[index];

        cell.classList.remove("winner");
        cell.classList.remove("oldest");
    });

    // Marca peça mais antiga
    ["X", "O"].forEach(player=>{

        if(playerPieces[player].length >= 3){

            const oldest = playerPieces[player][0];

            const el = document.querySelectorAll(".cell")[oldest];

            if(el){
                el.classList.add("oldest");
            }
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

    // Alterna quem começa
    const nextStarter =
        currentPlayer === "X" ? "O" : "X";

    localStorage.setItem(
        "nextStarterAging",
        nextStarter
    );
}

function resetGame(){

    board = Array(9).fill("");

    currentPlayer =
        localStorage.getItem("nextStarterAging") || "X";

    gameOver = false;

    playerPieces = {
        X: [],
        O: []
    };

    createBoard();
}

createBoard();
</script>

</body>
</html>