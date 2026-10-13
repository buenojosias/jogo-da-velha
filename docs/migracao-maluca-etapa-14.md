# Etapa 14 — Conquista Maluca

`/maluca` (`crazy`) usa agora `CrazyRules` e Livewire. As nove casas guardam pilhas de IDs `jogador-tamanho-cópia` (por exemplo, `x-2-1`); o estoque disponível é derivado desses IDs, sem contadores duplicados. Uma ação `place` envia apenas o ID e a casa; o servidor valida proprietário, uso único e cobertura por tamanho estritamente maior.

O motor avalia **todas** as oito linhas candidatas. Uma linha visível só encerra a rodada quando o adversário não possui peça não usada capaz de cobrir qualquer topo da linha. Assim, uma linha ainda cobrável não oculta outra definitiva. O legado sobrescrevia a última linha encontrada; a interpretação adotada preserva a regra pretendida e elimina essa dependência da ordem de iteração. As 18 peças usadas sem vitória definitiva produzem empate.

O registro mantém o prefixo `experimental_matches.`; a referência pública independente é `crazy.active_match_id`. Nova partida troca o ID e zera o placar mediante confirmação; Próxima rodada mantém ID/placar e alterna o inicial. Uma aba antiga não pode agir na partida pública nova. A seleção de peça fica somente em Alpine e é limpa após ação aceita, reinício ou recarga.

Criados: `app/Games/Rules/CrazyRules.php`, `app/Http/CrazyMatchManager.php`, `resources/views/crazy-match.blade.php`, `resources/views/components/⚡crazy-match.blade.php`, testes unitários, públicos e de navegador. Alterados: `SessionMatchStore`, rota e testes de navegação/responsividade. Removida somente `resources/views/crazy.blade.php`. `public/js/game.js` e a Ultimate permanecem intactos.

Limite atual: é uma partida local por sessão, sem autenticação ou sincronização completa entre abas. O topo e a quantidade na pilha são visíveis; as peças cobertas não podem ser recuperadas.

Verificação: testes direcionados de motor, rota e sessão; navegador real para empilhamento, vitória, retomada, confirmação de Nova partida, temas e larguras 360/740/800/1280 px; Pint, `npm run build` e `git diff --check` aprovados. Suíte global final: **891 testes e 5.443 asserções aprovados**.
