# Auditoria das modalidades — Etapa 1

## Escopo e método

Esta auditoria registra o comportamento existente em 10 de outubro de 2026. Nenhuma regra, view, estilo, dependência ou fluxo de jogo foi alterado. O levantamento foi feito a partir de `routes/web.php`, das views Blade, dos componentes, de `public/js/game.js`, dos manifestos de dependências e da renderização HTTP coberta por Pest.

Há 14 modalidades públicas, todas acessíveis por `GET` e todas listadas na página inicial. A rota adicional `/teste` não é uma modalidade e está quebrada porque referencia uma view inexistente.

## Inventário de rotas e modalidades

| Modalidade | Rota | Nome | View | Tabuleiro e condição atual |
| --- | --- | --- | --- | --- |
| Clássico 3×3 | `/3-basico` | `3-basic` | `3-basic` | 3×3; vence com qualquer linha clássica de 3; empate ao preencher as 9 casas. |
| Movimento 3×3 | `/3-mover` | `3-move` | `3-move` | Cada jogador coloca 3 peças e depois move uma peça própria para qualquer casa vazia; linha de 3 vence. |
| Rotação 3×3 | `/3-rotacao` | `3-rotation` | `3-rotation` | Cada jogador mantém 3 peças; toda nova colocação remove automaticamente a peça própria mais antiga; linha de 3 vence. |
| Clássico 4×4 (3 peças) | `/4-3-basico` | `4x3-basic` | `4x3-basic` | 4×4; qualquer segmento horizontal, vertical ou diagonal de 3 vence; empate ao preencher as 16 casas. |
| Movimento 4×4 (3 peças) | `/4-3-mover` | `4x3-move` | `4x3-move` | Cada jogador coloca 3 peças e depois move uma delas para qualquer casa vazia; segmento de 3 vence. |
| Rotação 4×4 (3 peças) | `/4-3-rotacao` | `4x3-rotation` | `4x3-rotation` | Cada jogador mantém 3 peças; a mais antiga é removida ao colocar outra; segmento de 3 vence. |
| Bloqueio 4×4 | `/4-3-bloqueio` | `4x3-block` | `4x3-block` | Segmento de 3 só vence quando não há peça adversária na continuação de uma das pontas; empate ao preencher o tabuleiro. |
| Oculto 4×4 | `/4-3-oculto` | `4x3-hidden` | `4x3-hidden` | Peças ficam invisíveis durante a partida; cada jogador mantém até 5 peças e depois gira a mais antiga; linha de 3 vence. |
| Linha de 4 com Movimento | `/4-mover` | `4x4-move` | `4x4-move` | Cada jogador coloca 4 peças e depois move uma delas para qualquer casa vazia; linha completa de 4 vence. |
| Duas Linhas 5×5 | `/2-linhas` | `5x5-2-lines` | `5x5-2-lines` | Vence ao formar pelo menos duas linhas de 3; janelas sobrepostas e conectadas na mesma direção contam como uma linha contínua. |
| Pontuação 5×5 | `/5-cheia` | `5-full` | `5-full` | O tabuleiro é preenchido por completo; sequências máximas de 3, 4 e 5 ou mais valem 3, 6 e 10 pontos; vence a maior pontuação. |
| Dados da Velha | `/dado` | `dice` | `dice` | Dois tabuleiros 3×3 representam seis colunas globais; o dado escolhe a única coluna permitida; linha de 3 em qualquer tabuleiro vence. |
| Conquista Maluca | `/maluca` | `crazy` | `crazy` | 3×3 com peças empilháveis: cada jogador possui 3 pequenas, 3 médias e 3 grandes; uma peça só cobre outra menor. |
| Ultimate Tic-Tac-Toe | `/ultimate` | `ultimate` | `ultimate` | Nove subtabuleiros 3×3; a casa jogada determina o próximo subtabuleiro; três subtabuleiros vencidos em linha ganham a partida. |

### Detalhes das regras

- Nas modalidades de movimento, a casa de destino só precisa estar vazia. Não existe restrição de adjacência.
- Nas modalidades de rotação, a ordem das peças é mantida em filas por jogador (`placedPiecesOrder`). A peça mais antiga recebe destaque visual antes de ser removida.
- Clássico 3×3, Clássico 4×4, Bloqueio 4×4 e Duas Linhas 5×5 declaram empate quando todas as casas estão ocupadas. As modalidades de movimento e rotação não têm detecção de empate ou repetição e podem continuar indefinidamente.
- Em Oculto 4×4, clicar numa peça adversária apenas consome o turno. Clicar numa peça própria a move aleatoriamente para uma casa vazia. Ao clicar numa casa vazia, o jogador coloca peças até chegar a cinco; depois disso, a mais antiga é removida. As peças só são reveladas ao finalizar uma vitória.
- Em Duas Linhas 5×5, os trios vencedores são agrupados por direção quando compartilham casas. Assim, vários trios sobrepostos de uma sequência longa contam como uma linha, não como várias. A vitória exige dois grupos direcionais.
- Em Pontuação 5×5, `getSequences()` encontra sequências máximas horizontais, verticais e nas duas diagonais. A pontuação é recalculada após cada jogada, mas a partida termina somente quando as 25 casas estão preenchidas.

## Particularidades de Dados, Maluca e Ultimate

### Dados da Velha

- É uma página HTML autônoma com CSS e JavaScript inline; não usa o layout, componentes, Tailwind ou `public/js/game.js`.
- Possui dois estados 3×3. As colunas 1–3 pertencem ao tabuleiro esquerdo e as colunas 4–6 ao direito.
- Uma rolagem escolhe de 1 a 6. Se a coluna sorteada estiver cheia, o jogador perde a vez. Caso contrário, somente uma casa vazia dessa coluna pode receber a peça.
- A vitória é a regra clássica de 3 em linha dentro de um dos tabuleiros. O empate só ocorre quando os dois tabuleiros estão totalmente preenchidos.
- O jogador inicial é guardado em `localStorage` pela chave `starterPlayer`; o primeiro valor é aleatório e o reinício alterna e persiste o próximo jogador.
- O dado usa uma animação de 1 segundo e libera o número sorteado após 1,1 segundo.

### Conquista Maluca

- Também é uma página autônoma, com CSS e JavaScript inline.
- Cada casa guarda uma pilha. A peça visível é sempre a última da pilha e uma nova peça só pode cobri-la se for estritamente maior.
- Uma linha visível não encerra imediatamente o jogo se o adversário ainda tiver alguma peça não usada grande o bastante para cobrir pelo menos uma das três peças da linha.
- Sem possibilidade de cobertura, a linha vence. Se as 18 peças forem usadas sem vitória definitiva, ocorre empate.
- O jogador inicial usa `localStorage` com a chave `crazy_tictactoe_starter`; `restart()` alterna o valor antes de reinicializar.

### Ultimate Tic-Tac-Toe

- É a terceira página autônoma, com CSS e JavaScript inline.
- Cada subtabuleiro mantém suas nove casas, vencedor e estado de empate. O tabuleiro principal guarda os vencedores dos nove subtabuleiros.
- A posição escolhida dentro de um subtabuleiro define o índice do subtabuleiro obrigatório na próxima jogada. Quando o destino já está vencido ou empatado, todos os subtabuleiros ainda disponíveis são liberados.
- Uma linha clássica de três vencedores no tabuleiro principal vence. A partida empata quando todos os subtabuleiros estão vencidos ou empatados sem vencedor global.
- A chave `ultimateTicTacToeStarter` guarda o próximo jogador inicial. `initializeGame()` lê a chave e já persiste o jogador oposto, inclusive no carregamento inicial da página.

## Componentes Blade compartilhados

| Componente | Responsabilidade e uso atual |
| --- | --- |
| `components.layout` | Estrutura comum, Vite, título, status, slot do jogo, carregamento de `js/game.js` e scripts específicos. Usado por 11 modalidades. |
| `components.board` | Gera 9, 16 ou 25 células dentro de `#board`. Usado pelas mesmas 11 modalidades. |
| `components.playing` | Status textual e ícones X/O do jogador atual; incluído pelo layout. |
| `components.footer` | Placar, botão de reinício, ajuda e saída; usado diretamente pelas 11 modalidades. |
| `components.modal-win` | Modal de vitória com troféu, mensagem, ícone do vencedor e reinício; usado pelas 11 modalidades. |
| `components.x` / `components.o` | SVGs parametrizados de X e O; usados no status, placar e indiretamente no modal. |
| `components.trophy` | SVG do troféu exibido pelo modal. |
| `components.crazy-selection` | Protótipo de seletor de peças X/O; não é referenciado por nenhuma view atual. A modalidade Maluca possui implementação autônoma própria. |

Dados, Maluca e Ultimate não reutilizam nenhum desses componentes. A home também possui documento HTML próprio, mas usa Vite e Tailwind.

## JavaScript compartilhado e específico

### Núcleo compartilhado em `public/js/game.js`

- Estado global: `board`, `currentPlayer`, `gameOver`, `score` e `startingPlayer`.
- `xSVG()` e `oSVG()`: geram os símbolos usados nas células e no modal.
- `toggleStartingPlayer()`: alterna o jogador inicial entre partidas.
- `updateScoreboard()`: reflete o placar acumulado da sessão no rodapé.
- `finishGame()`: encerra, pontua, destaca todas as combinações recebidas e abre o modal.
- `checkWinner()`: verificação genérica de combinações de três posições.
- `updatePlayerIcons()`: alterna os ícones do status.
- `resetBoardUI()`: limpa conteúdo e classes padrão, aceitando classes extras por modalidade.
- `renderBoard()`: desenha símbolos, chama o gancho global opcional `onCellRender()` e bloqueia células ao fim.
- Um listener delegado em `#board` chama o gancho `handleMove()` da modalidade.
- Os dois botões de reinício alternam o jogador inicial e chamam o gancho `startGame()`.

As views do layout declaram funções globais com nomes convencionados. Algumas substituem funções do núcleo por declaração de mesmo nome: Oculto redefine `finishGame()`, Duas Linhas redefine `checkWinner()`, Linha de 4 redefine `checkWinner()`, e Pontuação redefine `updateScoreboard()` e `finishGame()`.

### Funções específicas

| Grupo | Funções e estado específico |
| --- | --- |
| Clássicos | `startGame`, `onCellRender`, `handleMove`, `updateStatus`; combinações fixas por tamanho. |
| Movimento | As anteriores mais `switchPlayer`, `placedPieces` e `selectedPiece`; seleção e transferência de peças após a fase inicial. |
| Rotação | As anteriores mais `placedPiecesOrder`; remoção FIFO da peça mais antiga. |
| Bloqueio | `getBlockers()` calcula peças adversárias nas extensões e `checkWinner(newPieceIndex)` limita a busca aos trios da última jogada. |
| Oculto | `onCellRender()` oculta SVGs; `finishGame()` revela tudo; `handleMove()` contém as regras de turno perdido, deslocamento aleatório e rotação. |
| Duas Linhas | `winningCombinationsByDirection` e `checkWinner()` agrupam trios sobrepostos por direção. |
| Pontuação | `calculateScore`, `getSequences`, `highlightNewSequences` e versões próprias de placar/finalização. |
| Dados | `createBoards`, `rotateDice`, `columnHasFreeCell`, `checkWinner`, `checkDraw`, `highlightWin`, `restartGame`. |
| Maluca | `init`, `createPieces`, `renderBoard`, `renderPieces`, `selectPiece`, `sizeValue`, `placePiece`, `visibleOwner`, `checkGame`, `highlightWinner`, `restart`. |
| Ultimate | `createEmptyBoard`, `getSavedStarter`, `saveNextStarter`, `initializeGame`, `render`, `isBoardPlayable`, `makeMove`, `checkWinner`, `showWinner`, `showDraw`, `restartGame`. |

## Sistemas de layout, inicialização e reinício

Existem três famílias técnicas:

1. **Layout Blade compartilhado (11 modalidades):** usa Tailwind/Vite, componentes, o núcleo global `public/js/game.js` e scripts inline específicos. A view chama `startGame()` imediatamente. O jogador inicial é aleatório a cada carregamento e alterna em memória em cada reinício. O placar de vitórias permanece enquanto a página não for recarregada; Pontuação 5×5 substitui esse placar pelo valor da partida corrente.
2. **Páginas autônomas (Dados, Maluca e Ultimate):** cada arquivo contém documento, CSS, DOM e JavaScript próprios. Não há placar compartilhado nem botão de saída. O jogador inicial é persistido em chaves diferentes de `localStorage`, e cada modalidade tem semântica própria de reinício.
3. **Catálogo:** `welcome.blade.php` é autônoma em estrutura, mas usa Vite/Tailwind e apenas apresenta links para todas as 14 modalidades.

No layout comum, `js/game.js` é referenciado diretamente, fora do Vite e sem o helper `asset()`. Os scripts específicos dependem de variáveis e funções globais, da ordem de carregamento e de IDs fixos no DOM. Isso é uma fronteira de acoplamento importante para a modernização.

## Dependências instaladas

### Runtime PHP

- PHP 8.4; o manifesto aceita `^8.3`.
- Laravel Framework 13.35.0.
- Laravel Tinker 3.0.2.

### Desenvolvimento PHP

- Pest 4.7.8 e Pest Plugin Laravel 4.1.0.
- Laravel Boost 2.10.3, Pint 1.32.1, Pail 1.2.7 e PAO 1.1.5.
- FakerPHP 1.24.1, Mockery 1.6.15 e Collision 8.9.5.
- `pestphp/pest-plugin-browser` não está instalado.

### Frontend

- Tailwind CSS 4.3.0 e `@tailwindcss/vite` 4.3.0.
- Vite 8.0.14 e Laravel Vite Plugin 3.1.0.
- Concurrently 9.2.1.
- Na Etapa 1, Livewire ainda não estava instalado. A instalação posterior está registrada em `docs/modernizacao-etapa-2-livewire.md`.

## Problemas confirmados e riscos técnicos

### Problemas preexistentes solicitados

1. **`/teste` sem view:** `routes/web.php` registra `Route::view('/teste', 'teste')`, mas não existe `resources/views/teste.blade.php`. A resolução resulta em `View [teste] not found`. Essa rota ficou fora do conjunto das 14 modalidades e não foi corrigida.
2. **Geometria diagonal no Bloqueio:** `getBlockers()` impede mudança de linha apenas quando `direction === 1`. Nas diagonais de passo 3, índices aritmeticamente válidos podem estar fora da continuação geométrica. Por exemplo, para `[2,5,8]`, o índice `11` é tratado como extensão posterior, embora esteja na coluna 3 e não na coluna -1; para `[3,6,9]`, o índice `0` é aceito como extensão anterior apesar de a diagonal já começar na borda direita. Isso pode bloquear vitórias com peças que não estão na mesma diagonal.
3. **Mensagem da Pontuação 5×5:** o componente do modal já renderiza “Jogador”, o contêiner do ícone e “venceu!”. `finishGame()` cria `#win-message` depois do ícone contendo novamente “Jogador X venceu!”, “Jogador O venceu!” ou “Empate!”. O resultado combina fragmentos incompatíveis e duplica a frase de vitória.
4. **IDs duplicados em Dados:** há dois elementos aninhados com `id="status"`. `getElementById('status')` seleciona o primeiro e o segundo permanece inválido e sem função.
5. **Rolagens simultâneas em Dados:** o clique só testa `gameOver` e `currentColumn`. Como `currentColumn` só recebe o resultado dentro de um `setTimeout` de 1,1 segundo, cliques adicionais durante a animação agendam novas rolagens e callbacks. Não existe flag `isRolling` nem desabilitação temporária do botão.

### Riscos adicionais observados

- O título de Clássico 4×4 contém um parêntese excedente: `Clássico 4x4 (3 peças))`.
- A lógica central está em globais e em scripts inline; redefinições por nome e ordem de scripts tornam o comportamento difícil de isolar e testar.
- As três páginas autônomas duplicam estrutura, estilos, estado, sorteio de jogador inicial, detecção clássica de vitória e reinício.
- A persistência do início é inconsistente: memória efêmera no layout comum e três estratégias/chaves de `localStorage` nas páginas autônomas.
- Ultimate avança o jogador inicial persistido no simples carregamento/recarregamento, não apenas quando o usuário solicita nova partida.
- Não há regra de encerramento para repetição ou impossibilidade de progresso nas modalidades de movimento/rotação.
- O componente `crazy-selection` não participa da implementação atual e pode gerar falsa impressão de compartilhamento.
- O JavaScript não está modularizado pelo pipeline do frontend, portanto não existe uma unidade importável para testes sem primeiro refatorar ou executar um navegador real.

## Cobertura de testes implementada

O arquivo `tests/Feature/ModalityPagesTest.php` acrescenta 39 cenários Pest:

- 14 cenários exercitam os caminhos HTTP das modalidades, exigem resposta bem-sucedida, confirmam a view retornada com `assertViewIs`, verificam o título e marcadores estruturais específicos de cada modalidade.
- 11 cenários confirmam a quantidade exata de células renderizadas nas modalidades do layout compartilhado: 9, 16 ou 25 conforme o jogo.
- 14 cenários confirmam que o catálogo inicial contém um link para cada rota de modalidade.

Os marcadores cobrem tabuleiro, status, reinício, modal e ganchos de regra nas modalidades compartilhadas; dois tabuleiros, dado e botão de rolagem em Dados; tabuleiro e reservas X/O em Maluca; e tabuleiro principal, jogador, destino e overlay em Ultimate.

A suíte original tinha 2 testes e 2 asserções, ambos aprovados antes das alterações. O teste focal novo foi aprovado com 39 testes e 176 asserções. Ao final, a suíte completa foi aprovada com 41 testes e 178 asserções. Não houve falha introduzida pelo trabalho.

## Testes pendentes e limitações

Resposta HTTP 200, `assertViewIs` e inspeção do HTML confirmam roteamento e renderização no servidor, mas não executam JavaScript. Portanto, ainda precisam de testes em navegador real:

- jogadas válidas e inválidas de cada modalidade;
- todas as combinações de vitória, empate, movimento, rotação e bloqueio;
- pontuação de sequências 3/4/5 e composição visual da mensagem final;
- ocultação, revelação e deslocamento aleatório em Oculto;
- seleção, cobertura, vitória contestável e empate em Maluca;
- restrição de coluna, coluna cheia, corrida de múltiplas rolagens e reinício em Dados;
- direcionamento entre subtabuleiros, liberação de destino encerrado e vitória global em Ultimate;
- contagem de IDs únicos e comportamento real dos seletores DOM;
- persistência e alternância do jogador inicial em recarga e reinício;
- regressão visual e responsividade dos dois sistemas de layout.

Pest 4 suporta testes de navegador, mas o projeto não possui `pestphp/pest-plugin-browser` nem o navegador correspondente. Conforme a restrição desta etapa, nenhuma dependência foi adicionada. Refatorar regras em funções importáveis também permitiria testes unitários, mas isso mudaria a organização do código e pertence à próxima etapa.

A rota `/teste` é um defeito conhecido fora das 14 modalidades. Um teste que exija sucesso nela falharia por `View [teste] not found`; o problema foi documentado, sem congelar a falha como comportamento esperado e sem criar uma view vazia para mascará-la.

## Recomendações para a próxima etapa

1. Corrigir explicitamente os cinco defeitos confirmados, cada um acompanhado por um teste de regressão que falhe antes da correção.
2. Definir contratos PHP puros para tabuleiro, jogada, resultado e placar antes de integrar Livewire, preservando em testes as regras levantadas neste documento.
3. Separar regras comuns (combinações, alternância, empate, sequências e placar) das políticas específicas (movimento, FIFO, bloqueio, ocultação, dado, cobertura e Ultimate).
4. Escolher uma política única para jogador inicial, reinício, placar entre rodadas e persistência local.
5. Unificar o esqueleto visual e os componentes de status, tabuleiro, ações e resultado antes de implementar temas claro/escuro.
6. Adotar testes unitários para as classes de regra e testes Livewire para estado/interações; reservar testes de navegador para JavaScript, `localStorage`, temporizadores, animações e regressão visual.
7. Manter fixtures de cenários de tabuleiro com resultados conhecidos, principalmente para diagonais de Bloqueio, agrupamento de Duas Linhas, pontuação 5×5, cobertura da Maluca e direcionamento Ultimate.
8. Decidir se `/teste` deve ser removida ou receber uma finalidade real, em vez de criar uma view vazia sem requisito funcional.
