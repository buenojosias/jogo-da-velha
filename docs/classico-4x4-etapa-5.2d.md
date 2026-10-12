# Etapa 5.2D — Consolidação do Clássico 4×4

## Levantamento e remoção

`resources/views/4x3-basic.blade.php` foi removida. A rota pública `4x3-basic` (`/4-3-basico`) já renderizava `experimental-classic-4x3`, e não havia referência executável à view antiga em `app/`, `routes/`, `resources/views/` ou `tests/`. O catálogo continua usando `route('4x3-basic')`. As menções à view antiga em documentos das etapas anteriores são registros históricos, não dependências da aplicação.

A view removida usava os componentes Blade legados `layout`, `board`, `footer` e `modal-win`, mas outras nove modalidades ainda usam esse conjunto. O `layout` continua carregando `public/js/game.js` para essas modalidades. Todos esses recursos foram preservados, sem refatoração. As rotas experimentais 4×4 permanecem restritas a `local` e `testing`.

## Compatibilidade

As rotas públicas `/3-basico` (`3-basic`) e `/4-3-basico` (`4x3-basic`) e os respectivos componentes Livewire não mudaram. As chaves `classic.active_match_id` e `classic4x3.active_match_id`, os registros `experimental_matches.{matchId}` e sua serialização também não mudaram; partidas existentes continuam retomáveis. Placar, reinício, Nova partida, temas, destaque de vitória e modal permanecem sob a cobertura existente.

Nenhum teste foi removido ou alterado. Os testes HTTP verificam as views modernas e a ausência de `js/game.js` nas entradas públicas; os testes de navegador cobrem jogo, navegação, temas e responsividade. Os testes do motor e da experiência experimental continuam preservados.

## Verificação

- Testes direcionados dos Clássicos públicos, integração experimental 4×4, páginas e navegação: **114 testes, 682 asserções, aprovados**.
- Validação global única: **339 testes, 2.058 asserções, aprovados**.
- `git diff --check`: aprovado.
- Pint e build Vite não foram executados: não houve alteração de PHP executável nem de assets do frontend; somente remoção de uma view Blade sem referências ativas e adição deste documento.

## Pendências

Os nomes `experimental` dos componentes e o prefixo `experimental_matches` permanecem para preservar a compatibilidade de sessões. A arquitetura continua baseada em sessão local, sem persistência entre dispositivos ou coordenação transacional de abas simultâneas. Eventuais renomeações e melhorias de concorrência exigem uma etapa própria.
