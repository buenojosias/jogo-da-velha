# Consolidação do Clássico 3×3 — Etapa 4.3C

## Limpeza e dependências

A rota pública `/3-basico` (`3-basic`) permanece na implementação Livewire/PHP. Foram removidos somente a rota temporária `/3-basico-legado` (`legacy.classic`), a view exclusiva `resources/views/3-basic.blade.php` e `tests/Browser/ClassicParityTest.php`. O catálogo e as outras 13 rotas não foram alterados. Um teste HTTP agora protege a ausência da rota legada também no ambiente de testes; o teste existente continua verificando a ausência em produção.

`public/js/game.js` e os componentes Blade `layout`, `board`, `playing`, `footer` e `modal-win` continuam em uso pelas demais modalidades com layout compartilhado e foram preservados. Os helpers de navegador em `tests/Pest.php` também continuam necessários aos testes das outras modalidades. As views de Dados, Maluca e Ultimate possuem seus próprios scripts e não foram modificadas.

## Destino da paridade

Os sete casos de paridade dependiam da view antiga apenas para comparar duas implementações. Depois da migração pública, a proteção relevante permanece distribuída entre:

- `Classic3x3RulesTest.php`: jogadas, rejeições, vitórias horizontais/verticais/diagonais de X e O, empate, vitória na nona jogada e bloqueio após o término.
- `ExperimentalClassic3x3Test.php` e `PublicClassic3x3Test.php`: integração Livewire, resultado, placar, reinício, nova partida, retomada, isolamento e integridade do estado.
- `ClassicGameplayTest.php`, `ExperimentalClassic3x3BrowserTest.php`, `ThemeFoundationTest.php` e `ResponsiveLayoutTest.php`: jogo real, modal, temas, acessibilidade e tamanhos de tela. A rota pública usa a mesma view e o mesmo componente testados pela rota experimental.
- `ModalityPagesTest.php` e `ModalityNavigationTest.php`: catálogo e renderização das 14 modalidades.

Não foi identificada uma lacuna funcional exclusiva da suíte de paridade que justificasse mantê-la ou duplicar cenários do motor de regras no navegador. Os relatórios históricos das etapas anteriores permanecem como registro do estado naquele momento.

## Nomes e sessões

Foram mantidos `experimental-classic`, `experimental-classic-3x3`, as rotas experimentais restritas a `local`/`testing` e a chave de sessão `experimental_matches`. Renomear esses identificadores agora exigiria atualizar referências e migrar dados de sessões existentes sem trazer benefício funcional imediato. A chave `classic.active_match_id`, o formato das partidas, o placar e os IDs de partida não mudaram; partidas ativas válidas continuam recuperáveis. A eventual renomeação fica para uma etapa específica, com estratégia de compatibilidade de sessões.

## Verificação

| Execução | Resultado |
| --- | --- |
| Game Engine, Livewire, fluxo público e páginas HTTP | 143 testes, 691 asserções aprovados |
| Navegador: Clássico público/experimental, temas, responsividade e navegação das 14 modalidades | 63 testes, 530 asserções aprovados |
| `npm run build` | Aprovado |
| `vendor/bin/pint --dirty --format agent` | Aprovado |
| Suíte global única, `php artisan test --compact` | **223 testes, 1.293 asserções aprovados** |

A redução frente aos 229 testes da etapa anterior decorre da remoção dos sete testes de paridade e da inclusão do teste de ausência da rota legada; não houve falhas na suíte global. As outras 13 modalidades seguem cobertas pelos testes estruturais e de navegação existentes, sem alteração de seus recursos compartilhados.
