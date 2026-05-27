<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Jogo da Velha 5x5</title>

<style>
    *{
        box-sizing:border-box;
        font-family: Arial, sans-serif;
    }

    body{
        margin:0;
        background:#1e1e2f;
        color:white;
        display:flex;
        flex-direction:column;
        align-items:center;
        padding:20px;
    }

    h1{
        margin-bottom:10px;
    }

    .info{
        display:flex;
        gap:20px;
        margin-bottom:20px;
        flex-wrap:wrap;
        justify-content:center;
    }

    .card{
        background:#2d2d44;
        padding:12px 18px;
        border-radius:10px;
        min-width:180px;
        text-align:center;
        box-shadow:0 0 10px rgba(0,0,0,0.3);
    }

    #board{
        display:grid;
        grid-template-columns:repeat(5, 90px);
        gap:8px;
        margin-bottom:20px;
    }

    .cell{
        width:90px;
        height:90px;
        background:#2d2d44;
        border-radius:12px;
        display:flex;
        justify-content:center;
        align-items:center;
        font-size:42px;
        font-weight:bold;
        cursor:pointer;
        transition:0.2s;
        user-select:none;
        position:relative;
    }

    .cell:hover{
        background:#3c3c5c;
        transform:scale(1.03);
    }

    .cell.disabled{
        cursor:default;
    }

    .x{
        color:#4fc3f7;
    }

    .o{
        color:#ffb74d;
    }

    .highlight{
        animation: flash 0.6s ease;
    }

    @keyframes flash{

        0%{
            transform:scale(1);
            box-shadow:0 0 0px transparent;
        }

        50%{
            transform:scale(1.12);
            background:#6ee7ff;
            box-shadow:0 0 20px #6ee7ff;
            color:#111;
        }

        100%{
            transform:scale(1);
        }
    }

    button{
        padding:12px 20px;
        border:none;
        border-radius:10px;
        background:#4fc3f7;
        color:#111;
        font-weight:bold;
        cursor:pointer;
        transition:0.2s;
    }

    button:hover{
        transform:scale(1.05);
    }

    #message{
        margin-top:10px;
        font-size:20px;
        font-weight:bold;
    }

    .rules{
        margin-top:25px;
        max-width:700px;
        background:#2d2d44;
        padding:18px;
        border-radius:12px;
        line-height:1.6;
    }

    .highlight-text{
        color:#4fc3f7;
        font-weight:bold;
    }
</style>
</head>
<body>

<h1>Jogo da Velha 5x5</h1>

<div class="info">

    <div class="card">
        <div>Vez do jogador</div>
        <h2 id="turn"></h2>
    </div>

    <div class="card">
        <div>Pontuação X</div>
        <h2 id="scoreX">0</h2>
    </div>

    <div class="card">
        <div>Pontuação O</div>
        <h2 id="scoreO">0</h2>
    </div>

</div>

<div id="board"></div>

<button onclick="restartGame()">Reiniciar</button>

<div id="message"></div>

<div class="rules">

    <h3>Regras</h3>

    <p>
        • O tabuleiro possui
        <span class="highlight-text">5 linhas e 5 colunas</span>.
    </p>

    <p>
        • Os jogadores alternam jogadas marcando
        <span class="highlight-text">X</span> e
        <span class="highlight-text">O</span>.
    </p>

    <p>
        • Sequências valem pontos:
    </p>

    <p>
        ✔ 3 em sequência =
        <span class="highlight-text">3 pontos</span><br>

        ✔ 4 em sequência =
        <span class="highlight-text">6 pontos</span><br>

        ✔ 5 em sequência =
        <span class="highlight-text">10 pontos</span>
    </p>

    <p>
        • As sequências podem ser:
        horizontais, verticais ou diagonais.
    </p>

    <p>
        • Ao final do tabuleiro,
        quem tiver mais pontos vence.
    </p>

</div>

<script>

    const boardElement = document.getElementById("board");
    const turnElement = document.getElementById("turn");
    const scoreXElement = document.getElementById("scoreX");
    const scoreOElement = document.getElementById("scoreO");
    const messageElement = document.getElementById("message");

    const SIZE = 5;

    let board = [];
    let gameOver = false;

    // sorteia quem começa
    let startingPlayer = Math.random() < 0.5 ? "X" : "O";
    let currentPlayer = startingPlayer;

    function createBoard(){

        boardElement.innerHTML = "";
        board = [];

        for(let row = 0; row < SIZE; row++){

            board[row] = [];

            for(let col = 0; col < SIZE; col++){

                board[row][col] = "";

                const cell = document.createElement("div");

                cell.classList.add("cell");

                cell.dataset.row = row;
                cell.dataset.col = col;

                cell.addEventListener("click", handleMove);

                boardElement.appendChild(cell);
            }
        }

        turnElement.textContent = currentPlayer;

        updateScores();
    }

    function handleMove(e){

        if(gameOver) return;

        const cell = e.target;

        const row = parseInt(cell.dataset.row);
        const col = parseInt(cell.dataset.col);

        if(board[row][col] !== "") return;

        board[row][col] = currentPlayer;

        cell.textContent = currentPlayer;

        cell.classList.add(currentPlayer.toLowerCase());
        cell.classList.add("disabled");

        // destaque apenas das sequências criadas agora
        highlightNewSequences(currentPlayer, row, col);

        updateScores();

        if(isBoardFull()){

            finishGame();
            return;
        }

        currentPlayer = currentPlayer === "X" ? "O" : "X";

        turnElement.textContent = currentPlayer;
    }

    function isBoardFull(){

        for(let row of board){

            for(let cell of row){

                if(cell === "") return false;
            }
        }

        return true;
    }

    function getSequences(player){

        const sequences = [];

        const directions = [
            [0,1],
            [1,0],
            [1,1],
            [1,-1]
        ];

        for(let row = 0; row < SIZE; row++){

            for(let col = 0; col < SIZE; col++){

                if(board[row][col] !== player) continue;

                for(let [dr, dc] of directions){

                    let prevRow = row - dr;
                    let prevCol = col - dc;

                    // garante início da sequência
                    if(
                        prevRow >= 0 &&
                        prevRow < SIZE &&
                        prevCol >= 0 &&
                        prevCol < SIZE &&
                        board[prevRow][prevCol] === player
                    ){
                        continue;
                    }

                    let cells = [];

                    let r = row;
                    let c = col;

                    while(
                        r >= 0 &&
                        r < SIZE &&
                        c >= 0 &&
                        c < SIZE &&
                        board[r][c] === player
                    ){
                        cells.push([r, c]);

                        r += dr;
                        c += dc;
                    }

                    if(cells.length >= 3){
                        sequences.push(cells);
                    }
                }
            }
        }

        return sequences;
    }

    function calculateScore(player){

        let total = 0;

        const sequences = getSequences(player);

        for(let seq of sequences){

            if(seq.length === 3){
                total += 3;
            }
            else if(seq.length === 4){
                total += 6;
            }
            else if(seq.length >= 5){
                total += 10;
            }
        }

        return total;
    }

    function updateScores(){

        scoreXElement.textContent = calculateScore("X");
        scoreOElement.textContent = calculateScore("O");
    }

    // destaca SOMENTE as sequências criadas na jogada atual
    function highlightNewSequences(player, rowPlayed, colPlayed){

        const directions = [
            [0,1],
            [1,0],
            [1,1],
            [1,-1]
        ];

        const sequencesToHighlight = [];

        for(let [dr, dc] of directions){

            let cells = [[rowPlayed, colPlayed]];

            // trás
            let r = rowPlayed - dr;
            let c = colPlayed - dc;

            while(
                r >= 0 &&
                r < SIZE &&
                c >= 0 &&
                c < SIZE &&
                board[r][c] === player
            ){
                cells.unshift([r, c]);

                r -= dr;
                c -= dc;
            }

            // frente
            r = rowPlayed + dr;
            c = colPlayed + dc;

            while(
                r >= 0 &&
                r < SIZE &&
                c >= 0 &&
                c < SIZE &&
                board[r][c] === player
            ){
                cells.push([r, c]);

                r += dr;
                c += dc;
            }

            if(cells.length >= 3){
                sequencesToHighlight.push(cells);
            }
        }

        sequencesToHighlight.forEach(seq => {

            seq.forEach(([row, col]) => {

                const cell = document.querySelector(
                    `.cell[data-row="${row}"][data-col="${col}"]`
                );

                cell.classList.remove("highlight");

                // reinicia animação
                void cell.offsetWidth;

                cell.classList.add("highlight");

                setTimeout(() => {
                    cell.classList.remove("highlight");
                }, 600);
            });
        });
    }

    function finishGame(){

        gameOver = true;

        const scoreX = calculateScore("X");
        const scoreO = calculateScore("O");

        if(scoreX > scoreO){

            messageElement.textContent =
                `Jogador X venceu por ${scoreX} x ${scoreO}!`;
        }
        else if(scoreO > scoreX){

            messageElement.textContent =
                `Jogador O venceu por ${scoreO} x ${scoreX}!`;
        }
        else{

            messageElement.textContent =
                `Empate! ${scoreX} x ${scoreO}`;
        }
    }

    function restartGame(){

        // alterna quem começa
        startingPlayer = startingPlayer === "X" ? "O" : "X";

        currentPlayer = startingPlayer;

        gameOver = false;

        messageElement.textContent = "";

        createBoard();
    }

    createBoard();

</script>

</body>
</html>