# Etapa 12 — Pontuação 5×5

`/5-cheia` (`5-full`) usa agora `Full5x5Rules` e o componente Livewire `full-5x5`. Cada jogada envia somente o índice da casa; regras, tabuleiro, turno e pontuação são calculados no servidor.

O motor percorre horizontal, vertical e as duas diagonais, começando uma sequência apenas onde não existe marca igual imediatamente antes. Assim, cada sequência máxima de 3, 4 ou 5 vale, respectivamente, 3, 6 ou 10 pontos uma única vez. Cruzamentos em direções diferentes são independentes. Não há vitória antecipada: somente a 25ª colocação compara os pontos e produz `Completed` para o vencedor ou `Drawn` para a igualdade. A pontuação da rodada é derivada do tabuleiro; o placar de vitórias e empates fica separado no registro da sessão.

`SessionMatchStore` mantém o prefixo `experimental_matches.` e valida 25 casas, equilíbrio de marcas e coerência do resultado final. `FullMatchManager` usa `full5x5.active_match_id`, independente das dez modalidades anteriores. Próxima rodada mantém o ID e o placar, alternando o inicial; Nova partida, após confirmação, gera outro ID e zera o placar. Ações de abas com IDs antigos são recusadas. A sessão continua local e não sincroniza abas simultâneas.

Foram adicionados `app/Games/Rules/Full5x5Rules.php`, `app/Http/FullMatchManager.php`, o componente e a página Blade da modalidade, além de testes unitários, públicos e de navegador. Foram ajustados o armazenamento, a rota, a composição visual compartilhada para mostrar pontos da rodada, o diálogo de resultado e os testes de navegação. A view exclusiva `resources/views/5-full.blade.php` foi removida; `public/js/game.js` e componentes legados compartilhados foram preservados.

Verificação: testes direcionados de regras, fluxo público e páginas (76 testes, 353 asserções), navegador da modalidade (7 testes, 112 asserções) e regressões de navegação/modalidades especiais (28 testes, 203 asserções) aprovados. A suíte global passou com **816 testes e 4.985 asserções**. Pint, build Vite e `git diff --check` também passaram.
