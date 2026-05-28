@props(['pointsO' => '0', 'pointsX' => '0'])
<footer>
    <div class="scoreboard">
        <div class="score-o">
            <p class="header">jogador <x-o size="16" /></p>
            <p id="player-o-score" class="points">{{ $pointsO }}</p>
        </div>
        <div class="score-x">
            <p class="header">jogador <x-x size="16" /></p>
            <p id="player-x-score" class="points">{{ $pointsX }}</p>
        </div>
    </div>
    <div class="actions">
        <div class="flex gap-3">
            <button id="restartBtn" class="btn-restart">Reiniciar</button>
            <button class="btn-help">?</button>
        </div>
        <a class="btn-leave" href="{{ route('welcome') }}">Sair</a>
    </div>
</footer>