# Placar local entre rodadas — Etapa 4.2A

O `MatchState` continua descrevendo **uma rodada**: tabuleiro, turno, status e vencedor. O placar acumulado pertence ao registro da **partida local** identificado por `matchId`, armazenado por `SessionMatchStore` na sessão Laravel como `score: {x, o, draws}`. Uma partida nova inicia com três zeros. Registros de sessão criados antes desta etapa, sem a chave `score`, também começam em zero; valores armazenados inválidos são rejeitados.

O componente Livewire envia somente a intenção de colocar uma peça e salva apenas resultados aceitos por `Classic3x3Rules`. Ao salvar, o armazenamento compara o status anterior persistido com o novo estado: somente a transição de `InProgress` para `Completed` ou `Drawn` incrementa o contador correspondente. Uma ação rejeitada não é salva, e salvar novamente um estado terminal não soma outro ponto. O reinício cria uma nova rodada, mas conserva o placar e o mesmo `matchId`. Recarregar a página reconstrói ambos da sessão; nenhuma pontuação é propriedade pública mutável do Livewire.

As vitórias aparecem nos cartões X/O existentes; os empates ficam em uma linha compacta da barra lateral. O tabuleiro, os controles, os temas e os identificadores anteriores foram mantidos. Em 740×360 e 800×360, o placar e os controles continuam visíveis sem rolagem horizontal.

## Verificação

- `php artisan test --compact tests/Feature/ExperimentalClassic3x3Test.php tests/Unit/Games`: 78 testes e 330 asserções aprovados. Cobertura de placar inicial, vitórias X/O, empate, reinício, nova requisição, partidas e sessões isoladas, ação inválida, solicitação repetida após término e dados de sessão inválidos.
- `php artisan test --compact tests/Browser/ExperimentalClassic3x3BrowserTest.php`: 15 testes e 185 asserções aprovados, incluindo fluxo reativo de placar em 360×800 e 1280×900 e regressão da composição horizontal.
- `npm run build`: aprovado.
- `vendor/bin/pint --dirty --format agent`: aprovado.

Esta implementação continua limitada a duas pessoas no mesmo dispositivo e à sessão atual. Não há identidade individual, histórico de rodadas, placar compartilhado entre sessões ou serialização transacional de jogadas simultâneas em abas diferentes. Essas decisões precisam ser tomadas antes de multiplayer ou persistência durável.
