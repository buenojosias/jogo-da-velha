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
                <h2 class="text-2xl font-bold mb-2">Clássico 3x3</h2>
                <p class="text-gray-400">O jogo da velha tradicional em um tabuleiro 3x3.</p>
            </a>
            <a href="{{ route('3-move') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Movimento 3x3</h2>
                <p class="text-gray-400">Após colocar 3 peças, mova-as para um espaço vazio para conseguir a vitória.</p>
            </a>
            <a href="{{ route('3-rotation') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Rotação 3x3</h2>
                <p class="text-gray-400">Com 3 peças no tabuleiro, colocar uma nova remove a sua mais antiga do tabuleiro.</p>
            </a>
            <a href="{{ route('4x3-basic') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Clássico 4x4</h2>
                <p class="text-gray-400">Jogo da velha em um tabuleiro 4x4, mas bastam 3 peças em linha para vencer.</p>
            </a>
            <a href="{{ route('4x3-move') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Movimento 4x4 (3 peças)</h2>
                <p class="text-gray-400">Em um tabuleiro 4x4, coloque 3 peças e depois mova-as para fazer uma linha de 3.</p>
            </a>
            <a href="{{ route('4x3-rotation') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Rotação 4x4 (3 peças)</h2>
                <p class="text-gray-400">Em um tabuleiro 4x4, com 3 peças no tabuleiro, colocar uma nova remove a sua mais antiga.</p>
            </a>
            <a href="{{ route('4x3-block') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Bloqueio 4x4</h2>
                <p class="text-gray-400">Uma linha de 3 pode ser bloqueada por uma peça do oponente em uma das pontas.</p>
            </a>
            <a href="{{ route('4x3-hidden') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Oculto 4x4</h2>
                <p class="text-gray-400">As peças estão escondidas! Cada jogador tem 5 peças em um tabuleiro 4x4.</p>
            </a>
            <a href="{{ route('4x4-move') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Linha de 4 com Movimento</h2>
                <p class="text-gray-400">Em um tabuleiro 4x4, faça 4 em linha. Após colocar 4 peças, mova-as para vencer.</p>
            </a>
            <a href="{{ route('5-full') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Pontuação 5x5</h2>
                <p class="text-gray-400">Faça sequências de 3, 4 ou 5 peças para acumular pontos. Quem tiver mais pontos no final, vence.</p>
            </a>
            <a href="{{ route('dice') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Dados da Velha</h2>
                <p class="text-gray-400">Role o dado para decidir em qual coluna jogar em dois tabuleiros simultâneos.</p>
            </a>
            <a href="{{ route('crazy') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Conquista Maluca</h2>
                <p class="text-gray-400">Use peças de tamanhos diferentes para cobrir as do oponente e dominar o tabuleiro.</p>
            </a>
            <a href="{{ route('ultimate') }}" class="bg-gray-800 rounded-lg shadow-lg p-6 hover:bg-gray-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-2">Ultimate Tic-Tac-Toe</h2>
                <p class="text-gray-400">Um jogo da velha dentro de outro. Cada jogada determina onde o próximo oponente irá jogar.</p>
            </a>

        </div>
    </div>
</body>
</html>
