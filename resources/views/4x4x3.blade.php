<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jogo da Velha 4x4x3</title>

<style>
    *{
        box-sizing:border-box;
    }

    body{
        margin:0;
        font-family:Arial, sans-serif;
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

    select, button{
        padding:10px 16px;
        border:none;
        border-radius:10px;
        font-size:16px;
        margin:5px;
    }

    select{
        background:white;
    }

    button{
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
    }

    .board{
        display:grid;
        grid-template-columns:repeat(4, 90px);
        grid-template-rows:repeat(4, 90px);
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

    .old-piece{
        opacity:0.4;
    }

    .description{
        max-width:500px;
        margin:0 auto 20px;
        color:#cfcfe0;
        font-size:14px;
        line-height:1.4;
    }
</style>
</head>
<body>

<div class="container">

    <h1>Jogo da Velha 4x4x3</h1>

    <div class="menu">
        <select id="mode">
            <option value="normal">Modo Normal</option>
            <option value="blocked">
                Modo Bloqueio
            </option>
            <option value="rotation">
                Modo Rotação
            </option>
        </select>

        <button onclick="restartGame()">
            Reiniciar
        </button>
    </div>

    <div class="description" id="description">
        Vitória com 3 peças seguidas.
    </div>

    <div id="status">
        Vez do jogador X
    </div>

    <div class="board" id="board"></div>

</div>

<script>

const boardElement = document.getElementById('board');
const statusElement = document.getElementById('status');
const modeElement = document.getElementById('mode');
const descriptionElement = document.getElementById('description');

let board = Array(16).fill('');

let startingPlayer =
    localStorage.getItem('startingPlayer') || 'X';

    let currentPlayer = startingPlayer;

let gameOver = false;

let playerMoves = {
    X: [],
    O: []
};

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

        // destaque da peça mais antiga
        if(modeElement.value === 'rotation'){

            if(playerMoves.X[0] === index ||
               playerMoves.O[0] === index){

                cell.classList.add('old-piece');
            }
        }

        cell.textContent = value;

        cell.addEventListener('click',()=>makeMove(index));

        boardElement.appendChild(cell);
    });
}

function makeMove(index){

    if(board[index] || gameOver){
        return;
    }

    const mode = modeElement.value;

    board[index] = currentPlayer;

    playerMoves[currentPlayer].push(index);

    // MODO ROTAÇÃO
    if(mode === 'rotation'){

        if(playerMoves[currentPlayer].length > 3){

            const oldest =
                playerMoves[currentPlayer].shift();

            board[oldest] = '';
        }
    }

    createBoard();

    const winnerCombo =
        checkWinner(currentPlayer);

    if(winnerCombo){

        gameOver = true;

        highlightWinner(winnerCombo);

        statusElement.textContent =
            `Jogador ${currentPlayer} venceu!`;

        return;
    }

    if(board.every(cell => cell !== '')){

        gameOver = true;

        statusElement.textContent =
            'Empate!';

        return;
    }

    currentPlayer =
        currentPlayer === 'X' ? 'O' : 'X';

    statusElement.textContent =
        `Vez do jogador ${currentPlayer}`;
}

function checkWinner(player){

    const mode = modeElement.value;

    for(const combo of winningCombos){

        const [a,b,c] = combo;

        if(
            board[a] === player &&
            board[b] === player &&
            board[c] === player
        ){

            // MODO BLOQUEIO
            if(mode === 'blocked'){

                if(isBlocked(combo, player)){
                    continue;
                }
            }

            return combo;
        }
    }

    return null;
}

function isBlocked(combo, player){

    const opponent =
        player === 'X' ? 'O' : 'X';

    const [a,b,c] = combo;

    const diff1 = b - a;
    const diff2 = c - b;

    if(diff1 !== diff2){
        return false;
    }

    const direction = diff1;

    const before = a - direction;
    const after = c + direction;

    if(before >= 0 &&
       before < 16 &&
       board[before] === opponent){
        return true;
    }

    if(after >= 0 &&
       after < 16 &&
       board[after] === opponent){
        return true;
    }

    return false;
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

    playerMoves = {
        X: [],
        O: []
    };

    statusElement.textContent =
        `Vez do jogador ${currentPlayer}`;

    updateDescription();

    createBoard();
}

function updateDescription(){

    const mode = modeElement.value;

    if(mode === 'normal'){

        descriptionElement.textContent =
            'Vitória com 3 peças seguidas.';
    }

    if(mode === 'blocked'){

        descriptionElement.textContent =
            'Sequências de 3 podem ser bloqueadas pelo adversário nas extremidades.';
    }

    if(mode === 'rotation'){

        descriptionElement.textContent =
            'Cada jogador pode manter apenas 4 peças. A mais antiga desaparece.';
    }
}

modeElement.addEventListener('change',restartGame);

createBoard();

</script>

</body>
</html>