# Alinhamento funcional do Clássico 3×3 — Etapa 4.3A

## Início e ciclo de vida

`/experimental/3-basico` cria uma **nova partida** e sorteia X ou O no servidor com `Random\Randomizer::getInt(0, 1)`. Sem configuração especial, o `Randomizer` usa a fonte segura `Random\Engine\Secure`. A rota passa o lado escolhido a `Classic3x3Rules::initialState()`; o Game Engine continua determinístico e sem aleatoriedade interna. `SessionMatchStore` registra o lado inicial e o estado oficial sob o `matchId` opaco da sessão. Abrir novamente a URL da mesma partida não sorteia outro lado. Abrir a rota de criação cria outra partida e realiza outro sorteio independente.

O reinício existente alterna o lado que iniciou a rodada anterior, mantém o mesmo `matchId`, limpa o tabuleiro e preserva o placar `x`, `o` e `draws`. O navegador não envia o lado inicial nem pode substituir tabuleiro, turno, vencedor ou placar; um parâmetro de consulta `starting_player` é ignorado pela rota, inclusive quando pede o lado oposto ao sorteado. Nos testes PHP, uma instância de `Randomizer` com motor determinístico é vinculada **somente ao contêiner de teste**; no navegador, os cenários que exigem um lado inicial usam o botão normal de reinício após observar a indicação de turno. Nenhum parâmetro de teste foi exposto na rota.

## Comparação com `/3-basico`

| Aspecto | Público (JavaScript) | Experimental (Livewire/PHP) | Avaliação |
| --- | --- | --- | --- |
| Inicialização | Sorteio no navegador a cada carregamento. | Sorteio no servidor uma vez por nova partida. | Política de lado inicial alinhada; local e momento do sorteio diferem intencionalmente. |
| Turnos e jogadas válidas | Coloca X/O e alterna o turno. | Mesmo resultado, validado pelo Game Engine. | Equivalente nos cenários de paridade. |
| Jogada inválida | Casa ocupada é ignorada sem mensagem. | Botão ocupado fica indisponível; chamada direta é rejeitada no servidor com mensagem. | Mesmo estado final; feedback e proteção são melhorias intencionais. |
| Vitória e empate | Verifica os oito trios antes de empate. | Mesmos trios e precedência; estado terminal oficial. | Sem divergência de regra encontrada. |
| Placar | Vitórias X/O apenas em memória da página; não conta empates. | Vitórias X/O e empates por `matchId` na sessão, inclusive após recarga. | Ampliação intencional. |
| Linha vencedora | Destaca o trio encontrado. | Destaca o trio consultado na regra PHP. | Resultado funcional equivalente; apresentação diferente. |
| Resultado | Modal visual apenas para vitória; empate aparece no status. | Diálogo para vitória ou empate, com placar e controle de próxima rodada. | Ampliação intencional. |
| Reinício | Alterna o inicial e preserva o placar enquanto a página existir. | Alterna o inicial e preserva o placar da partida na sessão. | Equivalente na rodada; persistência ampliada intencionalmente. |
| Recarga | Sorteia nova partida e zera o placar. | A URL da partida conserva rodada e placar; a rota de criação inicia outra partida. | Diferença intencional de ciclo de vida. |
| Acessibilidade | Casas são `div` clicáveis; modal é `div` sem semântica de diálogo ou Escape. | Casas são botões; diálogo nativo tem título, foco, Escape e retorno do foco. | Melhoria intencional, não regressão de paridade. |

A comparação automatizada de regras usa `tests/Browser/ClassicParityTest.php`. Ela observa o lado inicial de cada implementação e, quando necessário, usa seus controles normais para alinhar as sequências antes de comparar turno, jogadas e resultado. A matriz unitária do Game Engine continua responsável por cobrir as demais combinações de vitória e rejeição. Não se reproduzem as limitações de acessibilidade ou persistência da interface antiga apenas para obter HTML idêntico.

## Limites antes da migração pública

A rota experimental continua disponível apenas em `local` e `testing`; `/3-basico` e as demais modalidades permanecem inalteradas. Ainda há uma decisão de produto sobre como usuários da rota pública iniciarão ou recuperarão uma partida persistida por sessão. A sessão pode expirar e não oferece histórico durável, identidade individual dos dois jogadores locais nem coordenação transacional entre abas simultâneas. Essas limitações não são novas nesta etapa e não afetam a paridade das regras de uma rodada.

## Verificação

| Comando | Resultado |
| --- | --- |
| `php artisan test --compact tests/Unit/Games tests/Feature/ExperimentalClassic3x3Test.php` | 91 testes, 429 asserções aprovados. |
| `php artisan test --compact tests/Browser/ClassicParityTest.php tests/Browser/ClassicGameplayTest.php tests/Browser/ExperimentalClassic3x3BrowserTest.php tests/Browser/ThemeFoundationTest.php` | 35 testes, 576 asserções aprovados em Chromium. |
| `vendor/bin/pint --dirty --format agent` | Aprovado. |

O build Vite não foi repetido porque não houve alteração em Blade, CSS, JavaScript ou configuração frontend. A suíte de navegador das outras 13 modalidades não foi executada, pois seus arquivos e rotas não mudaram.
