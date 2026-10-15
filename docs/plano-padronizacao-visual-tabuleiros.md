# Plano de padronização visual dos tabuleiros e casas

## Escopo e diagnóstico

Este documento planeja a padronização das 14 modalidades públicas presentes em [`routes/web.php`](../routes/web.php) e no catálogo. A análise é estática: foram inspecionados Blade, Livewire, Alpine, `app.css`, `app.js`, regras de jogo pertinentes e testes existentes. **Nenhuma implementação foi alterada nesta etapa.** As medidas e os contrastes de auditorias anteriores são referências, não uma nova medição visual.

O projeto já tem uma base compartilhada: [`game-board.blade.php`](../resources/views/components/design-system/game-board.blade.php) desenha casas de 3, 4 ou 5 colunas, com X/O, vitória, seleção de movimento, peça antiga, restrição, última jogada, progresso de linha e coluna do dado. [`classic-match.blade.php`](../resources/views/components/design-system/classic-match.blade.php), [`movement-match.blade.php`](../resources/views/components/design-system/movement-match.blade.php), [`rotation-match.blade.php`](../resources/views/components/design-system/rotation-match.blade.php) e [`hidden-match.blade.php`](../resources/views/components/design-system/hidden-match.blade.php) compõem páginas inteiras. Dados reutiliza `game-board` duas vezes. Oculto usa esse componente só na revelação final; Maluca e Ultimate desenham suas próprias casas, por razões estruturais reais.

Hoje as cores de tema são centralizadas em [`app.css`](../resources/css/app.css), mas geometria e estados estão divididos entre classes Tailwind longas nos Blade e seletores específicos em CSS. Os elementos internos dos tabuleiros não têm uma única fonte de verdade para borda, raio, espaçamento, sombra e hover. O painel `experimental-game-region` é um invólucro externo consistente; as grades de casas, as seções dos dois tabuleiros de Dados e os subtabuleiros de Ultimate usam tratamentos distintos. O catálogo usa miniaturas decorativas em `catalog-preview.blade.php`; pode adotar os tokens depois, sem ser tratado como tabuleiro jogável.

## Inventário das modalidades e componentes

Convenções da tabela: `GB` = `x-design-system.game-board`; `C`, `M`, `R`, `H` = componentes de composição `classic-match`, `movement-match`, `rotation-match`, `hidden-match`. Todas as páginas usam Livewire 4 com componentes de arquivo único `⚡*.blade.php`, exceto as views externas, que apenas montam os componentes. `4x3` nos nomes técnicos legados significa **tabuleiro 4×4 com objetivo de três marcas**, não quatro linhas por três colunas.

| Modalidade (rota) | Organização e casas | Renderização | Particularidade visual e interação |
| --- | --- | --- | --- |
| Clássico 3×3 (`/3-basico`) | 1 grade 3×3, 9 | `⚡experimental-classic-3x3` → C → GB | X/O, ocupada, vitória; clique, toque e botão por teclado. |
| Movimento 3×3 (`/3-mover`) | 1 grade 3×3, 9 | `⚡experimental-move-3x3` → GB | Colocação e seleção de peça via Alpine/`aria-pressed`; peça impedida de mover de novo recebe `data-restricted-cell`. |
| Rotação 3×3 (`/3-rotacao`) | 1 grade 3×3, 9 | `⚡rotation-match` → R → GB | Peça mais antiga recebe borda tracejada, anel e selo “SAI”; remoção automática após colocação. |
| Clássico 4×4, três em linha (`/4-3-basico`) | 1 grade 4×4, 16 | `⚡experimental-classic-4x3` → C → GB | Casas válidas são calculadas por proximidade; última jogada adversária recebe borda própria. |
| Movimento 4×4, três peças (`/4-3-mover`) | 1 grade 4×4, 16 | `⚡experimental-move-4x3` → M → GB | Colocação e seleção para movimento; vitória por três. |
| Rotação 4×4 (`/4-3-rotacao`) | 1 grade 4×4, 16 | `⚡rotation-match` → R → GB | Mesmo indicador “SAI” da variante 3×3, em casas menores. |
| Bloqueio 4×4 (`/4-3-bloqueio`) | 1 grade 4×4, 16 | `⚡block-4x3` → C → GB | Bloqueio de formação gera anel temporário de aviso nas casas envolvidas e mensagem “Linha bloqueada!”. |
| Oculto 4×4 (`/4-3-oculto`) | 1 grade 4×4, 16 | `⚡hidden-4x3` → H → grade própria durante partida; GB ao revelar | “?” em toda casa durante partida, feedback temporário de jogada/turno; X/O e vitória aparecem só no fim. |
| Linha de 4 com Movimento (`/4-mover`) | 1 grade 4×4, 16 | `⚡experimental-move-4x4` → M → GB | Quatro peças por jogador e linha de quatro; mesma apresentação de seleção. |
| Duas Linhas 5×5 (`/2-linhas`) | 1 grade 5×5, 25 | `⚡two-lines-5x5` → C → GB | Primeira linha X/O fica destacada enquanto a rodada continua; segunda completa a vitória. |
| Pontuação 5×5 (`/5-cheia`) | 1 grade 5×5, 25 | `⚡full-5x5` → C → GB | Pontuação por sequências; não passa casas vencedoras individuais ao GB. |
| Dados da Velha (`/dado`) | 2 grades 3×3, 18 casas ao todo | `⚡dice-match` → 2 seções → GB | Dado autoriza uma coluna de um tabuleiro; `inert` durante a rolagem; vitória local. |
| Conquista Maluca (`/maluca`) | 1 grade 3×3, 9 pilhas; seletor separado de 6 tipos de peça com 3 cópias cada | `⚡crazy-match` → `crazy-board`/`crazy-cell` | Topo X/O com tamanho e contador de pilha; peça selecionada no estoque; linha só ganha destaque terminal quando não pode ser coberta. |
| Ultimate (`/ultimate`) | Visão geral 3×3 de 9 subtabuleiros 3×3, 81 casas; cópia da grade no painel ampliado | `⚡ultimate-match` → `x-ultimate-board-cells` nas duas apresentações | Jogada direta na visão geral, ampliação por toque longo, seleção/foco; destino, conquista, empate local e linha global destacados no subtabuleiro. |

O ponteiro usa `wire:click` no GB comum, `x-on:click` seguido de ação Livewire nas modalidades com seleção/JS, e `pointerdown`/`pointermove`/`pointerup` apenas para o toque longo de Ultimate. Botões nativos preservam Tab, Enter e Espaço; estados de foco e `aria-pressed`/rótulos não devem mudar com a migração. A seleção de Ultimate também responde a `focusin`, possui fechamento por Escape e restauração de foco. Em Maluca, o tabuleiro depende da peça escolhida no seletor.

### Geometria e invólucros atuais

| Contexto | Dimensão, espaçamento e aparência atuais |
| --- | --- |
| Painel comum | `rounded-ds-card`, borda de 1 px e borda superior de 4 px, `bg-ds-surface`, `shadow-ds-elevated`, `p-3 sm:p-5`. |
| GB 3/4/5 | `max-w-[28rem]`, `grid-cols-{3,4,5}`, `gap-2 sm:gap-3`; casas `aspect-square min-h-18`, borda 2 px, `rounded-ds-control`, `bg-ds-surface`, `shadow-sm`, texto `text-5xl sm:text-6xl`. |
| GB em paisagem curta | `app.css` limita a largura geral a `min(100%, 14.5rem, 100dvh - 8rem)` e o vão a `0.375rem`; 5×5 chega a `16rem` e fonte menor. Seletor condicionado por largura, altura e orientação da viewport. |
| Dados | Duas seções lado a lado, `p-1 sm:p-3`, borda 1 px, fundo atenuado; GB interno até `18rem`, com `gap: clamp(0.25rem, 0.8vw, 0.75rem)` e fonte reduzida. |
| Maluca | Grade até `25rem`, `gap-2`; casas `min-h-16`, borda 2 px, superfície elevada, peça circular escalada em 32/58/86% da largura; ajustes próprios em paisagem curta. |
| Ultimate | Visão geral até `38rem`, `gap-1 sm:gap-2`; subtabuleiro com borda 2 px, `p-0.5 sm:p-1.5`, raio de 5 px/controle; casas internas com borda 1 px, `rounded-sm`, `gap-0.5`; ampliação até `19rem`, `gap-1.5`, sombra no painel e nas casas ampliadas. |

Há `min-h-18` no GB comum junto de largura limitada por grade. Isso merece medição em contêineres estreitos: uma altura mínima maior que a largura calculada pode quebrar o quadrado visual. `aspect-square`, `minmax(0,1fr)` e áreas de toque exigem ajuste conjunto, sem forçar overflow.

## Matriz dos estados encontrados

`S` = estado visual explícito; `L` = estado lógico/semântico presente, sem destaque exclusivo; `—` = não se aplica. Célula “indisponível” inclui ocupação, restrição de regra ou fim de rodada, conforme a modalidade; essas causas precisam continuar distintas nos dados. `V` = linha vencedora terminal; `P` = linha já formada com rodada em andamento; `A` = peça antiga/remoção automática; `B` = bloqueio de formação temporário; `D` = destino de dado; `R` = restrição de movimento; `U` = estados de subtabuleiro.

| Modalidade | Neutra / X / O | Disponível / indisponível | V | Seleção | P | A | B | D | R | U / outros |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Clássico 3×3 | S | L/S (`disabled`) | S | — | — | — | — | — | — | — |
| Movimento 3×3 | S | L/S | S | S | — | — | — | — | S | — |
| Rotação 3×3 | S | L/S | S | — | — | S | — | — | — | — |
| Clássico 4×4 | S | L/S | S | — | — | — | — | — | — | Última jogada: S |
| Movimento 4×4, três peças | S | L/S | S | S | — | — | — | — | — | — |
| Rotação 4×4 | S | L/S | S | — | — | S | — | — | — | — |
| Bloqueio 4×4 | S | L/S | S | — | — | — | S | — | — | — |
| Oculto 4×4 | “?” durante a rodada; X/O só ao revelar | L/S | S só ao revelar | — | — | remoção pela regra, sem selo na grade | — | — | — | Feedback transitório: S |
| Linha de 4 com Movimento | S | L/S | S | S | — | — | — | — | — | — |
| Duas Linhas 5×5 | S | L/S | S | — | S | — | — | — | — | — |
| Pontuação 5×5 | S | L/S | — | — | — | — | — | — | — | Pontos fora da grade |
| Dados da Velha | S | L/S | S | — | — | — | — | S | — | Rolagem pendente: `inert` |
| Conquista Maluca | S, topo/pilha/tamanho | L/S | S terminal | seleção no estoque | linha cobrável sem destaque próprio | — | — | — | — | Pilha e peça gasta/fora de turno: S |
| Ultimate | S | L/S por destino | vitória global no subtabuleiro | subtabuleiro: S | conquista local: S | — | — | — | — | U: neutro, disponível, alvo, selecionado, bloqueado, X, O, empate, zoom |

No GB, o marcador usa `text-ds-x`/`text-ds-o`; a casa vazia recebe por padrão a classe de X, mas não contém texto. `data-winning-cell`, `data-line-progress`, `data-oldest-cell`, `data-oldest-turn`, `data-restricted-cell`, `data-last-move` e `data-allowed-column` já transportam estados. A vitória comum recebe fundo, borda, sombra interna e ✦. O progresso de Duas Linhas usa o mesmo verde de sucesso para X e O; o jogador ainda é identificado pela marca e pelo `data-line-progress`. A peça antiga usa selo “SAI” além de tracejado/anel. Bloqueio usa `data-block-feedback` e anel por um segundo. Oculto usa `data-feedback-kind` (`placed-x`, `placed-o`, `own-move`, `turn-lost`) e animação de 1,2 segundo. Não há “casa conquistada X/O enquanto continua” em toda modalidade: em Ultimate a **unidade conquistada é o subtabuleiro**; em Duas Linhas há uma **linha em progresso**; em Maluca uma linha cobrável ainda não é conquista definitiva.

Em Ultimate, `data-result`, `data-available`, `data-target`, `data-selected` e `data-zoomed` compõem o subtabuleiro. O estado global vencedor hoje tem precedência sobre a cor X/O da borda; conquista local mostra sobreposição X/O, empate mostra `=`, indisponível tem borda tracejada e seleção troca cor da borda. As casas da linha que conquistou um subtabuleiro **não** recebem destaque individual, conforme a decisão já aplicada.

## Inconsistências e riscos visuais observados no código

1. A mesma casa básica tem borda 2 px e raio de controle no GB, borda 2 px em Maluca, e borda 1 px com raio pequeno em Ultimate. Parte da diferença é necessária pela densidade, mas hoje os números ficam dispersos.
2. O GB compartilha classes de superfície/hover/disabled com vários usos, mas Dados corrige `:disabled` e tamanho em CSS próprio, enquanto Maluca e Oculto repetem quase todas as classes-base. Alterar uma propriedade comum exige várias edições.
3. `enabled:hover:*` no GB e em Ultimate e `hover:*` em Maluca podem produzir hover persistente após toque. O suporte precisa ser limitado a `(hover: hover) and (pointer: fine)`; foco, seleção e feedback de toque permanecem independentes.
4. Bordas de última jogada, peça antiga, restrição, seleção e coluna autorizada competem pela mesma propriedade em classes utilitárias, com precedência difícil de revisar. A seleção no Ultimate é CSS separado. Um efeito de anel pode ocultar outro.
5. Bordas, raios, gap e sombras respondem principalmente a `sm:` ou `vw`, embora a área real varie com duas grades de Dados, 5×5 e nove subtabuleiros. `min-h-18` pode contrariar `aspect-square` em casas estreitas.
6. Vitória usa `--ds-success` mesmo quando a marca é X ou O; conquista Ultimate usa cores X/O; Duas Linhas usa sucesso para linha já feita. A semântica de vitória, conquista e progresso deve ser documentada e aplicada sem misturar esses eventos.
7. O estado `disabled` agrega vários motivos; opacidade uniforme apagaria marca, vitória, selo e feedback. Oculto revela as peças apenas ao final, e Maluca tem disponibilidade real por tamanho da peça, não só por casa.

O relatório [`auditoria-visual-etapa-17.md`](auditoria-visual-etapa-17.md) registrou e corrigiu anteriormente o contraste das bordas com `--ds-board-border` (3,79:1 claro, 5,03:1 escuro no fundo comum, em amostras). Essa melhoria é uma referência a preservar. O relatório também registrou a geometria funcional de 5×5, Dados, Maluca e Ultimate em viewports pequenas; alterações de densidade devem ser comparadas contra essas amostras.

## Arquitetura proposta

Concentrar o sistema em `resources/css/app.css`, junto dos tokens `--ds-*` existentes. Usar quatro camadas de responsabilidade, sem criar um componente Blade por estado:

1. **Tokens de tema**: valores claro/escuro em `html[data-design-system='experimental']` e `[data-theme='dark']`; mapear para utilitários Tailwind em `@theme inline` apenas quando houver uso real como classe. Variáveis comuns de geometria podem ficar fora do bloco de tema.
2. **Classes semânticas-base**: por exemplo `.ds-board-grid`, `.ds-board-cell`, `.ds-sub-board`, `.ds-board-frame`. Elas concentram superfície, borda, raio, gap, sombra, cursor, foco e pressão. O painel externo `experimental-game-region` pode manter sua composição atual; tokens específicos de tabuleiro não precisam redesenhar o painel da página.
3. **Modificadores de estado por atributos**: preservar dados existentes e introduzir apenas nomes consistentes quando uma migração exigir (`data-availability`, `data-result`, `data-selected`, `data-block-feedback`). Blade/Livewire/Alpine continuam calculando estados; CSS só os apresenta. Variantes de densidade (`data-density="compact"`, `data-density="regular"`) representam apresentação, não regra.
4. **Exceções pequenas de modalidade**: seletor de peças e pilhas da Maluca, feedback do Oculto, animação do dado e zoom do Ultimate continuam locais. Elas consomem tokens comuns para casa, marca e estados sempre que fizer sentido.

O GB é o primeiro ponto de integração: substituir gradualmente suas classes visuais repetidas por classes-base, mantendo `wire:key`, eventos, `data-testid`, atributos ARIA e propriedades recebidas. Oculto reutiliza a base da casa durante a partida sem revelar marcas; não precisa forçar GB antes da revelação. Maluca usa base de contorno/superfície na casa, mantendo a peça circular e o contador da pilha. `ultimate-board-cells` usa a mesma base em densidade compacta/ampliada; o invólucro do subtabuleiro recebe uma classe própria. As duas instâncias de Dados herdam a base e preservam `allowedColumn` e a rolagem.

### Catálogo de tokens propostos

Os nomes abaixo são **proposta**, não variáveis existentes. Os valores finais devem ser calibrados em navegador a partir dos valores atuais e do contraste medido. Atribuir uma cor semântica a cada conceito, sem criar uma nova cor para cada modalidade.

| Grupo | Tokens candidatos | Uso e relação com a base atual |
| --- | --- | --- |
| Superfícies | `--board-surface`, `--cell-surface`, `--cell-surface-muted`, `--cell-hover-surface` | Referenciar inicialmente `--ds-surface`, `--ds-elevated`, `--ds-surface-muted`, `--ds-hover`; variações de densidade podem trocar só superfície necessária. |
| Bordas | `--board-border`, `--cell-border`, `--cell-available-border`, `--cell-winning-border`, `--cell-restricted-border`, `--sub-board-border` | `--cell-border` parte de `--ds-board-border`; disponibilidade pode usar `--ds-primary`/sucesso conforme contexto; vitória e restrição têm nomes por função. |
| Jogadores | `--player-x`, `--player-o`, `--player-x-soft`, `--player-o-soft` | Alias de `--ds-x`/`--ds-o`; cores suaves por mistura controlada para conquista. X/O permanece visível como letra. |
| Conquista e vitória | `--line-progress-surface`, `--cell-winning-surface`, `--sub-board-captured-surface`, `--board-winning-border`, `--sub-board-draw-border` | Diferenciar progresso, vitória terminal, posse local X/O e vitória global. A cor de sucesso pode continuar comum para vitória, com X/O nas marcas. |
| Espessura e gap | `--cell-border-width`, `--board-border-width`, `--sub-board-border-width`, `--board-gap`, `--sub-board-gap` | Valores por densidade/tamanho efetivo; `--board-gap` no Grid, não margens por casa. |
| Raios | `--cell-radius`, `--board-radius`, `--sub-board-radius` | Reduzir em casas compactas; manter máximo menor que metade do vão percebido entre vizinhas. |
| Sombra e brilho | `--cell-shadow`, `--cell-hover-shadow`, `--cell-winning-glow`, `--selected-ring`, `--board-shadow` | O brilho de vitória e anel de seleção ocupam canais independentes; intensidades menores em grade compacta. |
| Interação e legibilidade | `--cell-focus-color`, `--cell-pressed-scale`, `--cell-unavailable-opacity`, `--cell-marker-size` | Foco usa `--ds-focus`; opacidade, se usada, afeta fundo/decoração sem apagar marca e rótulo; tamanho da marca acompanha a casa. |

Não mapear cada token para uma utility Tailwind. `@theme inline` é útil para tokens de cor compartilhados com outras partes do site; custom properties comuns são mais diretas para espessura, raio, sombra composta e valores que variam por contêiner. Não usar `dark:` com a semântica padrão de media query para estes elementos: o tema efetivo já é resolvido por `data-theme` no `html`.

Exemplo **ilustrativo, não implementado**, de estrutura CSS e HTML:

```css
html[data-design-system='experimental'] {
    --cell-border: var(--ds-board-border);
    --cell-focus-color: var(--ds-focus);
    --player-x: var(--ds-x);
    --player-o: var(--ds-o);
}

.ds-board-frame { container-type: inline-size; }
.ds-board-grid { display: grid; gap: var(--board-gap); }
.ds-board-cell {
    aspect-ratio: 1;
    min-width: 0;
    border: var(--cell-border-width) solid var(--cell-border);
    border-radius: var(--cell-radius);
    background: var(--cell-surface);
}
.ds-board-cell[data-mark='x'] { color: var(--player-x); }
.ds-board-cell[data-mark='o'] { color: var(--player-o); }
.ds-board-cell[data-winning-cell='true'] { border-color: var(--cell-winning-border); }
.ds-board-cell:focus-visible { outline: 3px solid var(--cell-focus-color); }
@media (hover: hover) and (pointer: fine) {
    .ds-board-cell:not(:disabled):hover { background: var(--cell-hover-surface); }
}
```

```blade
<div class="ds-board-frame">
    <div class="ds-board-grid" data-columns="3" data-density="regular">
        <button class="ds-board-cell" data-mark="x" data-winning-cell="true">X</button>
    </div>
</div>
```

O exemplo preserva a responsabilidade atual do Blade: ele entrega marca/estado e as ações existentes; a folha de estilo define a aparência. O wrapper é uma opção a introduzir somente onde necessário para obter um contêiner de medida correto. Seletores semânticos devem ter ordem e especificidade simples; na migração, remover utilitários que ainda escrevam a mesma propriedade.

## Tamanho real, responsividade e interação

1. Manter Grid com `minmax(0,1fr)` e casas `aspect-ratio: 1`. Medir a largura útil da grade, o número de colunas, os gaps e a largura efetiva das casas. Se `min-height` causar casa retangular ou overflow, reduzi-lo nas densidades pequenas e preservar a área de toque pela ampliação existente do Ultimate ou por mais espaço de layout, nunca por largura forçada.
2. Preferir três perfis de densidade baseados no papel visual: regular (3×3/4×4), denso (5×5 e Dados estreito) e compacto (Ultimate visão geral); Ultimate ampliado usa regular. Os perfis só ajustam medidas e efeitos, mantendo tokens de cor comuns. Eles podem ser refinados por `@container` na largura **do invólucro da grade**; `cqw` exige um ancestral com `container-type`, pois o próprio elemento não pode medir sua própria largura por query. Para pouca variação, `clamp()` com rem e limites é suficiente.
3. Definir gap, borda, raio e brilho com limites mínimos/máximos por perfil, conferindo em casas de aproximadamente 20–30 px do Ultimate compacto, 45–60 px de 5×5/Dados e 70–100 px de 3×3. Usar no máximo um nível discreto de brilho em compacto. Não aumentar espessura apenas porque a viewport desktop é larga se a grade está estreita num painel.
4. Preservar os limites atuais de altura em paisagem curta do `app.css`; container queries complementam esse caso, não substituem automaticamente o ajuste vertical. Verificar `scrollWidth <= clientWidth` sem esconder overflow legítimo.
5. Hover apenas para dispositivos que de fato possuem hover fino; `:focus-visible` permanece em todos os tamanhos e `:active` não substitui seleção persistente. Atributos `disabled` e `inert` seguem a lógica atual; cursor e aparência reforçam o estado. Respeitar `prefers-reduced-motion` nas transições e nas animações existentes.

## Precedência e composição dos estados

Separar eixos antes de escolher cores. **Conteúdo**: vazio, X, O, “?” e topo da pilha são mutuamente exclusivos. **Disponibilidade de ação**: jogável, aguardando/alvo não jogável, ocupada, fim da rodada e processamento são mutuamente exclusivos na interação, mas não apagam conteúdo. **Realce persistente**: selecionada, linha em progresso, conquistada, vencedora e empate local podem coexistir com conteúdo; alguns exigem prioridade no mesmo canal visual. **Informação complementar**: última jogada, peça antiga, restrição, coluna do dado, feedback temporário e foco devem usar selo, padrão de borda, ícone ou anel independente sempre que possível.

Para uma **casa**, resolver a superfície/borda principal nesta ordem: vitória terminal > seleção ativa > restrição/feedback temporário > disponibilidade > neutro. A marca X/O sempre conserva sua cor; casa ocupada e vencedora combina marca + realce, e casa ocupada desabilitada mantém legibilidade. O foco visível usa `outline` acima da hierarquia, sem ser removido por `disabled` ou por anéis. `:active` é transitório e só transforma controle operável. Peça antiga e última jogada devem preferir selo/traço auxiliar a tomar a borda vencedora. Em Duas Linhas, progresso usa fundo leve e pequena indicação de jogador; vitória terminal assume a borda principal. Em Maluca, tamanho da peça e contador de pilha permanecem legíveis sob vitória.

Para um **subtabuleiro Ultimate**, separar resultado (`open`, X, O, `draw`) de jogabilidade (`available`, `target`, indisponível) e apresentação (`selected`, `zoomed`, integrante da linha global). Resultado X/O/empate determina superfície e marca sobreposta; indisponibilidade pode adicionar tracejado/opacidade **somente no contorno**, sem apagar a posse. Seleção/alvo acrescenta contorno externo ou anel, sem trocar a cor de posse. A linha global vencedora assume o realce de vitória no contorno externo, mantendo X/O no interior. Subtabuleiro conquistado e indisponível, portanto, mostra as duas informações. Empate usa `=` e borda pontilhada; a seleção só existe para subtabuleiro disponível.

Implementar essa hierarquia em seletores semânticos ordenados ou variáveis CSS de canal (`--cell-current-border`, `--cell-current-surface`, `--cell-overlay-shadow`). Não somar `border-*`, `ring-*` e `shadow-*` utilitários concorrentes no Blade para o mesmo canal. Pseudo-elementos ou `outline` podem carregar realces complementares sem sobrescrever o estado base; garantir que não obstruam clique, foco ou texto.

## Temas claro, escuro e identidade dos jogadores

As views definem `data-design-system="experimental"` e um script no `<head>` lê `jogo-da-velha.theme` (`auto`, `light`, `dark`) antes do CSS. Em `auto`, `matchMedia('(prefers-color-scheme: dark)')` determina o **tema efetivo**; [`app.js`](../resources/js/app.js) atualiza `data-theme`, reage a mudanças do sistema e sincroniza o seletor. `app.css` define tokens claros no seletor base e sobrescreve os que mudam em `[data-theme='dark']`. Essa arquitetura deve continuar: tokens novos referenciam os valores `--ds-*` onde possível, e só recebem valores específicos por tema se contraste ou semântica exigirem.

Usar `--ds-x` e `--ds-o` como fonte das cores X/O para marcadores, peças e conquista. Vitória terminal pode usar o tom de sucesso comum, desde que a marca/letra/selo mantenha quem venceu. Disponibilidade, seleção e bloqueio devem ter indicação adicional à cor: borda contínua/tracejada, anel, “SAI”, ✦, `=`, texto ou rótulo ARIA já existente. Validar contraste da borda de casa contra cada superfície real, inclusive `--ds-elevated` em Ultimate e Maluca, e verificar foco claro/escuro.

## Exceções justificadas

- **Ultimate:** manter 81 casas na visão geral e a cópia interativa no zoom, com `wire:key` distintos. Não destacar individualmente a linha vencedora local; a conquista fica no subtabuleiro. `data-target`, `data-selected` e `data-zoomed` são estados diferentes. Preservar toque longo, supressão do clique posterior, Escape e foco.
- **Oculto:** manter “?” durante a partida e a animação de feedback por resultado de jogada. A base compartilhada não pode revelar X/O pelo `data-mark` interno. Ao encerrar, GB continua apresentando marcas e vitória.
- **Maluca:** manter pilha, tamanho de peça, contador, peça selecionada e estados do estoque (`used`, `waiting`, `available`, `finished`). A linha visível que ainda pode ser coberta não recebe a mesma aparência da vitória terminal.
- **Dados:** manter duas seções, coluna autorizada e `inert` durante a rolagem. O brilho da coluna deve ser discreto em casas menores; borda de vitória tem prioridade após a conclusão.
- **Movimento 3×3 e Rotação:** restrição de peça e remoção automática têm semânticas diferentes; seleção e selo “SAI” não devem virar estados globais de todas as modalidades.
- **Bloqueio:** o anel por um segundo é feedback de bloqueio de linha, não estado permanente de casa indisponível. Sua limpeza por Alpine continua necessária.
- **Duas Linhas e Pontuação:** uma linha formada com rodada em andamento é progresso somente em Duas Linhas; Pontuação mostra pontos fora das casas e não tem “casas vencedoras” no GB.

## Etapas de implementação propostas

| Etapa | Dependência | Mudança pequena e verificável | Saída esperada |
| --- | --- | --- | --- |
| A — Baseline | Aprovação deste plano | Registrar capturas e medidas representativas por tema/tamanho; catalogar classes conflitantes e valores computados. | Referência visual e limites de toque/overflow. |
| B — Tokens | A | Acrescentar tokens e aliases no `app.css`, sem mudar comportamento; decidir perfis de densidade a partir das medidas. | Tabela de valores claro/escuro e por densidade. |
| C — Base comum | B | Aplicar classes semânticas a GB e, se necessário, um wrapper de medida; migrar superfície, borda, raio, gap, sombra, foco e hover. | Clássico 3×3/4×4, Movimento, Rotação e 5×5 compartilham base. |
| D — Estados comuns | C | Migrar X/O, disponibilidade, desabilitado, seleção, vitória e progresso por atributos; remover utilitários conflitantes. | Precedência previsível no GB. |
| E — Variações existentes | C–D | Integrar Dados e a grade Oculto em partida; preservar `inert` e feedback. Integrar casa Maluca sem mexer no seletor/pilha. | Mesmo token-base em apresentações diferentes. |
| F — Ultimate e especiais | D–E | Aplicar base compacta/ampliada às casas Ultimate e base de subtabuleiro; harmonizar Bloqueio, peça antiga, restrição, conquista/empate/global. | Estados especiais legíveis e sem conflitos. |
| G — Revisão | C–F | Comparar capturas e medidas, corrigir temas, densidade, touch/hover, overflow e contraste; atualizar somente testes afetados. | Critérios de aceitação atendidos. |

Um commit por grupo de modalidades/estado facilita revisão e reversão. A sequência não exige mover regras do motor, trocar formato de componente Livewire, alterar dependências ou recriar todos os Blade. Evitar mudar a semântica de `data-testid`/`wire:key` durante a migração visual.

## Riscos, validação enxuta e critérios de aceitação

Riscos principais: (1) mudanças de especificidade CSS deixarem duas classes controlando a mesma borda; (2) container query medir o ancestral errado, especialmente em Dados/Ultimate; (3) `min-height` causar casa retangular ou rolagem horizontal; (4) `disabled`/opacidade ocultar marca ou vitória; (5) CSS de hover afetar toque; (6) atualização Livewire recriar nós e perder seleção/foco/feedback; (7) alterar contraste entre superfícies claras/escuras; (8) anel/sombra tapar o conteúdo das casas compactas.

Na implementação, reutilizar os testes existentes: [`GameBoardTest.php`](../tests/Feature/GameBoardTest.php), [`BoardBorderContrastBrowserTest.php`](../tests/Browser/BoardBorderContrastBrowserTest.php), [`ResponsiveLayoutTest.php`](../tests/Browser/ResponsiveLayoutTest.php) e testes de navegador das modalidades afetadas. Rodar testes funcionais específicos quando atributos, habilitação, ações, foco ou sequência de renderização mudarem. CSS puro não requer testes unitários novos; inspeção visual com capturas é a evidência principal. Não executar bateria completa do navegador após cada ajuste pequeno. A suíte completa fica para a etapa final conforme o fluxo do projeto.

A amostra visual mínima combina **360×800 e 1280×900, claro e escuro**, para: Clássico 3×3; Movimento ou Clássico 4×4; Duas Linhas ou Pontuação 5×5; Dados com dois tabuleiros; Ultimate com nove subtabuleiros, zoom, conquista e empate local; e uma mecânica especial (Movimento 3×3, Rotação, Bloqueio ou Oculto). Acrescentar **740×360 ou 800×360** em Dados, Maluca e Ultimate por causa dos ajustes existentes de paisagem curta. Inspecionar estados inicial, ocupado, disponível/indisponível, foco, seleção e vitória onde aplicável; não fabricar estados impossíveis só para preencher matriz.

Aceitar a padronização quando:

1. Uma mudança em token comum de borda, superfície, raio, gap, sombra ou cor X/O reflita nas modalidades que usam o mesmo papel visual, sem editar cada uma; exceções estejam nomeadas e justificadas.
2. A matriz de estados continue verdadeira: regras e ações Livewire/Alpine, dado, pilha, ocultação, bloqueio, rotação, movimento e direcionamento Ultimate funcionem como antes.
3. Casa e subtabuleiro mostrem estados simultâneos de forma determinística; X/O e foco permaneçam discerníveis, e vitória/posse não sumam por indisponibilidade.
4. Casas quadradas onde exigido, sem rolagem horizontal não intencional; borda/gap/raio/brilho proporcionais à casa efetiva; zoom Ultimate preserve a jogabilidade de áreas pequenas.
5. Hover não permaneça após toque; foco visível e teclado funcionem; estado desabilitado tenha sinal além do cursor; transições respeitem movimento reduzido.
6. Os dois temas preservem legibilidade e, nas superfícies representativas, pelo menos o contraste de contorno já medido na auditoria anterior; captura e medição nova confirmem o resultado final.
7. Build Vite, testes direcionados motivados pelas alterações e `git diff --check` passem. Nenhum teste de regra ou componente de jogo seja duplicado apenas para verificar CSS.

