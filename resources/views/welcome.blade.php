<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
        body {
            background-color: #111827;
        }
    </style>
</head>
<body class="antialiased text-white">
    <div class="container mx-auto p-8">
        <h1 class="text-4xl font-bold text-center mb-12">Jogos da Velha</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <a href="{{ route('3-basic') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha</h2>
                <p class="text-gray-400">O jogador inicial alterna automaticamente a cada nova partida.</p>
            </a>
            <a href="{{ route('3-move') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha - 3 Peças</h2>
                <p class="text-gray-400">Cada jogador possui apenas 3 peças. Depois de colocar todas, será possível mover uma peça por vez.</p>
            </a>
            <a href="{{ route('3-rotation') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha - Peça Mais Antiga</h2>
                <p class="text-gray-400">Cada jogador pode manter apenas 3 peças no tabuleiro. Ao colocar a 4ª peça, a peça mais antiga daquele jogador desaparece.</p>
            </a>
            <a href="{{ route('4x3-basic') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha 4x3 - Modo básico</h2>
                <p class="text-gray-400">Vitória com 3 peças seguidas.</p>
            </a>
            <a href="{{ route('4x3-move') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha 4x4 - Modo Mover</h2>
                <p class="text-gray-400">Cada jogador pode manter apenas 3 peças. Para comocar a 4ª peça, o jogador deve mover uma peça existente.</p>
            </a>
            <a href="{{ route('4x3-rotation') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha 4x4 - Modo Rotação</h2>
                <p class="text-gray-400">Cada jogador pode manter apenas 3 peças. Ao colocar a 4ª peça, a peça mais antiga daquele jogador desaparece.</p>
            </a>
            <a href="{{ route('4x4-block') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha 4x4 - Modo Bloqueio</h2>
                <p class="text-gray-400">Sequências de 3 podem ser bloqueadas pelo adversário nas extremidades.</p>
            </a>
            <a href="{{ route('4x4-rotation') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha 4x4 com Rotação</h2>
                <p class="text-gray-400">Jogo da Velha 4x4 com rotação de peças.</p>
            </a>
            <a href="{{ route('5-full') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha 5x5</h2>
                <p class="text-gray-400">O tabuleiro possui 5 linhas e 5 colunas. Sequências de 3, 4 ou 5 peças valem pontos. Quem tiver mais pontos no final vence.</p>
            </a>
            <a href="{{ route('dice') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha - Dado</h2>
                <p class="text-gray-400">Cada jogador rola o dado para determinar em qual coluna deve marcar.</p>
            </a>
            <a href="{{ route('crazy') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha Maluca</h2>
                <p class="text-gray-400">Cada jogador poderá cobrir a peça do adversário com uma peça maior.</p>
            </a>
            <a href="{{ route('ultimate') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Jogo da Velha - Ultimate</h2>
                <p class="text-gray-400">Versão mais avançada do jogo da velha com regras especiais.</p>
            </a>

        </div>
    </div>
</body>
</html>
