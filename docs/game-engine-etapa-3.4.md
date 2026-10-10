# Paridade do Clássico 3×3 — Etapa 3.4

## Resultado

As duas implementações apresentam os mesmos resultados de **regras de uma partida** nos cenários verificados. A versão pública usa `resources/views/3-basic.blade.php` e `public/js/game.js`; a experimental usa `Classic3x3Rules` pelo componente Livewire. Ambas usam tabuleiro de nove casas, os mesmos oito trios vencedores, alternam X/O após jogada válida, ignoram uma casa ocupada e verificam vitória **antes** de empate. Vitórias horizontais, verticais e diagonais, empate e vitória na nona jogada foram confirmados em navegador com sequências idênticas após alinhar o jogador inicial.

| Comportamento | Versão pública JavaScript | Versão experimental Livewire | Conclusão |
| --- | --- | --- | --- |
| Jogador inicial | Sorteia X ou O ao carregar a página. | Uma nova partida começa sempre com X. | **Diferença de política de inicialização**, não de vitória/movimento. |
| Colocação e turno | Registra a peça e troca de lado se a partida continua. | `Classic3x3Rules::apply()` faz o mesmo. | Equivalente nos testes, com quantidade de jogadas e lado da vez conferidos após cada lance. |
| Casa ocupada | Clique é recebido, mas `handleMove` retorna sem alterar tabuleiro ou turno. | Botão fica desabilitado; chamada direta ao componente é rejeitada pelo motor. | Mesmo resultado de partida; interação e mensagem diferem. |
| Vitória e empate | Verifica os mesmos oito trios, depois tabuleiro cheio. | Mesma ordem e mesmos trios. | Sem divergência encontrada; vitória na nona jogada prevalece sobre empate. |
| Após o fim | `gameOver` impede novas jogadas; o turno em memória conserva o último lado. | Estado final não tem próximo jogador; botões ficam desabilitados e a regra rejeita nova ação. | Mesmo bloqueio funcional; representação interna diferente. |
| Reinício | Limpa tabuleiro e alterna o lado inicial, inclusive pelo botão do modal. | Limpa tabuleiro e alterna o lado inicial pelo botão da página. | Equivalente para o botão de reinício testado. |

## Diferenças de interface e ciclo de vida

O jogo público usa ícones SVG, modal de vitória, marcação visual das casas vencedoras e **placar acumulado entre rodadas enquanto a página permanece aberta**. A versão experimental mostra o resultado no status e a quantidade de jogadas; ainda não tem modal, realce da linha vencedora nem placar acumulado. Essas funcionalidades não foram implementadas nesta etapa.

Uma jogada inválida é silenciosa na versão pública; na experimental, a casa ocupada fica indisponível e uma ação rejeitada diretamente pelo servidor produz mensagem amigável. A versão pública mantém a partida apenas no JavaScript e a perde ao recarregar a página. A experimental conserva a partida na sessão ao recarregar sua URL opaca; abrir `/experimental/3-basico` cria uma partida nova. Essas diferenças são de apresentação ou ciclo de vida, não de cálculo do vencedor.

## Evidência automatizada

`tests/Browser/ClassicParityTest.php` executa as duas páginas em Chromium. Para neutralizar o sorteio da versão pública, lê a indicação visível do lado da vez e reinicia a página apenas quando necessário; para iniciar com O, reinicia também a experimental. Em seguida, aplica **a mesma lista de índices** nos dois tabuleiros. Os adaptadores leem quantidade de peças, indicação de turno e resultado visível, sem consultar variáveis internas do JavaScript nem exigir HTML idêntico entre as páginas.

Os sete casos aprovados cobrem vitória horizontal de X, vertical de O, diagonal de X, vitória de X na nona jogada, empate, tentativa de jogar após vitória e casa ocupada seguida de dois reinícios. Em cada sequência, a contagem de jogadas é comparada após cada lance; em lances não finais, também se confere o lado da vez. A vitória é identificada pelo modal e placar público e pela mensagem de resultado experimental. O clique em uma casa já ocupada não aumenta a contagem nem muda o turno em nenhuma versão; na experimental, a tentativa ocorre sobre um botão desabilitado.

| Teste executado | Resultado |
| --- | --- |
| `tests/Browser/ClassicParityTest.php` | 7 testes, 223 asserções aprovados. |
| `tests/Browser/ClassicGameplayTest.php` | 2 testes, 24 asserções aprovadas. |
| `tests/Browser/ExperimentalClassic3x3BrowserTest.php` | 2 testes, 16 asserções aprovadas. |
| `tests/Feature/ExperimentalClassic3x3Test.php` | 15 testes, 53 asserções aprovadas. |
| `tests/Unit/Games/Rules/Classic3x3RulesTest.php` | 31 testes, 188 asserções aprovadas. |
| `vendor/bin/pint --dirty --format agent` | Aprovado. |

Os testes de navegador não esgotam todas as sequências possíveis; os unitários existentes cobrem as orientações e ambos os lados no motor PHP. Não foi encontrada divergência de regra nos cenários executados. As diferenças de sorteio inicial, placar, feedback, modal e persistência devem ser decididas explicitamente antes de substituir `/3-basico`. Recomenda-se preservar a suíte de paridade, definir a política de início e de ciclo de vida e só então planejar a equivalência de placar e apresentação. Nenhuma implementação foi substituída nesta etapa.
