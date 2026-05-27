<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jogo da Velha Maluca</title>

<style>

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:Arial, sans-serif;
}

body{
    background:#1b1b1b;
    color:white;
    display:flex;
    flex-direction:column;
    align-items:center;
    min-height:100vh;
    padding:20px;
}

h1{
    margin-bottom:10px;
}

#status{
    margin:15px 0;
    font-size:20px;
    text-align:center;
    min-height:30px;
}

.game{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:20px;
}

.board{
    display:grid;
    grid-template-columns:repeat(3,120px);
    grid-template-rows:repeat(3,120px);
    gap:8px;
}

.cell{
    background:#2b2b2b;
    border:2px solid #444;
    border-radius:12px;
    position:relative;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}

.cell:hover{
    background:#353535;
}

.piece{
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:bold;
    position:absolute;
}

.small{
    width:35px;
    height:35px;
    font-size:14px;
}

.medium{
    width:65px;
    height:65px;
    font-size:18px;
}

.large{
    width:95px;
    height:95px;
    font-size:22px;
}

.X{
    background:#e74c3c;
}

.O{
    background:#3498db;
}

.controls{
    display:flex;
    gap:30px;
    flex-wrap:wrap;
    justify-content:center;
}

.player-box{
    background:#2b2b2b;
    padding:15px;
    border-radius:12px;
    min-width:300px;
}

.player-box h2{
    margin-bottom:15px;
    text-align:center;
}

.pieces{
    display:grid;
    grid-template-columns:repeat(3, 80px);
    gap:20px;
    justify-content:center;
    padding:10px;
    min-height:320px;
}

.select-piece{
    cursor:pointer;
    opacity:0.5;
    transition:0.2s;
    border:3px solid transparent;
    position:relative !important;
    margin:auto;
}

.select-piece.small{
    width:35px;
    height:35px;
}

.select-piece.medium{
    width:65px;
    height:65px;
}

.select-piece.large{
    width:95px;
    height:95px;
}

.select-piece.active{
    opacity:1;
    border-color:yellow;
    transform:scale(1.1);
}

.select-piece.used{
    opacity:0.15;
    cursor:not-allowed;
}

button{
    padding:12px 20px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-size:16px;
    background:#27ae60;
    color:white;
    margin-top:10px;
}

button:hover{
    background:#2ecc71;
}

.winner{
    box-shadow:0 0 20px gold;
    border-color:gold;
}

</style>
</head>

<body>

<h1>Jogo da Velha Maluca</h1>

<div id="status"></div>

<div class="game">

    <div class="board" id="board"></div>

    <div class="controls">

        <div class="player-box">
            <h2>Jogador X</h2>
            <div class="pieces" id="piecesX"></div>
        </div>

        <div class="player-box">
            <h2>Jogador O</h2>
            <div class="pieces" id="piecesO"></div>
        </div>

    </div>

    <button onclick="restart()">Reiniciar</button>

</div>

<script>

const boardElement = document.getElementById('board');
const statusElement = document.getElementById('status');

const sizes = [
    'small','small','small',
    'medium','medium','medium',
    'large','large','large'
];

let board = [];
let currentPlayer = 'X';
let selectedPiece = null;
let gameOver = false;

let players = {
    X: [],
    O: []
};

let starterPlayer =
    localStorage.getItem('crazy_tictactoe_starter');

if(!starterPlayer){

    starterPlayer =
        Math.random() < 0.5 ? 'X' : 'O';

    localStorage.setItem(
        'crazy_tictactoe_starter',
        starterPlayer
    );
}

function init(){

    board = Array(9).fill(null).map(() => []);

    currentPlayer = starterPlayer;

    selectedPiece = null;

    gameOver = false;

    players.X = createPieces('X');
    players.O = createPieces('O');

    renderBoard();
    renderPieces();

    updateStatus();

}

function createPieces(player){

    return sizes.map((size, index) => ({
        id:index,
        size,
        used:false,
        player
    }));

}

function renderBoard(){

    boardElement.innerHTML = '';

    for(let i=0;i<9;i++){

        const cell = document.createElement('div');
        cell.className = 'cell';

        cell.onclick = () => placePiece(i);

        const stack = board[i];

        stack.forEach(piece => {

            const el = document.createElement('div');

            el.className = `piece ${piece.size} ${piece.player}`;

            el.innerText = piece.player;

            cell.appendChild(el);

        });

        boardElement.appendChild(cell);
    }

}

function renderPieces(){

    ['X','O'].forEach(player => {

        const container =
            document.getElementById('pieces'+player);

        container.innerHTML = '';

        players[player].forEach(piece => {

            const el = document.createElement('div');

            el.className =
                `piece ${piece.size} ${piece.player} select-piece`;

            el.innerText = piece.player;

            if(piece.used){
                el.classList.add('used');
            }

            if(selectedPiece === piece){
                el.classList.add('active');
            }

            el.onclick = () => selectPiece(piece);

            container.appendChild(el);

        });

    });

}

function selectPiece(piece){

    if(gameOver) return;

    if(piece.player !== currentPlayer) return;

    if(piece.used) return;

    selectedPiece = piece;

    renderPieces();
}

function sizeValue(size){

    if(size === 'small') return 1;
    if(size === 'medium') return 2;

    return 3;
}

function placePiece(index){

    if(gameOver) return;

    if(!selectedPiece){

        alert('Selecione uma peça.');

        return;
    }

    const stack = board[index];

    const topPiece = stack[stack.length - 1];

    if(topPiece){

        const topSize = sizeValue(topPiece.size);

        const newSize = sizeValue(selectedPiece.size);

        if(newSize <= topSize){

            alert('Você só pode cobrir com uma peça maior.');

            return;
        }

    }

    stack.push({
        player:selectedPiece.player,
        size:selectedPiece.size
    });

    selectedPiece.used = true;

    selectedPiece = null;

    renderBoard();
    renderPieces();

    checkGame();
}

function visibleOwner(index){

    const stack = board[index];

    if(stack.length === 0) return null;

    return stack[stack.length - 1].player;
}

function checkGame(){

    const combos = [
        [0,1,2],
        [3,4,5],
        [6,7,8],
        [0,3,6],
        [1,4,7],
        [2,5,8],
        [0,4,8],
        [2,4,6]
    ];

    let winnerData = null;

    for(const combo of combos){

        const [a,b,c] = combo;

        const va = visibleOwner(a);
        const vb = visibleOwner(b);
        const vc = visibleOwner(c);

        if(va && va === vb && vb === vc){

            winnerData = {
                player:va,
                combo
            };
        }

    }

    if(winnerData){

        const opponent =
            winnerData.player === 'X' ? 'O' : 'X';

        let canCover = false;

        for(const cellIndex of winnerData.combo){

            const topPiece =
                board[cellIndex][board[cellIndex].length - 1];

            const topSize =
                sizeValue(topPiece.size);

            const available =
                players[opponent].some(piece => {

                    return !piece.used &&
                        sizeValue(piece.size) > topSize;

                });

            if(available){
                canCover = true;
            }

        }

        if(!canCover){

            gameOver = true;

            highlightWinner(winnerData.combo);

            statusElement.innerHTML =
                `🎉 Jogador ${winnerData.player} venceu!`;

            return;
        }

    }

    const allUsed =
        players.X.every(p => p.used) &&
        players.O.every(p => p.used);

    if(allUsed){

        gameOver = true;

        statusElement.innerHTML =
            '🤝 Deu velha!';

        return;
    }

    currentPlayer =
        currentPlayer === 'X' ? 'O' : 'X';

    updateStatus();
}

function highlightWinner(combo){

    const cells =
        document.querySelectorAll('.cell');

    combo.forEach(index => {

        cells[index].classList.add('winner');

    });

}

function updateStatus(){
    statusElement.innerHTML =
        `🎮 Jogador inicial: ${starterPlayer}<br>
         👉 Vez do jogador ${currentPlayer}`;
}

function restart(){

    starterPlayer =
        starterPlayer === 'X' ? 'O' : 'X';

    localStorage.setItem(
        'crazy_tictactoe_starter',
        starterPlayer
    );

    init();

}

init();

</script>

</body>
</html>