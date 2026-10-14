@props(['modality', 'title', 'description'])
@php
    $howToPlay = [
        'Clássico 3×3' => 'Em cada turno, marque uma casa vazia. Faça três marcas em linha, coluna ou diagonal para vencer.',
        'Clássico 4×4 (3 peças)' => 'Em cada turno, marque uma casa vazia. Faça três marcas em linha, coluna ou diagonal para vencer.',
        'Movimento 3×3' => 'Coloque três peças. Depois, selecione uma das suas peças e uma casa vazia para movê-la. Após três movimentos seus, você não poderá mover a mesma peça em dois turnos consecutivos. Faça três em linha para vencer.',
        'Movimento 4×4' => 'Coloque três peças. Depois, selecione uma das suas peças e uma casa vazia para movê-la. Faça três em linha para vencer.',
        'Linha de 4 com Movimento' => 'Coloque quatro peças. Depois, selecione uma das suas peças e uma casa vazia para movê-la. Faça quatro em linha para vencer.',
        'Rotação 3×3' => 'Marque uma casa vazia a cada turno. Ao colocar a quarta peça, sua peça mais antiga sai. Faça três em linha para vencer.',
        'Rotação 4×4 (3 peças)' => 'Marque uma casa vazia a cada turno. Ao colocar a quarta peça, sua peça mais antiga sai. Faça três em linha para vencer.',
        'Bloqueio 4×4' => 'Marque uma casa vazia. Faça três em linha para vencer, desde que o adversário não ocupe a casa imediatamente antes ou depois da linha.',
        'Oculto 4×4' => 'Escolha qualquer casa: as marcas ficam escondidas até a vitória. Casa adversária não muda; tocar sua própria marca a move para uma casa vazia aleatória. Ao colocar a sexta peça, a mais antiga sai. Faça três em linha para vencer.',
        'Duas Linhas 5×5' => 'Marque uma casa vazia por turno. Forme duas linhas de três marcas para vencer. Sequências sobrepostas na mesma direção contam como uma linha só.',
        'Pontuação 5×5' => 'Marque uma casa vazia por turno. Sequências de três, quatro e cinco marcas valem 3, 6 e 10 pontos. O tabuleiro cheio encerra a rodada; vence quem tiver mais pontos.',
        'Dados da Velha' => 'Role o dado. A face determina um dos dois tabuleiros e uma coluna; escolha uma casa vazia nela. Se a coluna estiver cheia, role novamente. Faça três em linha em um dos tabuleiros para vencer.',
        'Conquista Maluca' => 'Escolha uma peça disponível e uma casa. Peças maiores podem cobrir as menores, inclusive as do rival. Faça três peças visíveis em linha que o rival não possa mais cobrir.',
        'Ultimate' => 'Escolha um subtabuleiro disponível e marque uma casa. Essa casa indica o subtabuleiro obrigatório do rival; se ele estiver fechado, o rival escolhe qualquer um aberto. Faça três em linha para conquistar um subtabuleiro e conquiste três subtabuleiros em linha para vencer.',
    ];
@endphp

<header {{ $attributes->class(['ds-match-header rounded-ds-card border border-ds-border bg-ds-surface px-4 py-3 shadow-ds-card sm:px-5 sm:py-3']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-5">
        <div class="ds-match-heading min-w-0">
            <span class="ds-modality inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-ds-primary before:size-2 before:rotate-45 before:rounded-[2px] before:bg-ds-turquoise before:content-['']">{{ $modality }}</span>
            <h1 class="ds-match-title text-xl leading-tight font-extrabold tracking-tight text-ds-text sm:text-2xl"><span class="sr-only">{{ $modality }} — </span>{{ $title }}</h1>
            @if ($description)
                <p class="sr-only">{{ $description }}</p>
            @endif
            @if (isset($howToPlay[$modality]))
                <details class="mt-2 text-sm text-ds-text-secondary" data-testid="how-to-play">
                    <summary class="w-fit cursor-pointer rounded-ds-control px-1 py-2 font-bold text-ds-primary focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">Como jogar</summary>
                    <p class="max-w-3xl pb-1 leading-relaxed">{{ $howToPlay[$modality] }}</p>
                </details>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
