<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Jogo da Velha' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex justify-center sm:py-4 h-screen overflow-hidden">
    <main class="relative h-full w-full max-w-[420px] sm:w-[420px] sm:rounded-lg sm:border border-neutral-700/80 sm:shadow shadow-neutral-700 flex flex-col justify-between space-y-2 bg-black/30 overflow-y-auto">
        <header class="py-3 px-6 bg-neutral-800 sm:rounded-t-lg">
            <div class="title text-center uppercase font-black">{{ $title }}</div>
        </header>
        <x-playing />
        {{ $slot }}
    </main>
    @yield('scripts')
</body>
</html>