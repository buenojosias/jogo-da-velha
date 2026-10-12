# Etapa 6.1C — Movimento 3×3 público

## Entrada e partidas ativas

`/3-mover` (rota `3-move`) renderiza a página Livewire do Movimento 3×3. A view JavaScript `resources/views/3-move.blade.php` permanece no repositório, mas não é carregada pela rota pública. O catálogo continua usando o mesmo nome de rota. As URLs `/experimental/3-mover` e `/experimental/3-mover/{matchId}` continuam exclusivas de `local` e `testing` e não alteram a partida pública ativa.

`MovementMatchManager` usa a chave `movement3x3.active_match_id`. Na primeira visita ou diante de um apontador ausente, inválido, expirado, corrompido ou de outra modalidade, cria uma partida com ID opaco e sorteia X/O no servidor. Caso a partida seja válida, retoma o mesmo tabuleiro, turno, jogador inicial e placar. O gerenciador só aceita registros `3-move` pertencentes à sessão atual. As chaves `classic.active_match_id`, `classic4x3.active_match_id` e `experimental_matches.` permanecem inalteradas; não houve migração nem mudança do formato dos registros existentes.

O componente `experimental-move-3x3` recebe `matchId` e `publicEntry` do servidor como propriedades `#[Locked]`. Em cada jogada e reinício públicos, consulta a partida ativa antes de acessar ou modificar o estado. Depois de Nova partida, uma aba pública antiga recebe 404 ao tentar agir sobre a partida anterior. Os dois Clássicos usam suas próprias chaves e continuam independentes.

## Nova partida e próxima rodada

O botão secundário **Nova partida** abre um diálogo nativo de confirmação. Cancelar não envia ação Livewire. Confirmar chama `newMatch`, cria outro ID, zera o placar, sorteia novamente o inicial, atualiza apenas o apontador do Movimento e redireciona para `/3-mover`. A seleção temporária desaparece com a navegação. A ação não está disponível nas partidas experimentais.

**Reiniciar partida/Próxima rodada** mantém o ID e o placar, alterna o jogador inicial anterior e volta ao tabuleiro vazio na fase de colocação. A seleção Alpine é apagada pelo evento `movement-state-changed`. Após movimento rejeitado, `movement-selection-reconcile` mantém o destaque somente se a peça ainda pertence ao jogador da vez no tabuleiro atualizado. Recarga sempre inicia sem seleção. O navegador envia apenas índices; `Move3x3Rules` continua responsável por validar `place` e `move`, determinar vitória e fornecer as casas vencedoras.

## Testes e compatibilidade

Os testes PHP públicos verificam primeira visita, sorteio, retomada, colocação e movimento, resultado terminal, placar, reinício, Nova partida, independência dos Clássicos, isolamento de sessões, recuperação de apontadores inválidos e proteção contra ações de abas antigas e alterações de propriedades Livewire. Os testes de navegador verificam catálogo, fases, seleção/troca/cancelamento, rejeição do servidor, modal, destaque, confirmação/cancelamento, temas, recarga e responsividade em 360×800, 740×360, 800×360 e 1280×900. Cada colocação aguarda confirmação observável no DOM antes da próxima ação ou recarga, sem espera fixa.

- Regressão PHP direcionada: 220 testes, 1.304 asserções, todos aprovados.
- Regressão de navegador direcionada: 53 testes, 452 asserções, todos aprovados.
- Suíte global única: 421 testes, 2.664 asserções, todos aprovados.
- `npm run build`, Pint e `git diff --check`: aprovados.

Limitações: a sessão Laravel continua sendo o armazenamento local; não há identificação individual dos dois participantes, sincronização entre abas, persistência remota ou coordenação de requisições concorrentes. O bloqueio de abas antigas protege a partida ativa após Nova partida, mas não pretende resolver toda a concorrência entre abas. A limpeza da view legada fica para etapa posterior.
