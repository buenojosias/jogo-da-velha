<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Jogo da Velha' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex justify-center sm:py-4 h-screen overflow-hidden">
    <main class="h-full w-full max-w-[420px] sm:w-[420px] sm:rounded-lg sm:border border-neutral-700/80 sm:shadow shadow-neutral-700 flex flex-col justify-between space-y-2 bg-black/30 overflow-y-auto">
        <header class="py-3 px-6 bg-neutral-800 sm:rounded-t-lg">
            <div class="title text-center uppercase font-black">{{ $title }}</div>
        </header>

        {{ $slot }}

        {{-- <div class="flex flex-col items-center space-y-2">
            <div class="mt-2 flex items-center py-1.5 px-4 space-x-1.5 bg-neutral-600/40 border border-neutral-600 rounded-full gap-x-1">Vez do jogador
                <x-o size="22" />
            </div>
        </div> --}}

        {{-- <div class="px-6">
            <div class="my-4 bg-neutral-600/20 border border-neutral-600/60 rounded-lg p-2">
                <h4 class="mb-4 text-sm text-center">Escolha uma peça</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid grid-cols-3 text-center gap-3">
                        <button class="text-center text-sm font-bold">X</button>
                        <button class="text-center text-sm font-bold">X</button>
                        <button class="text-center text-sm font-bold">X</button>
                        <button class="text-center text-md font-bold">X</button>
                        <button class="text-center text-md font-bold">X</button>
                        <button class="text-center text-md font-bold">X</button>
                        <button class="text-center text-lg font-bold">X</button>
                        <button class="text-center text-lg font-bold">X</button>
                        <button class="text-center text-lg font-bold">X</button>
                    </div>
                    <div class="grid grid-cols-3 text-center gap-3">
                        <button class="text-center text-sm font-bold">O</button>
                        <button class="text-center text-sm font-bold">O</button>
                        <button class="text-center text-sm font-bold">O</button>
                        <button class="text-center text-md font-bold">O</button>
                        <button class="text-center text-md font-bold">O</button>
                        <button class="text-center text-md font-bold">O</button>
                        <button class="text-center text-lg font-bold">O</button>
                        <button class="text-center text-lg font-bold">O</button>
                        <button class="text-center text-lg font-bold">O</button>
                    </div>
                </div>
            </div> --}}

            {{-- <div class="board w-full bg-neutral-600/10 border border-neutral-600/40 rounded-xl grid grid-cols-3 p-0.5">
                @for ($i = 0; $i < 9; $i++)
                <div class="p-1">
                    @php
                        $symbol = array_rand(['x', 'o', '']);
                    @endphp
                    <div class="cell w-full aspect-square transition-all duration-400 bg-neutral-800/20 hover:bg-neutral-600/40 rounded-lg border-2 border-neutral-600/40 cursor-pointer flex items-center justify-center text-4xl font-bold">
                        @if ($symbol === 1)
                            <x-x size="44" />
                        @elseif ($symbol === 2)
                            <x-o size="44" />
                        @endif
                    </div>
                </div>
                @endfor
            </div>
        </div> --}}

        {{-- <div class="flex flex-col items-center space-y-6 py-4 px-6">
            <div class="flex w-full space-x-4 text-center">
                <div class="flex-1 p-3 rounded-xl bg-neutral-900/50 border border-neutral-600 border-t-2 space-y-2 border-t-green-500">
                    <p class="text-xs font-semibold flex items-center justify-center gap-1">JOGADOR <x-o size="16" /></p>
                    <p class="text-3xl font-bold">2</p>
                </div>
                <div class="flex-1 p-3 rounded-xl bg-neutral-900/50 border border-neutral-600 border-t-2 space-y-2 border-t-blue-500">
                    <p class="text-xs font-semibold flex items-center justify-center gap-1">JOGADOR <x-x size="16" /></p>
                    <p class="text-3xl font-bold">1</p>
                </div>
            </div>
            <div class="w-full flex flex-col gap-4 font-bold">
                <div class="flex gap-3">
                    <button class="px-4 py-3 flex-1 bg-sky-600 hover:bg-sky-700 text-white cursor-pointer rounded-lg transition-all duration-400">REINICIAR</button>
                    <button class="px-6 py-3 border border-neutral-600 hover:bg-neutral-600 text-white cursor-pointer rounded-lg transition-all duration-400">?</button>
                </div>
                <button class="px-4 py-2.5 flex-1 bg-red-600 hover:bg-red-700 text-white cursor-pointer rounded-lg transition-all duration-400">SAIR</button>
                </div>
            </div>
        </div> --}}
    </main>
    
    @yield('scripts')
</body>
</html>