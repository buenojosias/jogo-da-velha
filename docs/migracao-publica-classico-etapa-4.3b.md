# Migração pública do Clássico 3×3 — Etapa 4.3B

## Entrada pública e retomada

`GET /3-basico` conserva o nome `3-basic` usado pelo catálogo e renderiza a mesma view Blade e o mesmo componente Livewire da experiência experimental. A página não inclui `public/js/game.js`; jogadas, turno, vitória e empate continuam sendo determinados por `Classic3x3Rules` no servidor.

`ClassicMatchManager` guarda na sessão o ID opaco da partida pública ativa (`classic.active_match_id`). Ao entrar, valida que o ID tem um registro pertencente à **mesma sessão** e que estado, jogador inicial e placar podem ser reconstruídos por `SessionMatchStore`. Se o apontador estiver ausente, inválido, expirado ou apontar para um registro corrompido, cria uma partida nova e substitui o apontador. A URL pública não recebe um ID de partida; parâmetros de consulta que tentem fornecer outro ID são ignorados. Atualizar a página ou voltar pelo catálogo retoma tabuleiro, resultado e placar sem novo sorteio.

O ID passado ao componente vem da rota no servidor e permanece `#[Locked]`. Em modo público, o componente também confere o apontador ativo antes de renderizar ou processar uma ação; uma aba antiga não altera a nova partida ativa. `MatchState`, vencedor, turno e placar permanecem na sessão e não são propriedades Livewire públicas mutáveis. A rota experimental por ID continua isolada por sessão e disponível apenas em `local` e `testing`.

## Rodada e partida

| Operação | Efeito |
| --- | --- |
| **Próxima rodada / Reiniciar partida** | Usa o reinício existente no mesmo `matchId`, limpa as nove casas, alterna o lado inicial e mantém vitórias e empates. |
| **Nova partida** | Requer confirmação em um diálogo acessível, cria outro `matchId`, zera o placar e sorteia X/O novamente no servidor. A ação ocorre em requisição Livewire, não em GET; depois retorna a `/3-basico`. |

O controle “Nova partida” é secundário ao reinício e existe somente na entrada pública. Cancelar ou fechar a confirmação por Escape não modifica a partida. A partida anterior não é apagada da sessão nesta etapa; apenas deixa de ser a ativa. Ela expira conforme a política da sessão Laravel.

## Rotas e compatibilidade

- `/3-basico` (`3-basic`): público, Livewire/PHP, sem autenticação.
- `/experimental/3-basico` e `/experimental/3-basico/{matchId}`: somente `local`/`testing`, preservados para desenvolvimento e testes.
- `/3-basico-legado` (`legacy.classic`): somente `local`/`testing`, temporário para a suíte de paridade; não aparece no catálogo nem em produção.

As outras 13 rotas, views e scripts de modalidades não foram alterados. O arquivo Blade legado `3-basic.blade.php` e o JavaScript compartilhado permanecem no repositório; a etapa não faz limpeza de código legado. Os testes antigos do Clássico foram ajustados para verificar resultados observáveis do Livewire, e os testes de paridade passaram a usar somente a rota legada local/de teste.

## Limitações

Partidas e placar duram apenas enquanto durar a sessão Laravel. Não há histórico persistente, recuperação entre dispositivos, identidade individual dos dois jogadores locais nem sincronização entre abas. A troca da partida ativa impede que uma aba antiga altere a nova partida pública, mas o armazenamento em sessão não oferece serialização transacional de jogadas simultâneas na **mesma** partida. Esses pontos exigem decisão própria antes de contas ou multiplayer; não foram ampliados nesta etapa.

## Verificação

| Verificação | Resultado |
| --- | --- |
| Testes dirigidos de Game Engine, Livewire, fluxo público e páginas de modalidades | 142 testes, 689 asserções aprovados. |
| Paridade com a rota legada restrita | 7 testes, 230 asserções aprovados. |
| Responsividade pública (360×800, 390×844, 740×360, 800×360, 768×1024 e 1280×900) | 6 testes, 54 asserções aprovados. |
| Validação global única com `php artisan test --compact` | **229 testes, 1.521 asserções aprovados**, incluindo as demais modalidades. |
| `npm run build` e `vendor/bin/pint --dirty --format agent` | Aprovados. |
| `git diff --check` | Aprovado. |

Os testes públicos novos cobrem primeira entrada sem login, retomada em andamento e concluída, placar/reinício, confirmação/cancelamento de nova partida, novo sorteio, IDs inválidos ou expirados, registro corrompido, isolamento entre sessões, propriedades bloqueadas e aba antiga. Testes de navegador verificam também a navegação pelo catálogo, ausência de erros JavaScript e acesso aos controles nos tamanhos acima. A ausência das rotas experimentais e da rota legada em produção é verificada por teste de registro de rotas.
