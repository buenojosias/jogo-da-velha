<!DOCTYPE html>
<html lang="pt-BR" data-design-system="experimental">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-public-meta route-name="3-basic" />
    <script>
        (() => {
            let preference = 'auto';

            try {
                const stored = localStorage.getItem('jogo-da-velha.theme');
                preference = stored === 'light' || stored === 'dark' ? stored : 'auto';
            } catch {
                // A blocked storage API must not prevent the page from rendering.
            }

            document.documentElement.dataset.themePreference = preference;
            document.documentElement.dataset.theme = preference === 'auto'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preference;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="min-h-screen text-ds-text">
        <livewire:experimental-classic-3x3 :match-id="$matchId" :public-entry="$publicEntry ?? false" />
    </main>
</body>
</html>
