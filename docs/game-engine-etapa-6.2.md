# Etapa 6.2 — Movimento 4×4 com três peças

## Entrega

`/4-3-mover` (`4x3-move`) agora serve `experimental-move-4x3`, com Livewire e `Move4x3Rules`. O catálogo continua usando o mesmo nome de rota. A view legada exclusiva `resources/views/4x3-move.blade.php` foi removida depois de verificar suas referências; `public/js/game.js`, o layout legado e os componentes compartilhados permanecem para as outras modalidades.

O motor usa `MatchState::data['board']`: lista de 16 posições, casa vazia `''`, peças `x` ou `o`. A fase decorre da quantidade de peças no tabuleiro: até a sexta colocação vale `place` (`cell` inteiro de 0 a 15); depois vale `move` (`from` e `to` inteiros). Seleção e cancelamento são apenas estados temporários Alpine, não ações oficiais. O motor valida a jogada completa, conserva o estado original nas rejeições e consulta as 24 linhas possíveis de três casas. Não há empate automático por duração ou repetição; uma rodada sem vitória pode continuar indefinidamente.

## Sessão e reutilização

- `SessionMatchStore` reconhece `4x3-move`, valida 16 células e no máximo três marcas por jogador, mantendo o formato dos registros sob `experimental_matches.`. A modalidade de um registro não pode ser trocada em `save` ou `restart`.
- `MovementMatchManager` reutiliza a criação, o sorteio no servidor e a recuperação validada das duas modalidades de Movimento. O 4×4 usa `movement4x3.active_match_id`; `movement3x3.active_match_id` e as duas chaves dos Clássicos não mudaram.
- A primeira visita cria uma partida; visitas seguintes retomam ID, tabuleiro, resultado e placar. Próxima rodada conserva ID e placar e alterna o inicial. Nova partida exige confirmação visual, cria ID e placar novos, sorteia novamente e redireciona à mesma rota pública. Ações de componentes de abas antigas falham após a troca da partida ativa.
- `x-design-system.game-board` foi reutilizado com quatro colunas e o ajuste responsivo já existente para casas 4×4. O componente Livewire 4×4 segue o componente Movimento 3×3, conservando explicitamente suas diferenças de modalidade e tamanho. Essa duplicação de apresentação é temporária; uma extração pequena poderá ser avaliada após outra modalidade de movimento, sem alterar agora a versão pública 3×3.

O navegador envia somente índices de casas. `matchId` e `publicEntry` são propriedades Livewire bloqueadas, e tabuleiro, turno, resultado e placar permanecem no servidor. Cada partida é acessível apenas pela própria sessão. A implementação usa sessão local, sem persistência remota nem resolução completa de concorrência entre abas.

## Cobertura e verificação

- `tests/Unit/Games/Rules/Move4x3RulesTest.php`: estado inicial, turnos, fase, índices e movimentos inválidos, 24 linhas, bordas, vitórias na colocação e no movimento, imutabilidade e ausência de empate automático.
- `tests/Feature/PublicMovement4x3Test.php`: sorteio, retomada, jogadas, placar, reinício, nova partida, independência das quatro modalidades, registros inválidos, isolamento e proteção contra abas antigas.
- `tests/Browser/PublicMovement4x3BrowserTest.php`: catálogo, seleção/troca/cancelamento, movimento, modal, destaque, próxima rodada, nova partida, temas e larguras 360, 740, 800 e 1280 px. As asserções aguardam a marca confirmada pelo Livewire antes de recarregar.
- Referências estruturais de `ModalityPagesTest` e `ModalityNavigationTest` passaram a verificar a página pública moderna, sem exigir o HTML removido.

Resultados: testes PHP direcionados dos quatro motores e entradas públicas, **261 aprovados / 1.452 asserções**; testes de navegador direcionados, **44 aprovados / 436 asserções**; nova suíte PHP após os últimos ajustes, **54 aprovados / 239 asserções**. Pint e `git diff --check` passaram; `npm run build` gerou o bundle Vite sem erros. A validação global única aprovou **482 testes / 3.003 asserções**. Nenhuma dependência foi adicionada e não houve alteração em `public/js/game.js` nem nos motores publicados.
