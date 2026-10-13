# Auditoria visual e de usabilidade — etapa 17.1C

## Resumo executivo

Inspeção **diagnóstica em Chromium real (Playwright, sem executar a suíte)**. As 14 rotas públicas abriram em 1280×900, sem exceções JavaScript de inicialização nem rolagem horizontal. A linguagem visual compartilhada é consistente e os estados amostrados continuaram jogáveis. Foram encontrados **2 achados P1, 2 P2 e nenhum P3**. A cobertura abaixo não equivale a uma auditoria WCAG ou à validação integral das regras.

## Cobertura efetiva

`L` = tema claro; `E` = escuro; `A` = automático com aparência clara. Todas as páginas da tabela foram vistas em 1280×900 (L); as demais combinações são explicitadas. “Inicial” significa apenas a primeira apresentação da rodada.

| Modalidade pública | Amostras adicionais observadas | Estados/interações realmente observados |
| --- | --- | --- |
| `/3-basico` | 360×800 L/E; 740×360 L | Inicial, turno alternado, vitória, empate, cartões finais, resultado, confirmação, Escape, foco e Enter numa casa. |
| `/4-3-basico` | 360×800 L; 740×360 L | Inicial. |
| `/3-mover` | 360×800 L; 740×360 L | Inicial, seis colocações, instrução de movimento e seleção com `aria-pressed`. |
| `/4-3-mover` | — | Inicial. |
| `/4-mover` | — | Inicial. |
| `/3-rotacao` | — | Inicial. |
| `/4-3-rotacao` | 360×800 L; 740×360 L | Inicial, seis colocações e indicação das peças mais antigas. |
| `/4-3-bloqueio` | — | Inicial. |
| `/4-3-oculto` | 360×800 L; 740×360 L | Inicial e uma escolha; casas permaneceram com “?” e rótulos equivalentes. |
| `/2-linhas` | 360×800 L; 740×360 L; 800×360 L | Inicial e instrução. |
| `/5-cheia` | 360×800 L; 740×360 L; 800×360 L | Inicial e cinco colocações; pontos atualizados sem resultado prematuro. |
| `/dado` | 360×800 L/E; 740×360 L; 800×360 A | Inicial e uma rolagem, coluna autorizada e botão desabilitado enquanto a jogada estava pendente; confirmação em 800×360. |
| `/maluca` | 360×800 L/E; 740×360 L/A; 800×360 A | Inicial, seleção, colocação, estoque após uso e confirmação em 800×360. |
| `/ultimate` | 360×800 L/E; 740×360 L/A; 800×360 A | Inicial, seleção de subtabuleiro por ponteiro/teclado e confirmação em 800×360. |

Além das capturas individuais temporárias, foram inspecionadas folhas de contato das 14 páginas em desktop e das dez páginas amostradas em celular/paisagem. Em 360 px, casas convencionais medidas ficaram entre 54 e 95 px; em 740 px, entre 46 e 73 px. Dados apresentou 18 casas em dois tabuleiros, Maluca nove casas e 18 botões de peça, e Ultimate nove subtabuleiros e 81 casas no DOM (somente o subtabuleiro ampliado escolhido fica visível para interação).

## Achados priorizados

### P1 — Contorno das casas vazias com contraste baixo

- **Evidência:** em `/3-basico`, 360×800, estado inicial, medições das cores computadas no Chromium: borda `rgb(201,198,223)` sobre branco = **1,66:1** no tema claro; borda `rgb(104,99,126)` sobre `rgb(36,35,58)` = **2,67:1** no escuro. As casas vazias têm o mesmo fundo do painel, tornando a borda seu principal limite visível. O padrão de borda é compartilhado por [`game-board`](../resources/views/components/design-system/game-board.blade.php) e pelos tokens em [`app.css`](../resources/css/app.css).
- **Impacto:** identificação das áreas clicáveis pode ficar difícil para pessoas com baixa visão. Ambos os valores ficam abaixo da referência de 3:1 normalmente usada para limites de controles; isto **não é** uma declaração de conformidade WCAG do conjunto da página.
- **Recomendação:** testar um token de borda de casas com maior contraste nos dois temas, sem alterar indiscriminadamente todas as bordas do sistema; conferir estados ocupado, foco e desabilitado em 3×3, 4×4 e 5×5.

### P1 — Ultimate separa seleção e jogada ampliada em paisagem curta

- **Evidência:** em `/ultimate`, 740×360 L, a região ampliada começa a **718 px** do topo (a 778 px em 800×360). Após selecionar o subtabuleiro 5, o foco permanece no botão da visão geral e a região ampliada ainda começa a **554 px abaixo do topo visível**. Pelo teclado, selecionar o subtabuleiro 1 exige **nove Tab** até a primeira casa ampliada. A escolha muda `aria-pressed`, mas não aproxima a área de jogada. [Captura após seleção](auditoria-visual-ultimate-740x360.png). Código envolvido: [`⚡ultimate-match.blade.php`](../resources/views/components/⚡ultimate-match.blade.php).
- **Impacto:** a ação central exige rolagem e navegação repetidas; a grade pequena da visão geral parece conter casas jogáveis, mas seus quadrados são apenas representação visual dentro de um único botão por subtabuleiro.
- **Recomendação:** ao escolher um subtabuleiro, levar foco e posição de rolagem para a primeira casa disponível da área ampliada, preservando retorno à visão geral e comportamento de leitores de tela. Validar em 360×800, 740×360 e 800×360.

### P2 — Maluca não diferencia visualmente peça gasta de peça fora de turno

- **Evidência:** em `/maluca`, após uma colocação em 740×360 A, a peça usada e outra peça ainda disponível do jogador anterior estavam ambas `disabled`, com as mesmas classes e `opacity: 0.35`. Apenas o `aria-label` da peça usada acrescentava “usada”. [Captura após a colocação](auditoria-visual-maluca-740x360.png). Código envolvido: [`⚡crazy-match.blade.php`](../resources/views/components/⚡crazy-match.blade.php).
- **Impacto:** a disponibilidade das 18 peças, informação central dessa modalidade, fica ambígua para quem depende da apresentação visual.
- **Recomendação:** separar visualmente “já usada” de “temporariamente indisponível por turno”, mantendo os rótulos acessíveis e sem alterar o inventário do motor.

### P2 — Maluca exige ida e volta por rolagem na paisagem

- **Evidência:** em `/maluca`, 740×360 L, o tabuleiro ocupa a faixa vertical aproximada de **166–577 px**, enquanto o seletor começa em **239 px** e termina em **621 px**; a página mede **749 px** de altura. As peças do fim do seletor e as casas superiores não cabem simultaneamente na viewport. A rolagem funciona e não há overflow horizontal. Código envolvido: [`⚡crazy-match.blade.php`](../resources/views/components/⚡crazy-match.blade.php) e ajustes de paisagem em [`app.css`](../resources/css/app.css).
- **Impacto:** cada jogada pode exigir rolar até a peça e retornar ao tabuleiro. Não é conteúdo inacessível, mas aumenta o esforço numa interação repetitiva.
- **Recomendação:** avaliar uma composição mais compacta ou uma aproximação contextual do seletor e tabuleiro em telas baixas, sem diminuir os botões de 44 px medidos.

## Pontos positivos e observações sem defeito confirmado

- O `h1` acessível identificou corretamente a modalidade em todas as 14 rotas; a identidade visível permaneceu consistente. No Clássico, os cartões mostraram “É a vez”/“Aguardando” durante a rodada, “Venceu”/“Rodada encerrada” na vitória e “Empate” para ambos no empate.
- No Clássico, Tab exibiu contorno de foco sólido de 3 px no seletor e nas casas; Enter realizou uma jogada. Em 740×360, resultado e confirmação couberam na viewport, Escape fechou ambos e o foco voltou a um controle externo ao diálogo após o ciclo de fechamento. Confirmações de Dados, Maluca e Ultimate também couberam em 800×360, com ações de 48 px e retorno de foco.
- Texto amostrado em claro/escuro apresentou estes contrastes medidos: nome da modalidade **5,87:1 / 6,33:1**, legenda do participante **8,58:1 / 9,76:1**, status **12,71:1 / 9,47:1**, botão principal **5,87:1 / 6,98:1**. As medições são pares pontuais de cores computadas, não uma avaliação integral de contraste.
- Os tabuleiros 5×5 mantiveram casas de 54 px em 360×800 e 46 px em 740×360. A última fileira pode exigir rolagem vertical curta (altura da página: 414 px em Duas Linhas e 504 px em Pontuação, para viewport de 360 px); não foi observado corte nem rolagem horizontal.
- Dados empilha os tabuleiros em 360 px, gerando página de 1300 px; a coluna sorteada recebeu indicação textual e visual, e o controle de nova rolagem ficou indisponível durante a jogada pendente. A rolagem vertical aqui foi funcional, não classificada como defeito.
- Rotação identificou as peças mais antigas por texto e símbolo; Movimento expôs seleção via `aria-pressed`; Oculto manteve marcas fora da apresentação observada durante a rodada. Essas observações são de interface, não uma nova validação das regras PHP.

## Plano de correção para 17.2

1. **Componente compartilhado:** ajustar apenas contraste de contorno de casas e verificar visualmente 3×3/4×4/5×5 nos dois temas; incluir medição automatizada pontual e estados de foco/ocupação.
2. **Ultimate:** melhorar transição de foco/rolagem entre visão geral e tabuleiro ampliado; testar mouse, toque, teclado e retorno após jogada nas três dimensões críticas.
3. **Maluca:** distinguir estoque gasto de indisponibilidade temporária; depois reduzir a alternância de rolagem em paisagem, sem reduzir áreas de toque.

## Limitações

Não houve leitor de tela, simulação de baixa visão/daltonismo, medição de todos os pares de cores, nem matriz completa de resolução/tema/estado. Vitória e empate foram observados no Clássico; resultado terminal das demais modalidades **não** foi inspecionado nesta auditoria. As 14 modalidades receberam inspeção visual inicial em desktop; quatro delas não receberam inspeção mobile específica. Não houve alteração de código, CSS ou testes, nem execução da suíte global. Tempo aproximado de inspeção: **10 minutos**; contagem de tokens/consumo da ferramenta não está disponível.
