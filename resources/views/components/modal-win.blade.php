<div id="modal-win" class="absolute z-100 h-full w-full pb-6 flex justify-center items-end bg-neutral-900/30" style="display: none;">
    <div class="w-[80%] sm:w-[90%] bg-neutral-800 rounded-lg p-6 flex flex-col items-center gap-6">
        <x-trophy size="64" color="oklch(82.8% 0.189 84.429)" />
        <h2 id="winner-message" class="text-xl font-bold flex items-center">
            <span class="mr-2">Jogador</span> 
            <span id="winner-icon-container"></span> 
            <span class="ml-2">venceu!</span>
        </h2>
        <button id="modal-restart-btn" class="btn-restart">Jogar novamente</button>
    </div>
</div>