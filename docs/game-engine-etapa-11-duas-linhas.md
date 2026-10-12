# Etapa 11 — Duas Linhas 5×5

- `/2-linhas` (`5x5-2-lines`) usa `TwoLines5x5Rules` e Livewire. O estado da rodada contém somente 25 casas; o navegador envia apenas `place(cell)`.
- O motor gera 48 trincas geométricas nas quatro direções. Em cada direção, trincas do mesmo jogador que compartilham ao menos uma casa são agrupadas por conectividade transitiva, como no `checkWinner()` legado. Uma sequência de quatro ou cinco marcações vale uma linha; direções distintas contam separadamente mesmo quando se cruzam. A vitória exige ao menos dois grupos e prevalece sobre o empate na última jogada. O destaque reúne todas as casas das trincas reconhecidas do vencedor.
- `SessionMatchStore` aceita a nova modalidade com 25 casas, valida marcas, equilíbrio de peças e resultados terminais, sem alterar o formato das nove modalidades anteriores. `twoLines5x5.active_match_id` isola a partida pública: retorno retoma estado e placar; Próxima rodada mantém ID e placar e alterna o inicial; Nova partida confirmada cria outro ID e zera o placar. Abas antigas deixam de poder agir na partida pública.
- A interface reutiliza a composição dos jogos por colocação, com instrução e texto de resultado configuráveis. O ajuste CSS é restrito ao tabuleiro 5×5 em paisagem. A view exclusiva `5x5-2-lines.blade.php` foi removida após confirmar que não restavam referências de execução; `public/js/game.js` e recursos compartilhados permanecem.
- Limitações: sessões locais não sincronizam jogadas concorrentes entre abas; sem autenticação, os dois lados pertencem ao mesmo dispositivo.

## Verificação

- Testes unitários cobrem direções, agrupamento, cruzamentos, bordas, empate, vitória na última casa, validação e imutabilidade.
- Testes públicos cobrem sorteio, retomada, vitória, empate, placar, reinício, Nova partida, isolamento das dez modalidades e dados de sessão inválidos.
- Testes de navegador cobrem catálogo, jogabilidade, destaque, modal, placar, confirmação, temas e toque em 360×800, 740×360, 800×360 e 1280×900.
- Resultados: motor novo 23 testes/80 asserções; integração pública 10 testes/104 asserções; navegador novo 7 testes/113 asserções. A suíte global passou com 770 testes e 4.672 asserções. `npm run build`, Pint e `git diff --check` também passaram.
