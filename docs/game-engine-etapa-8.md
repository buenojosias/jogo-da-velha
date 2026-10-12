# Etapa 8 — Linha de 4 com Movimento

`/4-mover` (`4x4-move`) agora usa Livewire e `Move4x4Rules`. O motor mantém somente o tabuleiro de 16 casas no estado da rodada: após oito colocações alternadas (quatro por jogador), aceita `move` de uma peça própria para qualquer casa vazia. As dez linhas vencedoras têm quatro posições; não há empate automático. A seleção de origem é temporária no Alpine e só o movimento completo chega ao servidor.

## Reutilização e sessão

`x-design-system.movement-match` reúne a apresentação e os diálogos dos Movimentos 4×4 de três e quatro peças; os dois componentes Livewire mantêm motores e ações específicos. O 3×3 e os demais jogos não foram alterados. `SessionMatchStore` preserva o formato de registros e `experimental_matches.`; para `4x4-move`, valida 16 casas e até quatro peças por lado. `MovementMatchManager` usa `movement4x4.active_match_id`, sem modificar as seis chaves públicas anteriores. O ID é opaco, protegido por `#[Locked]`, validado contra modalidade e sessão; ações de abas antigas são recusadas.

Próxima rodada conserva ID e placar e alterna o inicial. Nova partida requer confirmação na interface, cria novo ID, zera o placar e sorteia o inicial no servidor. A modalidade é retomada automaticamente ao voltar à URL pública. O limite de concorrência simultânea entre abas, já existente nas partidas por sessão, permanece.

## Arquivos

- Criados: `Move4x4Rules.php`, `⚡experimental-move-4x4.blade.php`, `experimental-move-4x4.blade.php`, `design-system/movement-match.blade.php` e testes unitários, públicos e de navegador correspondentes.
- Alterados: `SessionMatchStore.php`, `MovementMatchManager.php`, `routes/web.php`, componente Livewire 4×4 de três peças (somente composição visual compartilhada) e assertions de navegação/estrutura da rota migrada.
- Removido após conferência das referências: `resources/views/4x4-move.blade.php`. `public/js/game.js` e componentes legados compartilhados permanecem.

## Verificação

Testes dirigidos do novo motor e rota pública: 37 testes, 117 asserções; fluxos PHP dos Movimentos 4×4: 18 testes, 153 asserções após a extração visual; navegador e navegação relacionada: 69 testes, 460 asserções. Foram exercitados colocação, seleção/troca/cancelamento, movimento, vitória, modal, placar, reinício, nova partida, retomada, sessão, temas e larguras de 360, 740, 800 e 1280 px. Pint, build Vite e `git diff --check` passaram. A validação global única passou com **613 testes e 3.734 asserções**.

Limitação: a sessão não fornece coordenação distribuída nem resolução completa de ações simultâneas em abas diferentes. Nenhuma persistência em banco foi introduzida.
