# Experiência pública — Etapa 19

## Decisões e mudanças

- A página inicial apresenta a proposta, o acesso gratuito sem cadastro e a partida local para duas pessoas. O catálogo mostra as 14 rotas públicas em quatro grupos, com Clássico 3×3 em destaque.
- As prévias são decorativas e feitas com HTML e CSS. Incluem grades 3×3, 4×4 e 5×5, dois tabuleiros em Dados, peças de tamanhos diferentes em Maluca e nove subtabuleiros em Ultimate.
- O cabeçalho compartilhado das partidas agora oferece retorno ao catálogo e um painel nativo “Como jogar”. As explicações cobrem a jogada, a condição especial e a vitória ou pontuação; foram conferidas com os motores das modalidades especiais. Nenhuma regra ou sessão foi alterada.
- A seleção de tema é compartilhada com as partidas. Links, áreas de toque e foco seguem o design system existente.

## Cobertura e validação

- Feature: conteúdo essencial, 14 links, retorno e instruções das 14 modalidades; testes existentes de rotas e componentes afetados.
- Browser: catálogo nos tamanhos 360×800, 740×360, 800×360 e 1280×900; navegação até uma partida e retomada da jogada após voltar ao catálogo.
- Pint, build Vite e `git diff --check` executados.
- A suíte global ficou reservada para a homologação da versão 1.0; não foi executada nesta etapa.

## Pendências

- Fazer a homologação completa da versão 1.0, incluindo a suíte global, antes do lançamento público. Preparação de VPS pertence à Etapa 20.
