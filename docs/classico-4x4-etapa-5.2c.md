# Etapa 5.2C — Clássico 4×4 público

## Entrada e sessões

`/4-3-basico` preserva o nome `4x3-basic` e agora renderiza a view e o componente Livewire usados pela experiência experimental. Não carrega `public/js/game.js`. A view JavaScript antiga `resources/views/4x3-basic.blade.php` foi preservada, mas não é mais a entrada pública. As rotas experimentais continuam registradas somente em `local` e `testing`.

`ClassicMatchManager` mantém `classic.active_match_id` exclusivamente para o 3×3 e usa `classic4x3.active_match_id` para o 4×4. Ambos apontam para os registros já existentes em `experimental_matches.{matchId}`. A leitura valida se o registro pertence à sessão, se é íntegro e se corresponde à modalidade esperada; um ponteiro ausente, expirado, corrompido ou de outra modalidade gera uma nova partida para aquela modalidade. Nenhum identificador é aceito por parâmetro da rota pública. A chave e os registros antigos do 3×3 não foram migrados nem invalidados.

## Rodadas e nova partida

“Reiniciar partida” e “Próxima rodada” usam o reinício existente: limpam o tabuleiro, preservam vitórias e empates e alternam o jogador inicial. “Nova partida” abre um diálogo de confirmação; confirmar cria outro identificador opaco, zera o placar, sorteia X/O no servidor e atualiza apenas o ponteiro ativo do 4×4. Cancelar preserva o estado. O jogador permanece em `/4-3-basico`.

O navegador envia somente o índice da casa. `matchId` e o indicador da entrada pública são propriedades Livewire bloqueadas; tabuleiro, turno, vencedor e placar continuam exclusivamente na sessão. Ações de abas antigas são rejeitadas quando seu identificador deixa de ser o ativo. O 3×3 conserva seu fluxo e ponteiro originais.

## Arquivos e cobertura

Foram alterados `app/Http/ClassicMatchManager.php`, `routes/web.php`, `resources/views/experimental-classic-4x3.blade.php` e `resources/views/components/⚡experimental-classic-4x3.blade.php`. Foram criados testes públicos em `tests/Feature/PublicClassic4x3Test.php` e `tests/Browser/PublicClassic4x3BrowserTest.php`; as expectativas de navegação em `tests/Feature/ModalityPagesTest.php`, `tests/Feature/ExperimentalClassic4x3Test.php` e `tests/Browser/ModalityNavigationTest.php` foram atualizadas para a nova entrada. Um teste preexistente do 3×3 em `tests/Browser/ClassicGameplayTest.php` passou a aguardar a conclusão observável da jogada antes de recarregar a página. Motores, contratos, armazenamento da sessão, visual compartilhado e JavaScript legado não foram modificados.

Os testes públicos cobrem primeira visita sem login, sorteio X/O, retomada de rodada e placar, vitória, empate, reinício, nova partida, separação do 3×3, sessão alheia, dados corrompidos, propriedades bloqueadas, abas antigas, catálogo, confirmação no navegador, temas e seis dimensões de viewport. Os testes experimentais continuam cobrindo linhas vencedoras, modal, placar e responsividade detalhada.

## Limitações

As partidas são locais e duram enquanto a sessão Laravel estiver disponível; não há conta nem persistência entre sessões ou dispositivos. Duas abas da mesma partida ainda podem disputar ações simultâneas sem controle transacional distribuído; a verificação de aba antiga protege apenas contra o uso de uma partida que deixou de ser a ativa. O diálogo de confirmação é uma proteção de interface, seguindo o padrão do 3×3; a ação Livewire autenticada pela sessão continua invocável diretamente pelo cliente. A view legada 4×4 permanece no repositório para uma limpeza posterior, fora desta etapa.

## Verificação

- Testes direcionados dos motores, componentes e navegação: **202 testes, 1.216 asserções, aprovados**.
- `npm run build`: aprovado.
- `vendor/bin/pint --dirty --format agent` e `git diff --check`: aprovados.
- Primeira execução global: **338 de 339 testes aprovados**; o teste de recarga imediata do 3×3 falhou por sincronização assíncrona. Após aguardar a contagem de jogadas, o cenário isolado passou (**1 teste, 15 asserções**) e a nova execução global passou: **339 testes, 2.058 asserções**.
