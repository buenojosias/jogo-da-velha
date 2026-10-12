# Etapa 7 — Rotação 3×3 e 4×4

As rotas públicas `/3-rotacao` (`3-rotation`) e `/4-3-rotacao` (`4x3-rotation`) agora usam Livewire e regras PHP. O catálogo conserva os nomes de rota. As views exclusivas `resources/views/3-rotation.blade.php` e `resources/views/4x3-rotation.blade.php` foram removidas após verificação das referências. `public/js/game.js`, o layout legado e seus componentes compartilhados permanecem para as modalidades ainda não migradas.

## Regras e interface

`RotationRules` concentra a ação `place`, validação, atualização imutável da ordem FIFO e consultas `winningLine()`/`oldestPieces()`. `Rotation3x3Rules` define 9 casas e 8 linhas; `Rotation4x3Rules` define 16 casas e 24 linhas. O destino é validado como vazio **antes** de retirar a peça mais antiga. Não há empate automático por repetição ou duração.

O estado oficial contém `data.board` (casas `''`, `x`, `o`) e `data.order` (`['x' => [índices], 'o' => [índices]]`, do mais antigo ao mais recente). Cada lista tem até três índices inteiros, únicos e correspondentes exatamente às marcas do tabuleiro. O navegador envia apenas o índice de destino. A composição `x-design-system.rotation-match` é comum às duas telas; `x-design-system.game-board` recebeu indicação visual e acessível das peças mais antigas, independente do destaque vencedor. O componente Livewire `rotation-match` escolhe apenas entre os dois motores explícitos e mantém `matchId` e modalidade bloqueados.

## Sessão

`SessionMatchStore` preserva o prefixo `experimental_matches.` e o formato das quatro modalidades anteriores; apenas estados de Rotação incluem `order`. Na recuperação, valida tamanho, marcas, limites, índices duplicados e correspondência entre ordem e tabuleiro. `RotationMatchManager` usa `rotation3x3.active_match_id` e `rotation4x3.active_match_id`, sem alterar `classic.active_match_id`, `classic4x3.active_match_id`, `movement3x3.active_match_id` ou `movement4x3.active_match_id`. Cada entrada pública retoma uma partida válida da própria sessão ou cria outra com inicial sorteado no servidor. Próxima rodada mantém ID e placar e alterna o inicial; Nova partida exige confirmação na interface, cria ID e placar novos. Componentes de abas antigas não podem agir após a troca do ID ativo.

## Arquivos e cobertura

Criados: três classes de regras em `app/Games/Rules`, `app/Http/RotationMatchManager.php`, `resources/views/components/⚡rotation-match.blade.php`, `resources/views/components/design-system/rotation-match.blade.php`, `resources/views/rotation-match.blade.php`, testes unitários, de integração e de navegador para as duas modalidades. Alterados: `SessionMatchStore`, `game-board`, rotas e testes estruturais/de navegação afetados pela migração. O teste de navegador experimental do Movimento 3×3 passou a esperar a confirmação de cada jogada Livewire antes de recarregar. Removidas somente as duas views legadas exclusivas.

Os testes de motor cobrem as 8/24 linhas, bordas, rejeições, ordem após várias rotações, vitória em colocação e rotação, imutabilidade e ausência de empate automático. Os testes de integração cobrem sorteio, retomada, placar, reinício, nova partida, seis modalidades independentes, sessões distintas, dados corrompidos, propriedades protegidas e abas antigas. Os testes de navegador exercitam remoção real, destaques, modal, confirmação, temas e 360×800, 740×360, 800×360 e 1280×900.

Resultados: testes PHP dirigidos de regras, integração e estrutura, **109 aprovados / 452 asserções**; testes de navegador dirigidos de Rotação e navegação, **18 aprovados / 217 asserções**. Pint, `npm run build` e `git diff --check` passaram. A primeira suíte global encontrou uma corrida preexistente no teste experimental do Movimento 3×3, que recarregava antes da resposta Livewire; o teste foi sincronizado por condição observável e passou isoladamente (**6 testes / 80 asserções**). A segunda execução global aprovou **569 testes / 3.508 asserções**. Limitação conhecida: partidas continuam restritas à sessão local; não há sincronização ou resolução completa de concorrência entre abas.
