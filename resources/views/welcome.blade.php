@php
    $groups = [
        'Para começar' => [
            ['3-basic', 'Clássico 3×3', 'O jogo conhecido por todo mundo: três em linha vencem.', 'Três em linha', 'grid', 3, true],
            ['4x3-basic', 'Clássico 4×4', 'Mais espaço para jogar; três em linha ainda vencem.', 'Tabuleiro maior', 'grid', 4],
        ],
        'Peças em movimento' => [
            ['3-move', 'Movimento 3×3', 'Depois de colocar três peças, mova uma por turno.', 'Mova suas peças', 'movement', 3],
            ['3-rotation', 'Rotação 3×3', 'A quarta peça substitui a sua mais antiga.', 'Peça mais antiga sai', 'rotation', 3],
            ['4x3-move', 'Movimento 4×4', 'Coloque três peças e depois mova para formar uma linha.', 'Três peças móveis', 'movement', 4],
            ['4x3-rotation', 'Rotação 4×4', 'No tabuleiro maior, sua peça mais antiga sai ao colocar a quarta.', 'Rotação no 4×4', 'rotation', 4],
            ['4x4-move', 'Linha de 4 com Movimento', 'Coloque quatro peças e mova para fazer quatro em linha.', 'Quatro em linha', 'movement', 4],
        ],
        'Estratégia e desafios' => [
            ['4x3-block', 'Bloqueio 4×4', 'Três em linha só vencem sem um rival bloqueando a ponta.', 'Pontas importam', 'grid', 4],
            ['4x3-hidden', 'Oculto 4×4', 'As marcas ficam escondidas até uma linha vencedora surgir.', 'Memória e surpresa', 'hidden', 4],
            ['5x5-2-lines', 'Duas Linhas 5×5', 'Conquiste duas linhas independentes de três marcas.', 'Duas linhas', 'grid', 5],
            ['5-full', 'Pontuação 5×5', 'Sequências de três, quatro ou cinco marcas somam pontos.', 'Mais pontos vencem', 'grid', 5],
        ],
        'Jeitos especiais de jogar' => [
            ['dice', 'Dados da Velha', 'O dado define o tabuleiro e a coluna da sua jogada.', 'Dois tabuleiros', 'dice', 3],
            ['crazy', 'Conquista Maluca', 'Peças maiores cobrem as menores para conquistar casas.', 'Peças de tamanhos diferentes', 'crazy', 3],
            ['ultimate', 'Ultimate Tic-Tac-Toe', 'Nove jogos pequenos formam uma disputa maior.', 'Nove subtabuleiros', 'ultimate', 3],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR" data-design-system="experimental">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-public-meta route-name="welcome" />
    <script>
        (() => {
            let preference = 'auto';
            try {
                const stored = localStorage.getItem('jogo-da-velha.theme');
                preference = stored === 'light' || stored === 'dark' ? stored : 'auto';
            } catch {}
            document.documentElement.dataset.themePreference = preference;
            document.documentElement.dataset.theme = preference === 'auto'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preference;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-ds-text">
    <a href="#catalogo" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-ds-control focus:bg-ds-surface focus:p-3 focus:text-ds-primary focus:outline-3 focus:outline-ds-focus">Pular para o catálogo</a>
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-ds-divider py-4">
            <span class="text-sm font-black tracking-tight text-ds-primary sm:text-base">✕ ○ <span class="text-ds-text">Plataforma Jogo da Velha</span></span>
            <div class="flex items-center gap-3">
                <a href="#catalogo" class="inline-flex min-h-11 items-center rounded-ds-control px-3 text-sm font-bold text-ds-text-secondary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Ver modalidades</a>
                <x-design-system.theme-selector />
            </div>
        </header>
        <main>
            <section class="grid items-center gap-7 py-12 sm:py-16 lg:grid-cols-2 lg:gap-12" aria-labelledby="hero-title">
                <div class="max-w-2xl">
                    <p class="font-black uppercase tracking-widest text-ds-turquoise">14 maneiras de jogar</p>
                    <h1 id="hero-title" class="mt-3 text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">Uma partida. Muitas possibilidades.</h1>
                    <p class="mt-5 max-w-xl text-lg leading-relaxed text-ds-text-secondary">Escolha um tabuleiro, convide alguém e descubra uma nova maneira de jogar velha. Da primeira linha à estratégia mais surpreendente.</p>
                    <div class="mt-6 flex flex-wrap gap-2 text-sm font-bold text-ds-text-secondary">
                        <span class="rounded-full border border-ds-border bg-ds-surface px-4 py-2">Grátis e sem cadastro</span>
                        <span class="rounded-full border border-ds-border bg-ds-surface px-4 py-2">Duas pessoas no mesmo dispositivo</span>
                    </div>
                    <a href="#catalogo" class="mt-7 inline-flex min-h-12 items-center rounded-ds-control bg-ds-primary px-6 font-extrabold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Explorar as 14 modalidades ↓</a>
                </div>
                <div class="relative mx-auto flex w-full max-w-sm items-center justify-center rounded-[2rem] border border-ds-border bg-ds-surface p-8 shadow-ds-elevated sm:p-12" aria-hidden="true">
                    <div class="grid aspect-square w-full grid-cols-3 gap-2 rotate-[-5deg] rounded-[1.5rem] bg-ds-primary p-3 shadow-ds-card">
                        @foreach (['X', '', 'O', '', 'X', '', 'O', '', 'X'] as $mark)
                            <span class="flex items-center justify-center rounded-xl bg-ds-surface text-5xl font-black {{ $mark === 'X' ? 'text-ds-x' : 'text-ds-o' }}">{{ $mark }}</span>
                        @endforeach
                    </div>
                </div>
            </section>
            <section id="catalogo" class="scroll-mt-6 pb-16" aria-labelledby="catalog-title">
                <div class="mb-8 max-w-2xl">
                    <p class="font-black uppercase tracking-widest text-ds-turquoise">Escolha sua próxima partida</p>
                    <h2 id="catalog-title" class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Catálogo de modalidades</h2>
                    <p class="mt-3 text-ds-text-secondary">Comece pelo clássico ou escolha uma regra que desperte sua curiosidade. As instruções ficam disponíveis em cada partida.</p>
                </div>
                @foreach ($groups as $group => $modalities)
                    <section class="mb-10" aria-labelledby="group-{{ $loop->index }}">
                        <h3 id="group-{{ $loop->index }}" class="mb-4 text-xl font-black text-ds-text">{{ $group }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($modalities as [$routeName, $name, $description, $feature, $kind, $size])
                                <article class="flex min-w-0 flex-col rounded-ds-card border {{ $routeName === '3-basic' ? 'border-ds-primary' : 'border-ds-border' }} bg-ds-surface p-4 shadow-ds-card sm:p-5">
                                    <x-design-system.catalog-preview :kind="$kind" :size="$size" />
                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-ds-surface-muted px-3 py-1 text-xs font-bold text-ds-primary">{{ $feature }}</span>
                                        @if ($routeName === '3-basic')<span class="rounded-full bg-ds-primary px-3 py-1 text-xs font-bold text-ds-on-primary">Para começar</span>@endif
                                    </div>
                                    <h4 class="mt-3 text-xl font-black">{{ $name }}</h4>
                                    <p class="mt-2 grow text-sm leading-relaxed text-ds-text-secondary">{{ $description }}</p>
                                    <a href="{{ route($routeName) }}" class="mt-5 inline-flex min-h-12 items-center justify-center rounded-ds-control bg-ds-primary px-4 font-bold text-ds-on-primary hover:bg-ds-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus" aria-label="Jogar {{ $name }}">Jogar <span aria-hidden="true" class="ml-2">↗</span></a>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </section>
        </main>
        <footer class="flex flex-wrap justify-between gap-2 border-t border-ds-divider py-6 text-sm text-ds-text-secondary">
            <span>Plataforma Jogo da Velha</span><a href="{{ route('privacy') }}" class="rounded-ds-control font-bold text-ds-primary hover:underline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Privacidade (rascunho)</a><span>14 modalidades · duas pessoas · uma tela</span>
        </footer>
    </div>
</body>
</html>
