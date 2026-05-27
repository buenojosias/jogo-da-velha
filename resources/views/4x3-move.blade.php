<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jogo da Velha 4x4 - Modo Rotação</title>

<style>
    *{
        box-sizing:border-box;
    }

    body{
        margin:0;
        font-family:Arial,sans-serif;
        background:#1f1f2e;
        color:white;
        display:flex;
        justify-content:center;
        align-items:center;
        min-height:100vh;
    }

    .container{
        text-align:center;
        padding:20px;
    }

    h1{
        margin-bottom:10px;
    }

    .menu{
        margin-bottom:20px;
    }

    button{
        padding:10px 16px;
        border:none;
        border-radius:10px;
        font-size:16px;
        margin:5px;
        background:#4fc3f7;
        color:#111;
        font-weight:bold;
        cursor:pointer;
    }

    button:hover{
        transform:scale(1.03);
    }

    #status{
        margin-bottom:20px;
        font-size:20px;
        font-weight:bold;
        min-height:30px;
    }

    .board{
        display:grid;
        grid-template-columns:repeat(4,90px);
        grid-template-rows:repeat(4,90px);
        gap:8px;
        justify-content:center;
    }

    .cell{
        width:90px;
        height:90px;
        background:#2e2e45;
        border-radius:12px;
        display:flex;
        justify-content:center;
        align-items:center;
        font-size:42px;
        font-weight:bold;
        cursor:pointer;
        user-select:none;
        transition:0.2s;
    }

    .cell:hover{
        background:#404064;
        transform:scale(1.03);
    }

    .x{
        color:#4fc3f7;
    }

    .o{
        color:#ffca28;
    }

    .winner{
        background:#43a047 !important;
        color:white !important;
    }

    .selected-piece{
        background:#ef5350 !important;
        transform:scale(1.08);
        box-shadow:0 0 15px #ef5350;
    }

    .move-target{
        background:#5c6bc0 !important;
    }

    .description{
        max-width:520px;
        margin:0 auto 20px;
        color:#cfcfe0;
        font-size:14px;
        line-height:1.5;
    }
</style>
</head>
<body>

<div class="container">

    <h1>Jogo da Velha 4x4 - Modo Rotação</h1>

    <div class="menu">
        <button onclick="restartGame()">
            Reiniciar
        </button>
    </div>

    <div class="description">
        Cada jogador pode manter apenas 3 peças no tabuleiro.
        Quando precisar jogar uma nova peça, deverá primeiro escolher uma peça própria para mover e depois selecionar a nova posição dela.
    </div>

    <div id="status">
        Vez do jogador X
    </div>

    <div class="board" id="board"></div>

</div>

<script>

const boardElement = document.getElementById('board');
const statusElement = document.getElementById('status');

let board = Array(16).fill('');

let startingPlayer =
    localStorage.getItem('startingPlayer') || 'X';

let currentPlayer = startingPlayer;

let gameOver = false;

let playerMoves = {
    X: [],
    O: []
};

let selectedPiece = null;

const winningCombos = [

    // Horizontais
    [0,1,2],
    [1,2,3],

    [4,5,6],
    [5,6,7],

    [8,9,10],
    [9,10,11],

    [12,13,14],
    [13,14,15],

    // Verticais
    [0,4,8],
    [4,8,12],

    [1,5,9],
    [5,9,13],

    [2,6,10],
    [6,10,14],

    [3,7,11],
    [7,11,15],

    // Diagonais
    [0,5,10],
    [1,6,11],
    [4,9,14],
    [5,10,15],

    [2,5,8],
    [3,6,9],
    [6,9,12],
    [7,10,13]
];

function createBoard(){

    boardElement.innerHTML = '';

    board.forEach((value,index)=>{

        const cell = document.createElement('div');

        cell.classList.add('cell');

        if(value){
            cell.classList.add(value.toLowerCase());
        }

        // peça selecionada
        if(index === selectedPiece){
            cell.classList.add('selected-piece');
        }

        // possíveis destinos
        if(
            selectedPiece !== null &&
            board[index] === ''
        ){
            cell.classList.add('move-target');
        }

        cell.textContent = value;

        cell.addEventListener(
            'click',
            ()=>handleCellClick(index)
        );

        boardElement.appendChild(cell);
    });
}

function handleCellClick(index){

    if(gameOver){
        return;
    }

    // modo normal
    if(playerMoves[currentPlayer].length < 3){

        if(board[index] !== ''){
            return;
        }

        placePiece(index);

        return;
    }

    // escolher peça para mover
    if(selectedPiece === null){

        if(board[index] !== currentPlayer){
            return;
        }

        selectedPiece = index;

        statusElement.textContent =
            `Jogador ${currentPlayer}: escolha para onde mover`;

        createBoard();

        return;
    }

    // cancelar seleção
    if(index === selectedPiece){

        selectedPiece = null;

        statusElement.textContent =
            `Vez do jogador ${currentPlayer}`;

        createBoard();

        return;
    }

    // mover para casa vazia
    if(board[index] === ''){

        movePiece(selectedPiece,index);

        return;
    }
}

function movePiece(from,to){

    board[from] = '';

    board[to] = currentPlayer;

    const pieceIndex =
        playerMoves[currentPlayer].indexOf(from);

    if(pieceIndex !== -1){
        playerMoves[currentPlayer][pieceIndex] = to;
    }

    selectedPiece = null;

    createBoard();

    checkGameState();
}

function placePiece(index){

    board[index] = currentPlayer;

    playerMoves[currentPlayer].push(index);

    createBoard();

    checkGameState();
}

function checkGameState(){

    const winnerCombo =
        checkWinner(currentPlayer);

    if(winnerCombo){

        gameOver = true;

        highlightWinner(winnerCombo);

        statusElement.textContent =
            `Jogador ${currentPlayer} venceu!`;

        return;
    }

    currentPlayer =
        currentPlayer === 'X' ? 'O' : 'X';

    statusElement.textContent =
        `Vez do jogador ${currentPlayer}`;
}

function checkWinner(player){

    for(const combo of winningCombos){

        const [a,b,c] = combo;

        if(
            board[a] === player &&
            board[b] === player &&
            board[c] === player
        ){
            return combo;
        }
    }

    return null;
}

function highlightWinner(combo){

    const cells =
        document.querySelectorAll('.cell');

    combo.forEach(index=>{
        cells[index].classList.add('winner');
    });
}

function restartGame(){

    // alterna jogador inicial
    startingPlayer =
        startingPlayer === 'X' ? 'O' : 'X';

    localStorage.setItem(
        'startingPlayer',
        startingPlayer
    );

    board = Array(16).fill('');

    currentPlayer = startingPlayer;

    gameOver = false;

    selectedPiece = null;

    playerMoves = {
        X: [],
        O: []
    };

    statusElement.textContent =
        `Vez do jogador ${currentPlayer}`;

    createBoard();
}

createBoard();

</script>

</body>
</html>