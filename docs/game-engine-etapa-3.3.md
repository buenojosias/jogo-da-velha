# Integração experimental do Clássico 3×3 — Etapa 3.3

## Organização e fluxo

A rota nomeada `experimental.classic.create` (`/experimental/3-basico`) cria uma partida local e redireciona para `experimental.classic.show` (`/experimental/3-basico/{matchId}`). Ambas só são registradas em `local` e `testing`; `/3-basico` e o catálogo público não foram alterados. A rota de partida só responde quando o ID pertence à sessão atual.

`resources/views/components/⚡experimental-classic-3x3.blade.php` é um componente Livewire 4 de arquivo único, como o contador de referência do projeto. `resources/views/experimental-classic.blade.php` fornece apenas o documento HTML e os recursos Vite da página experimental. O componente expõe um ID de partida protegido por `#[Locked]` e uma mensagem de apresentação; **não** expõe tabuleiro, turno ou vencedor como propriedades públicas mutáveis.

Ao clicar numa casa, o navegador envia somente seu índice. O componente lê o `MatchState` da sessão, usa `currentPlayer` para construir uma `GameAction('place')` e chama `Classic3x3Rules::apply()`. Apenas um `ActionResult` aceito é salvo. Para uma partida já encerrada, o contrato `GameAction` ainda exige um `PlayerMark`; o componente fornece X somente para permitir que a regra devolva `game_finished`, sem alterar o estado. O componente traduz o código de rejeição em mensagem amigável. Vitória, empate e turno exibidos vêm do estado reconstruído, não de regras repetidas na interface. Os botões de casas ocupadas ou de partida finalizada ficam desabilitados; `wire:loading.attr="disabled"` também bloqueia cliques repetidos durante uma requisição.

O reinício consulta o jogador inicial registrado no servidor, escolhe o outro lado e pede a `Classic3x3Rules::initialState()` um novo estado vazio. O mesmo ID de partida é mantido; a mensagem de erro é limpa. Não há placar acumulado.

## Estado, integridade e isolamento

`App\Http\SessionMatchStore` guarda uma estrutura de arrays e valores escalares na sessão Laravel, sob uma chave por ID aleatório alfanumérico de 40 caracteres. O registro contém o lado que iniciou a rodada e o estado codificado: modalidade, participantes, status, próximo jogador, vencedor e dados do tabuleiro. A reconstrução confere a modalidade `3-basic`, os dois participantes, enums, nove células com `''`, `x` ou `o` e as invariantes de `MatchState`. O armazenamento contém o formato concreto desta prova de conceito; não é uma infraestrutura genérica de persistência para outras modalidades.

O ID público é opaco e precisa existir **na mesma sessão** para leitura ou gravação. Conhecer um ID de outra sessão não dá acesso à partida. O navegador não envia nem pode substituir o estado oficial. Como é uma partida local, os visitantes X/O compartilham o dispositivo; ainda não há autenticação nem autorização individual por jogador.

Esta solução pressupõe sessões Laravel disponíveis e é adequada à prova de conceito em um único dispositivo. A proteção de interface reduz envios acidentais, mas duas requisições simultâneas de abas diferentes não recebem serialização transacional adicional nesta etapa. Antes de usar partidas concorrentes ou múltiplos servidores, será necessário definir armazenamento compartilhado, controle de concorrência, identidade/autorização por participante e uma política de recuperação de sessões expiradas. Se rotas forem compiladas em cache para implantação, esse cache deve ser gerado no ambiente de destino para preservar a restrição `local`/`testing`.

## Verificação

| Escopo | Resultado |
| --- | --- |
| `tests/Unit/Games` | 52 testes, 237 asserções aprovados. |
| `tests/Feature/ExperimentalClassic3x3Test.php` | 15 testes, 53 asserções aprovados: criação, jogadas, vitória, empate, reinício, persistência, isolamento, adulteração, estado malformado e ausência de rotas em produção. |
| `tests/Browser/ExperimentalClassic3x3BrowserTest.php` | 2 testes, 16 asserções aprovadas em Chromium: partida real, atualização reativa sem recarga, interação em celular de 360px, nenhuma rolagem horizontal e nenhum erro JavaScript. |
| `tests/Browser/ClassicGameplayTest.php` | 2 testes, 24 asserções aprovadas para o Clássico JavaScript original. |
| `npm run build` | Aprovado; necessário para gerar as classes Tailwind da nova view. |
| `vendor/bin/pint --dirty --format agent` | Aprovado. |

A primeira tentativa do novo teste de navegador encontrou o tabuleiro invisível porque o build Vite ainda era anterior à view. O build resolveu a condição; o cenário completo passou em seguida. A suíte completa das 14 modalidades não foi repetida, pois suas views, rotas e scripts não foram modificados. A migração definitiva deverá preservar a rota antiga até haver testes de paridade entre as duas interfaces e uma decisão explícita sobre identidade, concorrência e placar.
