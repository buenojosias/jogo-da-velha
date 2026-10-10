# Modernização — Etapa 2.1: testes de navegador

## Ambiente verificado

A suíte foi implementada e executada em 10 de outubro de 2026 com:

- PHP 8.4.25 e Laravel 13.35.0;
- Pest 4.7.8, Pest Plugin Laravel 4.1.0 e Pest Browser 4.3.1;
- Livewire 4.4.7;
- Playwright 1.64.0;
- Chromium instalado pelo Playwright em `~/.cache/ms-playwright/chromium-1248`;
- Vite 8.0.14 e Laravel Vite Plugin 3.1.0.

O `.env` define `APP_URL=http://localhost`, enquanto `phpunit.xml` força `APP_ENV=testing`. Nos testes de navegador, o Pest Browser inicia um servidor HTTP interno em `127.0.0.1`, escolhe uma porta livre e reescreve a URL da aplicação durante a execução. Não é necessário manter `php artisan serve` ou o servidor de desenvolvimento do Vite ativos. O manifesto de produção é gerado antes dos testes com `npm run build`.

O ambiente isolado usado na execução inicialmente impediu a abertura da porta TCP com `PortNotFoundException`. Isso é uma restrição do sandbox, não uma falha da aplicação. A mesma suíte foi repetida com permissão para sockets locais e passou integralmente.

## Estrutura implementada

| Arquivo | Responsabilidade | Testes | Asserções |
| --- | --- | ---: | ---: |
| `tests/Browser/ModalityNavigationTest.php` | Catálogo, links e renderização real das 14 modalidades | 15 | 80 |
| `tests/Browser/ClassicGameplayTest.php` | Fluxo completo do Clássico 3×3 e alternância no reinício | 2 | 24 |
| `tests/Browser/SpecialModalitiesTest.php` | Cenários representativos das demais famílias de regras | 10 | 55 |
| `tests/Browser/ResponsiveLayoutTest.php` | Quatro viewports no layout comum e nas páginas autônomas | 16 | 64 |
| `tests/Browser/LivewireIntegrationTest.php` | Atualização reativa real do contador de referência | 1 | 7 |
| **Total de navegador** |  | **44** | **230** |

`tests/Pest.php` registra `tests/Browser` no `TestCase` Laravel e contém apenas dois auxiliares para selecionar e clicar nas células do layout compartilhado. `phpunit.xml` inclui a suíte `Browser` para que a execução completa também descubra esses testes. As pastas de screenshots e traces já estão ignoradas pelo Git.

## Navegação e estrutura

A home é aberta em Chromium real, exige exatamente 14 links e confirma um link para cada modalidade. Cada rota é então aberta individualmente, com verificação do título, tabuleiro visível, quantidade de casas e ausência de erros JavaScript não tratados.

Além da estrutura comum, os testes confirmam:

- Dados da Velha: dois tabuleiros, 18 casas, dado e botão de rolagem;
- Conquista Maluca: nove casas e nove peças disponíveis para cada jogador;
- Ultimate: nove subtabuleiros, 81 casas e os nove subtabuleiros inicialmente disponíveis.

## Jogabilidade coberta

### Clássico 3×3

O cenário não presume que X começa. Ele lê o jogador inicial escolhido pela implementação e monta a sequência em relação a esse valor. São verificados:

- tabuleiro vazio e indicador inicial coerente;
- colocação da primeira peça e alternância de turno;
- impossibilidade de sobrescrever uma casa ocupada;
- vitória em linha, destaque das três casas, modal e placar;
- reinício pelo modal e pelo botão principal;
- limpeza do tabuleiro e alternância do jogador inicial.

### Famílias de regras

- Movimento: seis peças são colocadas e uma peça própria é selecionada e movida para uma casa vazia.
- Rotação: após seis colocações, uma nova peça remove a mais antiga do jogador atual.
- Bloqueio: uma linha horizontal bloqueada não encerra o jogo; uma linha horizontal válida posterior vence.
- Oculto: as peças permanecem com `piece-hidden` durante a partida e são reveladas após a vitória.
- Linha de quatro: uma sequência de quatro encerra a partida e destaca quatro casas.
- Duas Linhas 5×5: a primeira linha não vence; uma segunda linha distinta abre o modal.
- Pontuação 5×5: o JavaScript real da página calcula 3, 6 e 10 pontos para sequências máximas de 3, 4 e 5 peças.
- Dados: o teste aguarda uma coluna sorteada, rejeita uma casa de outro tabuleiro/coluna sem alterar o estado e realiza uma jogada na coluna permitida. Nenhum valor aleatório é previsto ou substituído.
- Maluca: o jogador inicial observado seleciona e coloca uma peça pequena; depois cobre uma peça pequena adversária com uma peça média.
- Ultimate: uma jogada na quinta casa direciona o oponente exclusivamente ao quinto subtabuleiro.

As modalidades Clássico 4×4, Movimento 4×4 e Rotação 4×4 possuem cobertura estrutural direta e cobertura funcional indireta pelas respectivas famílias de regra. Não foram criadas sequências duplicadas apenas para repetir a mesma política em outro tamanho de tabuleiro.

## Integração Livewire

A rota `/livewire-reference` renderiza o componente `reference-counter` apenas nos ambientes `local` e `testing`. Uma execução com `APP_ENV=production` confirmou que a rota não é registrada. A view de demonstração usa o pipeline Vite existente e não foi incorporada à home nem às modalidades.

No Chromium, o teste confirma o estado inicial zero, clica na Action `increment`, aguarda o valor reativo igual a um e verifica que uma marca JavaScript criada antes do clique continua presente. Isso comprova a comunicação com o servidor sem recarregamento completo do documento. Nenhum erro JavaScript foi registrado.

## Responsividade

Foram exercitadas as larguras solicitadas: 360, 390, 768 e 1280 pixels.

No Clássico 3×3, cada viewport exige tabuleiro, controle de reinício e célula visíveis, ausência de rolagem horizontal, centro da célula sem sobreposição e uma jogada clicável. Esses 4 cenários passaram.

Dados, Maluca e Ultimate possuem 12 cenários adicionais que confirmam que os tabuleiros e controles centrais são renderizados nas quatro larguras, sem erros JavaScript. Esses cenários não afirmam ausência de overflow ou boa usabilidade móvel, porque isso esconderia defeitos conhecidos:

- Dados usa dois tabuleiros fixos de 380 px, separação de 80 px e `body { overflow: hidden; }`; em 360, 390 e 768 px parte do jogo fica recortada e não pode ser alcançada por rolagem horizontal.
- Maluca usa um tabuleiro fixo de aproximadamente 376 px, somado ao padding do `body`; em 360 e 390 px há largura insuficiente e risco de overflow horizontal. A reserva de peças também exige uma área vertical extensa.
- Ultimate adapta o tabuleiro com `vw`, mas combina largura de 96vw, padding do `body`, destaque por escala e uma grade quadrada; os viewports móveis continuam sujeitos a overflow e compressão dos controles.

Os testes funcionais das três modalidades especiais comprovam interação em viewport desktop. Verificações móveis rigorosas de ausência de corte, sobreposição e acesso a todas as casas devem ser ativadas como regressão somente quando a etapa visual autorizada corrigir esses problemas.

## Problemas preexistentes e cenários pendentes

Nenhum problema da auditoria foi corrigido nesta etapa:

1. `/teste` continua referenciando a view inexistente `teste`. Permanece fora das 14 modalidades e da suíte aprovada.
2. Bloqueio diagonal continua com validação aritmética que pode considerar como bloqueadora uma casa fora da continuação geométrica. A suíte aprovada cobre bloqueio e vitória horizontais; os casos diagonais documentados na auditoria devem virar testes de regressão falhos imediatamente antes da correção.
3. O modal de Pontuação 5×5 continua compondo a mensagem dinâmica com o texto fixo do componente. O cálculo 3/6/10 está coberto, mas o cenário de preenchimento completo e mensagem final permanece pendente.
4. Dados continua renderizando dois elementos com `id="status"`. Os seletores do teste não dependem desse ID duplicado.
5. Dados continua aceitando múltiplos cliques durante os 1,1 segundos anteriores à definição de `currentColumn`. O teste principal faz uma rolagem por turno; um teste que exija exclusão mútua falharia no código atual e será acrescentado junto da correção.

Os cenários acima não foram marcados como aprovados, ignorados genericamente nem transformados em expectativas que congelem o defeito. Permanecem explicitamente separados da suíte verde.

Outras limitações:

- não há regressão visual por screenshots; screenshots automáticas de falhas são artefatos locais ignorados;
- não são cobertas todas as combinações de vitória, empate ou estados terminais;
- Pontuação é testada pela execução das funções reais no contexto do navegador, sem preencher manualmente as 25 casas;
- não há teste multi-browser; a referência atual é Chromium;
- o Pest Browser precisa de permissão para abrir sockets locais no ambiente de execução.

## Resultados finais

- `npm run build`: aprovado com Vite 8.0.14;
- `php artisan test --compact tests/Browser`: 44 testes, 230 asserções, todos aprovados;
- suíte preexistente (`tests/Unit`, `tests/Feature` e componente Livewire): 45 testes, 186 asserções, todos aprovados;
- `php artisan test --compact`: 89 testes, 416 asserções, todos aprovados;
- `vendor/bin/pint --dirty --format agent`: aprovado.

## Recomendações para as próximas etapas

1. Corrigir cada problema conhecido em uma mudança própria, ativando antes o respectivo teste de regressão que hoje falharia.
2. Após autorização para alterações visuais, tornar obrigatórias as assertions móveis de overflow, área clicável e ausência de sobreposição em Dados, Maluca e Ultimate.
3. Preservar os cenários de navegador como testes de ponta a ponta e mover a cobertura combinatória para as futuras classes PHP puras, onde será mais rápida e determinística.
4. Manter a rota de referência fora de produção e removê-la quando um componente Livewire real passar a fornecer a mesma prova de integração.
5. Executar build e testes sequencialmente para evitar concorrência sobre o manifesto Vite.
