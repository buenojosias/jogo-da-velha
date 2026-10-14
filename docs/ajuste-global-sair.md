# Ajuste global: Sair

- **Componentes:** `match-header` perdeu o link Catálogo; `exit-match` concentra botão e diálogo. Os painéis de ações das 14 modalidades o incluem após Nova partida. `ExitMatchAction` reúne validação da partida ativa, detecção de progresso e descarte.
- **Detecção:** no servidor, rodada encerrada ou placar não zerado exige confirmação. Em rodada ativa, tabuleiros comuns são verificados por marcas; Dados considera os dois tabuleiros e rolagens pendente ou anterior; Maluca verifica as pilhas; Ultimate verifica os nove subtabuleiros e o alvo. Oculto usa somente o estado no servidor. Na Pontuação 5×5, os pontos decorrem das marcas do tabuleiro.
- **Decisão:** manter conserva ID, estado e placar; cancelar fecha o diálogo sem alteração; descartar valida o ID ativo e chama o gerenciador da modalidade para criar outra partida ativa com placar zerado. As outras modalidades permanecem intactas. Uma aba antiga não pode agir sobre o ID substituído.
- **Testes:** `tests/Feature/ExitMatchTest.php` (49 casos), `tests/Feature/ModalityPagesTest.php` (38 casos), `tests/Browser/ExitMatchBrowserTest.php` e `tests/Browser/PublicCatalogueBrowserTest.php` (9 casos). Pint, build Vite e `git diff --check` executados na validação final.
- **Limitação:** a retomada depende da duração da sessão Laravel; não há persistência permanente.
