# Etapa 5.2B — Clássico 4×4 experimental em Livewire

## Integração

As rotas `experimental.classic4x3.create` (`/experimental/4-3-basico`) e `experimental.classic4x3.show` (`/experimental/4-3-basico/{matchId}`) existem apenas em `local` e `testing`. A rota pública `4x3-basic` (`/4-3-basico`) continua servindo a view JavaScript legada. O catálogo não aponta para a experiência experimental.

`ClassicMatchManager::create4x3()` usa `Classic4x3Rules`, participantes visitantes locais e um sorteio X/O com `Randomizer`. A criação não altera `classic.active_match_id`; essa chave continua exclusiva da retomada pública do 3×3. A rota de visualização só aceita um identificador opaco pertencente à sessão e um registro válido da modalidade `4x3-basic`. Recarregar a URL de visualização retoma a rodada e o placar sem novo sorteio.

O componente Livewire `resources/views/components/⚡experimental-classic-4x3.blade.php` mantém somente o `matchId` bloqueado e uma mensagem de rejeição como propriedades públicas. Em cada ação, recupera o estado oficial, envia exclusivamente o índice da casa a `Classic4x3Rules`, persiste o resultado aceito e consulta `winningLine()` para destacar as três casas. O reinício alterna o lado inicial, limpa o tabuleiro e mantém o placar. O modal abre pelo evento de término e pode ser fechado sem outra requisição; uma página recarregada mostra o resultado, mas não reabre o diálogo automaticamente.

## Sessão e compatibilidade

`SessionMatchStore` mantém o prefixo `experimental_matches.` e o formato do registro (`starting_player`, `state`, `score`). A reconstrução aceita somente `3-basic` com nove casas ou `4x3-basic` com dezesseis e valida símbolos, participantes e status; a leitura do placar valida seus totais. Gravar ou reiniciar uma partida com um estado de outra modalidade é rejeitado antes de alterar o registro. O placar continua sendo incrementado apenas na passagem de uma rodada em andamento para vitória ou empate; ações rejeitadas e repetições após o término não o incrementam.

Registros 3×3 anteriores permanecem legíveis. `ClassicMatchManager` só retoma pela chave pública um registro efetivamente `3-basic`, evitando que um ponteiro corrompido para 4×4 seja exibido como 3×3. Partidas de sessões diferentes não se cruzam.

## Apresentação e reutilização

A nova tela reutiliza `game-board` com quatro colunas e os componentes Blade de cabeçalho, participantes, status, ações, tema e diálogo de resultado. O ajuste CSS `.classic-4x3-board > button` elimina somente no 4×4 a altura mínima que distorcia o tabuleiro em 360 px; os botões permaneceram com pelo menos 44 px nos seis tamanhos testados. Não houve mudança nas regras PHP, no tabuleiro 3×3 ou no JavaScript legado.

O SFC 4×4 e a view de entrada repetem temporariamente parte da composição e da inicialização visual do 3×3. A duplicação foi mantida para não refatorar o componente público durante esta prova experimental. Uma extração futura deve preservar os contratos de teste, o foco do modal e as sessões ativas.

## Verificação

- Testes PHP direcionados dos dois motores e dos fluxos Livewire 3×3/4×4: **163 testes, 925 asserções, aprovados**.
- Navegador do 4×4: **11 testes, 93 asserções, aprovados**. Cobrem jogo reativo, vitória, empate, placar, reinício, recarga, tema escuro, Escape, modal em orientação horizontal e dimensões 360×800, 390×844, 740×360, 800×360, 768×1024 e 1280×900.
- Regressão de navegador do 3×3 público e experimental: **26 testes, 329 asserções, aprovados**.
- `npm run build` e Pint: aprovados.

## Limites

A partida 4×4 continua local e associada à sessão do navegador; não há conta, persistência em banco, apontador público de partida ativa 4×4 nem controle de concorrência entre abas da mesma sessão. A rota de criação experimental inicia uma partida nova a cada visita; para retomar, deve-se manter a URL com `matchId`. A ação pública de “Nova partida” com confirmação será considerada apenas na migração pública.
