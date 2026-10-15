# Clássico 4×4 original (três em linha)

Esta pasta preserva a implementação anterior da modalidade pública `/4-3-basico`.
Cada jogador podia marcar qualquer casa vazia do tabuleiro 4×4 em todos os turnos.
Três peças consecutivas em linha, coluna ou diagonal venciam; tabuleiro cheio sem
vitória resultava em empate. O placar e a alternância do jogador inicial eram
mantidos entre rodadas.

Ela foi arquivada após testes manuais indicarem vantagem estratégica excessiva
em algumas aberturas. A modalidade pública continua ativa, agora com a Regra de
Proximidade.

## Arquivos preservados

- `Classic4x3Rules.php`: cópia da regra PHP antes da mudança.
- `tests/Unit/Games/Rules/Classic4x3RulesTest.php`: testes unitários originais.
- `tests/Feature/PublicClassic4x3Test.php` e `tests/Feature/ExperimentalClassic4x3Test.php`: testes funcionais originais.
- `tests/Browser/PublicClassic4x3BrowserTest.php` e `tests/Browser/ExperimentalClassic4x3BrowserTest.php`: testes de navegador originais.

Os arquivos desta pasta são apenas referência versionada; não são carregados
automaticamente pelo Laravel, Livewire ou Pest. Para recuperar a regra, copie o
conteúdo para os caminhos ativos correspondentes e adapte o contrato de estado
e os testes à versão então vigente. Não exponha esta pasta por rota pública.

## Sessões anteriores

O estado original guardava apenas `board` e não registrava a ordem das jogadas.
Na primeira retomada de uma sessão antiga, a aplicação reinicia somente a rodada
incompatível, preservando o placar e o identificador da partida. Uma rodada em
andamento mantém seu jogador inicial; se já estava encerrada, a nova rodada
começa com o outro jogador. A ordem antiga não é inferida do tabuleiro.
