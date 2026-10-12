# Etapa 6.1A — Motor PHP de Movimento 3×3

## Contrato e estado

`App\Games\Rules\Move3x3Rules` implementa `GameRules` para a chave `3-move`, confirmada na rota `/3-mover`. `initialState()` recebe o jogador inicial, não sorteia, e cria `MatchStatus::InProgress` com `data = ['board' => ['', '', '', '', '', '', '', '', '']]`. X e O são armazenados como `'x'` e `'o'`; a casa vazia é `''`. O estado não contém seleção visual, contadores ou indicador de fase: as contagens são derivadas do tabuleiro. Até cinco marcas, a fase é de colocação; com seis, é de movimento. Uma jogada aceita cria outro `MatchState`; uma rejeitada devolve a mesma instância recebida.

## Ações e rejeições

- `place` recebe `['cell' => índice inteiro de 0 a 8]`. Só aceita casa vazia na fase de colocação, com menos de três peças do jogador.
- `move` recebe `['from' => índice, 'to' => índice]`. Só aceita, após seis colocações, origem com peça do jogador e destino vazio, sem exigir adjacência.
- `validate()` não altera estado; `apply()` valida antes de criar novo tabuleiro. O motor não depende de Livewire, sessão, HTTP ou JavaScript.

A precedência das rejeições é: status (`game_not_started` ou `game_finished`), tipo (`unsupported_action`), turno (`not_your_turn`), fase (`placement_complete` ou `movement_not_started`), limite de peças (`piece_limit_reached`, em `place`), índices (`invalid_cell`), origem igual ao destino (`same_cell`, em `move`), origem vazia (`empty_source`), peça adversária (`not_your_piece`) e destino ocupado (`cell_occupied`). Na colocação, `cell_occupied` é verificado após o índice. Os códigos não são mensagens de interface.

## Paridade com o legado

A view JavaScript atual alterna turnos nas colocações, permite no máximo três peças por lado e, depois, move uma peça própria para qualquer casa vazia. O motor reproduz as oito linhas clássicas e verifica vitória após cada `place` ou `move`. A escolha/cancelamento visual de uma peça no legado não é ação de domínio e não foi persistida. A rota e a view públicas não mudaram.

Não há empate automático após a sexta colocação nem por repetição ou quantidade de movimentos. Se ninguém vencer, a partida pode continuar indefinidamente. O motor aceita um jogador inicial fornecido pelo chamador, mantendo qualquer sorteio fora das regras.

## Verificação

- Testes unitários do novo motor e dos motores clássicos 3×3/4×4: **131 testes, 744 asserções, aprovados**. Cobrem fases, ambos os jogadores, vitórias na colocação e no movimento, oito linhas, ações inválidas, identidade do estado rejeitado, imutabilidade, movimentos distantes e continuidade sem empate automático.
- Pint e `git diff --check`: aprovados.
- Testes de navegador não executados: nenhuma interface ou rota foi alterada.

Uma futura integração Livewire precisará mapear os códigos de rejeição para mensagens e manter a seleção temporária somente na camada de apresentação. Não há persistência para esta modalidade nesta etapa.
