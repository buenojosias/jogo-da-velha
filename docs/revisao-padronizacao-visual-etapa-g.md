# Etapa G — revisão final da padronização visual de tabuleiros

## Resultado e limites da verificação

Revisão de código **estática** na branch `feat/visual-board-tokens-baseline`, após as etapas A–F. Foram inspecionados `resources/css/app.css`, os componentes compartilhados de casas, os renderizadores de Ultimate/Oculto/Maluca/Dados e o feedback de Bloqueio. A verificação incluiu a presença dos atributos de estado, gestos e regras CSS correspondentes, além do equilíbrio estrutural de chaves do CSS.

**Não foi possível executar o aplicativo neste ambiente**: sem acesso de rede para clonar o repositório e sem execução local de PHP, Vite, Playwright ou dispositivo físico. Portanto, este documento **não confirma** contraste computado, ausência de overflow, aparência pixel a pixel, build ou aprovação de testes automatizados. O baseline anterior contém medições históricas, não medições repetidas agora.

## Correções realizadas nesta etapa

1. **Ultimate — casa ocupada/desabilitada**: a regra base `.ds-board-cell:disabled` aparecia depois da adaptação do Ultimate e podia substituir o fundo elevado, porque os seletores tinham especificidade equivalente. O seletor foi refinado para `.ds-ultimate-cells .ds-ultimate-cell:disabled`, preservando a superfície própria sem modificar `disabled` ou jogadas.
2. **Bloqueio — alerta temporário**: a sombra de bloqueio concorria com sombras de seleção e vitória. O destaque temporário passa a ser desenhado por `::after`, com `pointer-events: none`, mantendo os canais de foco, marca e sombras sem alterar os eventos Alpine. O contorno é de 2 px e não aumenta a borda real da casa.

## Revisões estáticas concluídas

| Área | Verificação |
| --- | --- |
| Tokens e temas | Aliases de superfícies, jogadores, bordas e estados permanecem associados aos `--ds-*` dos temas claro e escuro |
| Geometria | `.ds-board-grid` e `.ds-board-cell` compartilham tokens; Dados mantém dois tabuleiros com override de gap próprio; densidades regular/densa/compacta permanecem por contexto |
| Estado comum | `data-mark`, `data-winning-cell`, `data-selected`, `data-line-progress`, `data-restricted-cell` e metadados de peça antiga têm apresentação semântica |
| Ultimate | Atributos de resultado, disponibilidade, seleção e vitória global continuam separados; overlay X/O e empate permanecem; o zoom conserva toque longo e fechamento com Escape |
| Ultimate, casas | Visão geral e zoom usam `.ds-board-cell`, com densidade própria; não se destaca individualmente linha de conquista local |
| Oculto | A grade em partida conserva o conteúdo oculto e o feedback temporário; não foram alteradas regras de revelação |
| Maluca | Pilhas, tamanho das peças e estoque permanecem no Blade; apenas a base visual das casas usa os estilos compartilhados |
| Dados | Fluxo de rolagem, animação e bloqueio `inert` continuam no componente; CSS específico preservado |
| Bloqueio | Feedback transitório continua ativado e limpo por `data-block-feedback`, sem classes de anel adicionadas manualmente |
| Interação e movimento | Hover condicionado a `(hover: hover) and (pointer: fine)`; preferência por redução de movimento preservada |
| Estrutura CSS | Conferência estática de chaves balanceadas aprovada; não substitui parser/build Vite |

## Amostra manual mínima antes do merge

Executar no ambiente da aplicação, sem ampliar desnecessariamente a suíte de testes:

1. **360×800, claro e escuro**: Clássico 3×3 (vazio, ocupado, vitória); Dados (dois tabuleiros lado a lado, coluna disponível, casas vazias durante processamento); Ultimate (81 casas, subtabuleiro conquistado X/O, empate local, ampliação por toque longo e cancelamento).
2. **1280×900, claro e escuro**: Clássico, Movimento (seleção e peça restrita), Duas Linhas 5×5 (progresso vs. vitória), Maluca (pilha e vitória) e Oculto (feedback e revelação).
3. **740×360 ou 800×360**: conferir largura, altura, borda, espaço, legibilidade, rolagem e foco em Dados, Ultimate e Maluca.
4. **Bloqueio 4×4**: produzir uma linha bloqueada e confirmar o indicador transitório sem esconder X/O, anel de seleção ou foco.
5. **Hover/foco**: em mouse, hover somente nas casas operáveis; em smartphone, sem hover preso após toque; por Tab/Enter, foco visível e jogada funcionando.
6. **Medição**: repetir os contrastes de borda contra cada fundo real. Os antigos 3,79:1 (claro) e 5,03:1 (escuro) são referências históricas, não resultados desta revisão.

Depois, executar apenas build Vite, `git diff --check` e os testes existentes diretamente afetados (`GameBoardTest`, `BoardBorderContrastBrowserTest`, `ResponsiveLayoutTest` e cenários pontuais de Ultimate/Bloqueio quando necessários). Não criar testes de regra para estilos CSS.

## Status para integração

- **Revisão estática e correções pontuais: concluídas.**
- **Validação de execução, navegador e dispositivos físicos: pendente.**
- **Merge para `main`: ainda não realizado.**

Não considerar a etapa pronta para integração irrestrita sem pelo menos a amostra manual e o build local. Caso haja divergências visuais, ajustar primeiro a branch atual e comparar com este documento.
