# Etapa 6.1B — Movimento 3×3 experimental

## Interface e ações

A experiência experimental está em `/experimental/3-mover` (criação) e `/experimental/3-mover/{matchId}` (retomada), registradas somente em `local` e `testing`. `/3-mover` continua servindo a view JavaScript legada. Não há entrada no catálogo público.

O componente Livewire `experimental-move-3x3` mantém apenas o identificador opaco da partida (`#[Locked]`) e uma mensagem de rejeição como propriedades públicas. O estado da rodada, turno, vencedor e placar são lidos da sessão a cada requisição. O servidor deriva o participante da vez do `MatchState` e chama `Move3x3Rules` com uma `GameAction` do tipo `place` (payload `['cell' => índice]`) ou `move` (payload `['from' => origem, 'to' => destino]`). Somente resultados aceitos são persistidos.

Durante a colocação, casas ocupadas ficam indisponíveis. Com seis peças, a interface passa à fase de movimento com base no tabuleiro oficial. Alpine guarda somente o índice da peça temporariamente selecionada: tocar a mesma peça cancela, tocar outra peça própria troca a seleção, e tocar uma casa vazia envia a ação completa ao Livewire. Peças adversárias são desabilitadas. Nenhuma regra de validação é transferida ao navegador. A seleção é apagada após jogada aceita, reinício, fim da rodada e recarga; após rejeição, é mantida apenas se ainda corresponder à peça do jogador atual.

`x-design-system.game-board` recebeu a opção explícita `interaction="movement"`; o padrão `place` e o comportamento dos Clássicos não mudaram. Cabeçalho, status, participantes, placar, ações, seletor de tema e diálogo de resultado reutilizam componentes existentes. O destaque usa `Move3x3Rules::winningLine()`. A apresentação tem o mesmo padrão responsivo/temático dos Clássicos, sem usar `classic-match`, que pressupõe apenas colocação.

## Sessão e limites

`SessionMatchStore` aceita `3-move` com nove casas, até três peças por lado e diferença de contagem de no máximo uma. A chave `experimental_matches.` e os registros existentes dos Clássicos permanecem inalterados. `MovementMatchManager` cria partidas locais com dois visitantes e sorteia X/O no servidor. Reiniciar alterna o jogador inicial e preserva o placar da instância; cada URL experimental aponta para uma partida da própria sessão. Não há apontador de partida ativa pública nem ação pública de Nova partida.

O resultado de vitória incrementa o placar na transição terminal, pela lógica existente do armazenamento. A modalidade não possui empate automático; uma rodada pode durar indefinidamente. A seleção de uma peça não é persistida nem compartilhada entre abas. As sessões são locais ao armazenamento configurado pelo Laravel; esta etapa não resolve concorrência simultânea entre abas, expiração da sessão, identidade individual, IA ou multiplayer.

## Verificação

Os testes direcionados cobrem criação/retomada, fase de colocação e transição, seleção/troca/cancelamento, movimento válido e rejeitado, vitória em colocação e movimento, placar, reinício, isolamento, registros corrompidos, temas, modal, recarga e responsividade em 360×800, 740×360 e 1280×900.

- Testes novos PHP + navegador: 17 aprovados, 143 asserções na execução final dos respectivos arquivos.
- Regressão PHP direcionada (motores, integrações e páginas públicas): 267 aprovados, 1.487 asserções.
- Regressão de navegador direcionada (Movimento e dois Clássicos públicos): 16 aprovados, 166 asserções; após acrescentar a verificação de limpeza da seleção no reinício, o arquivo novo foi reexecutado isoladamente: 6 aprovados, 62 asserções.
- `npm run build`, `vendor/bin/pint --dirty --format agent` e `git diff --check`: aprovados.

Antes da migração pública, faltam política de retomada por URL pública, confirmação de Nova partida, avaliação de concorrência entre abas e atualização dos testes públicos legados. Nenhuma dessas ações foi iniciada aqui.
