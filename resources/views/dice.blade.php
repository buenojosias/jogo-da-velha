<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<title>Jogo da Velha 3.0</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#000;
    color:#fff;
    font-family:Arial, Helvetica, sans-serif;
    overflow:hidden;
}

.game{
    width:100%;
    height:100vh;

    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;

    gap:40px;
}

/* =========================
   TOPO
========================= */

.top{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:25px;
}

.status{
    font-size:28px;
    font-weight:bold;
    text-align:center;
}

.controls{
    display:flex;
    align-items:center;
    gap:20px;
}

button{
    border:none;
    border-radius:12px;

    padding:14px 28px;

    font-size:18px;
    font-weight:bold;

    cursor:pointer;

    transition:0.2s;
}

button:hover{
    transform:scale(1.05);
}

#rollBtn{
    background:#1976d2;
    color:white;
}

#restartBtn{
    background:#e53935;
    color:white;
}

/* =========================
   DADO 3D
========================= */

.scene{
    width:110px;
    height:110px;
    perspective:800px;

    pointer-events:none;
}

.dice{
    width:100%;
    height:100%;

    position:relative;

    transform-style:preserve-3d;

    transition:1s;
}

.face{
    position:absolute;

    width:110px;
    height:110px;

    background:white;

    border:2px solid #222;
    border-radius:16px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:42px;
    font-weight:bold;

    color:black;
}

.front{
    transform:rotateY(0deg) translateZ(55px);
}

.back{
    transform:rotateY(180deg) translateZ(55px);
}

.right{
    transform:rotateY(90deg) translateZ(55px);
}

.left{
    transform:rotateY(-90deg) translateZ(55px);
}

.topf{
    transform:rotateX(90deg) translateZ(55px);
}

.bottom{
    transform:rotateX(-90deg) translateZ(55px);
}

/* =========================
   TABULEIROS
========================= */

.boards{
    display:flex;
    align-items:center;
    gap:80px;
}

.divider{
    width:4px;
    height:420px;
    background:#aaa;
}

.board{
    display:grid;

    grid-template-columns:repeat(3, 120px);
    grid-template-rows:repeat(3, 120px);

    gap:10px;
}

.cell{
    width:120px;
    height:120px;

    border:3px solid #999;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:72px;

    cursor:pointer;

    position:relative;

    transition:0.2s;

    user-select:none;
}

.cell:hover{
    background:rgba(255,255,255,0.05);
}

/* PREVIEW */

.cell.preview-x::after{
    content:'X';
    position:absolute;
    color:rgba(255,255,255,0.18);
}

.cell.preview-o::after{
    content:'O';
    position:absolute;
    color:rgba(255,255,255,0.18);
}

/* VITÓRIA */

.win{
    background:#00c853 !important;
    color:black;
    box-shadow:0 0 20px #00ff88;
}

</style>
</head>

<body>

<div class="game">

    <div class="top">

        <div class="status" id="status">
            <div class="status" id="status"></div>
        </div>

        <div class="controls">

            <button id="rollBtn">
                🎲 Rolar Dado
            </button>

            <button id="restartBtn">
                🔄 Recomeçar
            </button>

            <div class="scene">

                <div class="dice" id="dice">

                    <div class="face front">1</div>
                    <div class="face back">6</div>
                    <div class="face right">3</div>
                    <div class="face left">4</div>
                    <div class="face topf">2</div>
                    <div class="face bottom">5</div>

                </div>

            </div>

        </div>

    </div>

    <div class="boards">

        <div class="board" id="board1"></div>

        <div class="divider"></div>

        <div class="board" id="board2"></div>

    </div>

</div>

<script>

/* ====================================
   ELEMENTOS
==================================== */

const boards = [
    document.getElementById('board1'),
    document.getElementById('board2')
];

const dice = document.getElementById('dice');

const rollBtn =
    document.getElementById('rollBtn');

const restartBtn =
    document.getElementById('restartBtn');

const statusText =
    document.getElementById('status');

/* ====================================
   ESTADO
==================================== */

let starterPlayer =
    localStorage.getItem('starterPlayer');

if(!starterPlayer){

    starterPlayer =
        Math.random() < 0.5
            ? 'X'
            : 'O';

    localStorage.setItem(
        'starterPlayer',
        starterPlayer
    );

}

let currentPlayer = starterPlayer;

let currentColumn = null;

let gameOver = false;

const boardState = [
    Array(9).fill(''),
    Array(9).fill('')
];

/* ====================================
   CRIAR TABULEIROS
==================================== */

statusText.innerHTML =
    `Jogador ${currentPlayer} - role o dado`;

function createBoards(){

    boards.forEach((board, boardIndex)=>{

        board.innerHTML = '';

        for(let i=0;i<9;i++){

            const cell =
                document.createElement('div');

            cell.classList.add('cell');

            cell.dataset.index = i;

            /* =======================
               PREVIEW
            ======================== */

            cell.addEventListener(
                'mouseenter',
                ()=>{

                    if(gameOver) return;

                    if(currentColumn === null)
                        return;

                    const localColumn =
                        (i % 3) + 1;

                    const globalColumn =
                        boardIndex === 0
                            ? localColumn
                            : localColumn + 3;

                    if(globalColumn !== currentColumn)
                        return;

                    if(boardState[boardIndex][i] !== '')
                        return;

                    cell.classList.add(
                        currentPlayer === 'X'
                            ? 'preview-x'
                            : 'preview-o'
                    );

                }
            );

            cell.addEventListener(
                'mouseleave',
                ()=>{

                    cell.classList.remove(
                        'preview-x'
                    );

                    cell.classList.remove(
                        'preview-o'
                    );

                }
            );

            /* =======================
               CLICK
            ======================== */

            cell.addEventListener(
                'click',
                ()=>{

                    if(gameOver) return;

                    if(currentColumn === null){

                        alert(
                            'Role o dado primeiro!'
                        );

                        return;
                    }

                    const localColumn =
                        (i % 3) + 1;

                    /*
                       COLUNA GLOBAL
                       TAB 1 -> 1,2,3
                       TAB 2 -> 4,5,6
                    */

                    const globalColumn =
                        boardIndex === 0
                            ? localColumn
                            : localColumn + 3;

                    if(globalColumn !== currentColumn){

                        alert(
                            'Você só pode jogar na coluna '
                            + currentColumn
                        );

                        return;
                    }

                    if(boardState[boardIndex][i] !== '')
                        return;

                    boardState[boardIndex][i] =
                        currentPlayer;

                    cell.textContent =
                        currentPlayer;

                    checkWinner(boardIndex);

                    if(gameOver) return;

                    checkDraw();

                    if(gameOver) return;

                    currentPlayer =
                        currentPlayer === 'X'
                            ? 'O'
                            : 'X';

                    currentColumn = null;

                    statusText.innerHTML =
                        `Jogador ${currentPlayer} - role o dado`;

                }
            );

            board.appendChild(cell);

        }

    });

}

/* ====================================
   ROLAR DADO
==================================== */

function rotateDice(number){

    const rotations = {

        1:'rotateX(0deg) rotateY(0deg)',

        2:'rotateX(-90deg) rotateY(0deg)',

        3:'rotateX(0deg) rotateY(-90deg)',

        4:'rotateX(0deg) rotateY(90deg)',

        5:'rotateX(90deg) rotateY(0deg)',

        6:'rotateX(0deg) rotateY(180deg)'

    };

    const randomX =
        Math.floor(Math.random()*6 + 4)
        * 360;

    const randomY =
        Math.floor(Math.random()*6 + 4)
        * 360;

    dice.style.transform =
        `rotateX(${randomX}deg)
         rotateY(${randomY}deg)`;

    setTimeout(()=>{

        dice.style.transform =
            rotations[number];

    },1000);

}

/* ====================================
   VERIFICAR COLUNA
==================================== */

function columnHasFreeCell(column){

    let boardIndex;
    let localColumn;

    /*
       1,2,3 -> tabuleiro esquerdo
       4,5,6 -> tabuleiro direito
    */

    if(column <= 3){

        boardIndex = 0;

        localColumn = column - 1;

    }else{

        boardIndex = 1;

        localColumn = column - 4;

    }

    for(let row=0; row<3; row++){

        const index =
            row * 3 + localColumn;

        if(
            boardState[boardIndex][index]
            === ''
        ){
            return true;
        }

    }

    return false;

}

/* ====================================
   VITÓRIA
==================================== */

function checkWinner(boardIndex){

    const b = boardState[boardIndex];

    const wins = [

        [0,1,2],
        [3,4,5],
        [6,7,8],

        [0,3,6],
        [1,4,7],
        [2,5,8],

        [0,4,8],
        [2,4,6]

    ];

    for(let combo of wins){

        const [a,b1,c] = combo;

        if(
            b[a] &&
            b[a] === b[b1] &&
            b[a] === b[c]
        ){

            gameOver = true;

            highlightWin(
                boardIndex,
                combo
            );

            statusText.innerHTML =
                `🏆 Jogador ${currentPlayer} venceu!`;

            rollBtn.disabled = true;

            return;
        }

    }

}

/* ====================================
   EMPATE
==================================== */

function checkDraw(){

    const allFilled =
        boardState[0].every(c => c !== '')
        &&
        boardState[1].every(c => c !== '');

    if(allFilled){

        gameOver = true;

        statusText.innerHTML =
            '🤝 Deu velha nos dois tabuleiros!';

        rollBtn.disabled = true;

    }

}

/* ====================================
   DESTACAR VITÓRIA
==================================== */

function highlightWin(boardIndex, combo){

    const cells =
        boards[boardIndex]
            .querySelectorAll('.cell');

    combo.forEach(index=>{

        cells[index]
            .classList
            .add('win');

    });

}

/* ====================================
   RECOMEÇAR
==================================== */

function restartGame(){

    /*
       Alterna quem começa
    */

    starterPlayer =
        starterPlayer === 'X'
            ? 'O'
            : 'X';

    localStorage.setItem(
        'starterPlayer',
        starterPlayer
    );

    boardState[0] =
        Array(9).fill('');

    boardState[1] =
        Array(9).fill('');

    currentPlayer = starterPlayer;

    currentColumn = null;

    gameOver = false;

    rollBtn.disabled = false;

    statusText.innerHTML =
        `Jogador ${currentPlayer} - role o dado`;

    createBoards();
}

/* ====================================
   BOTÃO DADO
==================================== */

rollBtn.addEventListener(
    'click',
    ()=>{

        if(gameOver) return;

        if(currentColumn !== null){

            alert(
                'Faça sua jogada primeiro!'
            );

            return;
        }

        const number =
            Math.floor(Math.random()*6) + 1;

        rotateDice(number);

        setTimeout(()=>{

            currentColumn = number;

            if(
                !columnHasFreeCell(number)
            ){

                statusText.innerHTML =
                    `Coluna ${number} cheia! Jogador ${currentPlayer} perdeu a vez.`;

                currentPlayer =
                    currentPlayer === 'X'
                        ? 'O'
                        : 'X';

                currentColumn = null;

                setTimeout(()=>{

                    statusText.innerHTML =
                        `Jogador ${currentPlayer} - role o dado`;

                },1800);

                return;
            }

            statusText.innerHTML =
                `Jogador ${currentPlayer}: jogue na coluna ${number}`;

        },1100);

    }
);

/* ====================================
   BOTÃO RECOMEÇAR
==================================== */

restartBtn.addEventListener(
    'click',
    restartGame
);

/* ====================================
   INICIAR
==================================== */

createBoards();

</script>

</body>
</html>