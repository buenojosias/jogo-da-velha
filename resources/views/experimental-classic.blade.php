<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clássico 3×3 experimental</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="min-h-screen bg-slate-50">
        <livewire:experimental-classic-3x3 :match-id="$matchId" />
    </main>
</body>
</html>
