# Baseline visual para padronização dos tabuleiros — Etapas A e B

## Escopo e origem das evidências

Registro de referência **estático**, elaborado a partir do levantamento em [plano-padronizacao-visual-tabuleiros.md](plano-padronizacao-visual-tabuleiros.md), da [auditoria-visual-etapa-17.md](auditoria-visual-etapa-17.md) e do CSS vigente. Não foram realizadas novas medições em navegador nem capturas neste registro; números abaixo são **históricos** e devem ser revalidados na migração visual.

O baseline cobre as 14 modalidades inventariadas no plano. As observações históricas mais úteis incluem Clássico 3×3, 4×4, 5×5, Dados, Maluca, Oculto e Ultimate, nos contextos 360×800, 1280×900 e paisagem curta 740×360/800×360. A auditoria antiga contém constatações já superadas por mudanças posteriores (por exemplo, o empilhamento de Dados e a rolagem para o ampliado de Ultimate): **não devem ser adotadas como comportamento atual desejado**.

## Referências e invariantes a preservar

| Elemento | Referência conhecida | Implicação para próximas etapas |
| --- | --- | --- |
| Contraste de contorno | Auditoria após correção: casa clara 3,79:1 e escura 5,03:1 contra superfície comum; Ultimate ampliado escuro 3,80:1 | Não degradar o contraste; repetir medição nos fundos realmente utilizados |
| GB compartilhado | 3, 4 ou 5 colunas; casas com borda de 2px, `gap-2 sm:gap-3`, raio de controle | Comparar computados atuais antes de ativar os tokens |
| Dados | Dois tabuleiros lado a lado inclusive no smartphone; `gap: clamp(0.25rem,0.8vw,0.75rem)` | Não retornar ao empilhamento nem provocar overflow |
| Ultimate | Nove subtabuleiros 3×3 e toque longo para ampliação flutuante; casas compactas com borda de 1px | Não destacar a linha local em cada casa; preservar conquista do subtabuleiro |
| Maluca | Nove pilhas com peças de tamanhos diferentes e estoque separado | Preservar composição, seleção e legibilidade dos contadores |
| Oculto | Casas exibem “?” até o fim e feedback transitório | Não revelar conteúdo pela padronização |
| Movimento / Rotação | Seleção, restrição e selo “SAI” | Conservar informação adicional sem conflito com contorno de vitória |
| Duas Linhas | Linha formada continua em destaque na rodada | Distinguir progresso de vitória terminal |

## Critérios leves de comparação nas etapas C–G

- Usar capturas existentes como orientação histórica, não como prova do estado atual.
- Na primeira mudança visual, comparar estado inicial e um estado especial em 360×800 e 1280×900, claro e escuro, em Clássico, Dados e Ultimate; ampliar amostras somente quando uma modalidade for migrada.
- Inspecionar largura/altura quadrada da casa, gap, borda, raio, marca, hover (mouse fino), foco por teclado e ausência de overflow horizontal.
- Para paisagem curta, conferir 740×360 ou 800×360 quando Dados, Maluca ou Ultimate forem tocadas.
- CSS sem alteração comportamental não exige nova bateria de testes de regras. Validar sintaxe/build quando houver ambiente de execução; anotar indisponibilidade quando não houver.

## Etapa B — tokens preparados, ainda sem consumidores

Os tokens foram adicionados ao `resources/css/app.css`, sob `html[data-design-system='experimental']`. Nenhum Blade foi alterado, nenhuma classe compartilhada nova foi aplicada, e os seletores legados mantêm sua prioridade atual.

**Fundamentos:** `--board-*`, `--cell-surface`, `--cell-border`, `--cell-focus-color`, `--player-x`, `--player-o` referenciam `--ds-*`, permitindo mudanças globais e adaptação automática à paleta do tema atual.

**Semântica:** `--cell-available-border`, `--cell-selected-border`, `--cell-winning-*`, `--cell-restricted-border`, `--line-progress-surface` e `--sub-board-*` nomeiam papéis, não cores literais. Estados compostos devem usar canais independentes para fundo, contorno, foco e adornos.

**Geometria candidata:** `--cell-border-width`, `--board-gap`, `--cell-radius`, sombras e anéis têm perfis regulares, densos e compactos, opt-in por `data-density`. São valores **iniciais, não medidos nem aprovados para ativação**; a etapa C deve calibrar contra o tamanho efetivo de cada casa e contra os espaçamentos existentes. A densidade não é definida rigidamente por rota.

**Escopo futuro:** não mapear todos os tokens em `@theme`; custom properties são suficientes para borda, raio, composição de sombra e propriedades que dependam do contêiner. As cores continuam utilizando o `data-theme` efetivo, sem forçar `dark:` ou mídia de esquema de cor no CSS dos tabuleiros.

## Estado da entrega

- Etapa A: baseline documental a partir de evidências existentes, sem capturas novas.
- Etapa B: vocabulário CSS preparado, sem uso pelos tabuleiros atuais.
- Sem modificação de Blade, Livewire, Alpine ou regras.
- Próxima etapa: integrar uma primeira grade `game-board` usando os tokens e medir visualmente, removendo classes conflitantes apenas na migração.
