const boardElement = document.getElementById("board");
const statusTextElement = document.getElementById("status-text");
const restartBtn = document.getElementById("restartBtn");
const playerXScoreElement = document.getElementById("player-x-score");
const playerOScoreElement = document.getElementById("player-o-score");
const playerXIcon = document.getElementById("player-x-icon");
const playerOIcon = document.getElementById("player-o-icon");
const modalWinElement = document.getElementById("modal-win");
const winnerIconContainer = document.getElementById("winner-icon-container");
const modalRestartBtn = document.getElementById("modal-restart-btn");

const oSVG = (size, color = 'oklch(79.5% 0.184 86.047)') => `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="${color}" viewBox="0 0 256 256"><path d="M128,20A108,108,0,1,0,236,128,108.12,108.12,0,0,0,128,20Zm0,192a84,84,0,1,1,84-84A84.09,84.09,0,0,1,128,212Z"></path></svg>`;
const xSVG = (size, color = 'oklch(68.5% 0.169 237.323)') => `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" fill="${color}" viewBox="0 0 256 256"><path d="M208.49,191.51a12,12,0,0,1-17,17L128,145,64.49,208.49a12,12,0,0,1-17-17L111,128,47.51,64.49a12,12,0,0,1,17-17L128,111l63.51-63.52a12,12,0,0,1,17,17L145,128Z"></path></svg>`;

let board = [];
let currentPlayer = "x";
let gameOver = false;
let score = { x: 0, o: 0 };
let startingPlayer = Math.random() < 0.5 ? "x" : "o";

// let placedPieces = { x: 0, o: 0 };
// let selectedPiece = null;

function getStartingPlayer() {
    startingPlayer = startingPlayer === 'x' ? 'o' : 'x';
}

function toggleStartingPlayer() {
    startingPlayer = startingPlayer === 'x' ? 'o' : 'x';
}

function updateScoreboard() {
    playerXScoreElement.textContent = score.x;
    playerOScoreElement.textContent = score.o;
}

function finishGame(winnerData) {
    gameOver = true;
    
    score[winnerData.player]++;
    updateScoreboard();

    const cells = boardElement.querySelectorAll(".cell");
    winnerData.combo.forEach(index => {
        cells[index].classList.add(`winner-${winnerData.player}`);
    });

    winnerIconContainer.innerHTML = winnerData.player === 'x' ? xSVG(28) : oSVG(28);
    modalWinElement.style.display = 'flex';
}

function checkWinner() {
    for (const combo of winningCombinations) {
        const [a, b, c] = combo;
        if (board[a] && board[a] === board[b] && board[a] === board[c]) {
            return { player: board[a], combo };
        }
    }
    return null;
}