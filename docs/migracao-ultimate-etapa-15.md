# Etapa 15 — Ultimate

`/ultimate` (`ultimate`) agora usa `UltimateRules` e Livewire. O estado oficial guarda nove arrays de nove casas (`boards`) e `target_board` (índice 0–8 ou `null`). Conquistas, empates locais e vitória global são derivados das casas; não existem contadores duplicados. A ação `place` envia apenas subtabuleiro e casa. O índice da casa direciona a próxima jogada; destino encerrado libera a escolha entre os demais subtabuleiros abertos. Vitória global prevalece sobre empate na última jogada.

`SessionMatchStore` valida formato, destino, contagem de marcas, resultados e estados terminais. A chave ativa independente é `ultimate.active_match_id`, mantendo os registros `experimental_matches.` e as 13 modalidades anteriores. Próxima rodada mantém ID/placar e alterna o inicial; Nova partida requer confirmação, cria outro ID e zera o placar. Abas com IDs públicos antigos não podem agir na nova partida.

A interface mostra a grade completa como visão geral e um subtabuleiro ampliado para interação, especialmente no celular. Subtabuleiros conquistados e empatados têm rótulos textuais, sem sobrepor e ocultar suas jogadas. O foco visual da seleção não altera as regras do servidor. Temas, placar e diálogos seguem o design system existente.

Criados: `UltimateRules`, `UltimateMatchManager`, `ultimate-match.blade.php`, componente `⚡ultimate-match.blade.php` e testes de domínio, sessão e navegador. Alterados: `SessionMatchStore`, rota, CSS restrito a `[x-cloak]` experimental e testes de navegação/estrutura. Removida somente a view legada `resources/views/ultimate.blade.php`; `public/js/game.js` e demais recursos compartilhados permanecem.

Limites: partida local por sessão, sem autenticação nem sincronização distribuída entre abas. Em celulares, a visão geral é informativa e a jogada ocorre no painel ampliado; a rolagem vertical é permitida.

Verificação: testes unitários de direcionamento, fechamento local/global, empate e estados inválidos; testes públicos de retomada, placar, isolamento e abas antigas; navegador real para vitória global, empate após 67 jogadas, tema, confirmação e larguras 360/740/800/1280 px. Pint, `npm run build` e `git diff --check` passaram. Suíte global: **935 testes e 5.791 asserções aprovados**. Depois dela, somente o teste de isolamento de sessão e propriedades Livewire recebeu asserções adicionais e foi reexecutado isoladamente: **1 teste, 9 asserções, aprovado**; nenhuma mudança de produção ocorreu após a suíte global.
