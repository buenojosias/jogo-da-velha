<div id="modal-win" class="absolute z-100 h-full w-full flex justify-center items-center bg-neutral-900/90" style="display: none;">
    <div class="w-[70%] sm:w-[90%] bg-neutral-800 rounded-lg p-6 flex flex-col items-center gap-6">
        <x-trophy size="64" color="oklch(82.8% 0.189 84.429)" />
        <h2 id="winner-message" class="text-xl font-bold flex items-center">
            <span class="mr-2">Jogador</span> 
            <span id="winner-icon-container"></span> 
            <span class="ml-2">venceu!</span>
        </h2>
        <button id="modal-restart-btn" class="btn-restart">Jogar novamente</button>
    </div>
</div>