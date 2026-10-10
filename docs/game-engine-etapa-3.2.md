# Regras PHP do Clássico 3×3 — Etapa 3.2

`App\Games\Rules\Classic3x3Rules` implementa o contrato `GameRules` para a modalidade `3-basic`, sem dependência de Livewire, HTTP, JavaScript ou persistência. `initialState()` recebe o lado inicial explicitamente: a regra não sorteia jogadores. Uma modalidade com outra chave é rejeitada com `InvalidArgumentException`.

## Estado e ação

O estado específico usa `data['board']`: uma lista de nove strings, em ordem de leitura (linhas de cima para baixo, colunas da esquerda para a direita). `''` representa uma casa vazia; `'x'` e `'o'` representam os lados de `PlayerMark`. O estado inicial tem as nove casas vazias, status `InProgress`, nenhum vencedor e o jogador inicial como próximo turno.

A única ação aceita é `new GameAction($player, 'place', ['cell' => $index])`, com índice inteiro de 0 a 8. A validação segue esta precedência: partida aguardando (`game_not_started`), partida concluída ou empatada (`game_finished`), tipo desconhecido (`unsupported_action`), lado fora do turno (`not_your_turn`), índice ausente/inválido (`invalid_cell`) e casa ocupada (`cell_occupied`). Esses códigos não são mensagens de interface. Um tabuleiro estruturalmente inválido é considerado erro de estado e provoca `InvalidArgumentException`, não uma rejeição de jogada.

Uma ação aceita gera outro `MatchState`. A regra verifica as oito linhas de três casas antes de verificar empate, portanto uma vitória na nona jogada resulta em `Completed`, não `Drawn`. Em ambos os resultados finais, `currentPlayer` é nulo; somente a vitória define `winner`. Ação rejeitada devolve a mesma instância de estado, e `validate()` não a modifica. A autenticação e a associação entre uma identidade real e o lado X/O continuam responsabilidade do chamador.

## Verificação e limite

`tests/Unit/Games/Rules/Classic3x3RulesTest.php` cobre 31 cenários e 188 asserções: inicialização com ambos os lados, colocação e alternância, entradas inválidas e sua precedência, preservação de estado, vitórias por direção para X/O, empate, vitória na nona jogada, rejeição após o fim e ausência de efeitos colaterais de `validate()`. A execução de `tests/Unit/Games` passou com 52 testes e 237 asserções no total. O plugin Pest Browser exige uma porta local mesmo nesses testes unitários; a execução dentro do sandbox falhou antes das asserções por falta de permissão para abrir essa porta, e a execução autorizada fora dele passou.

Esta etapa não altera a interface JavaScript e não comprova paridade entre o motor PHP e o jogo no navegador. A integração, incluindo o mapeamento da identidade do participante e da mensagem de rejeição para a interface, fica para uma etapa posterior.
