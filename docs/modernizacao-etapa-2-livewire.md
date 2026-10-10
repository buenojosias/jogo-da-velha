# Modernização — Etapa 2: infraestrutura Livewire 4

## Dependência e compatibilidade

Foi instalado `livewire/livewire` 4.4.7 com a restrição `^4.0`. O Composer realizou uma instalação e nenhuma atualização ou remoção de pacotes existentes.

A versão é compatível com o ambiente atual:

- PHP 8.4, enquanto Livewire exige PHP 8.1 ou superior;
- Laravel 13.35.0, suportado explicitamente pelas restrições `illuminate/*` do Livewire 4.4.7;
- Pest 4.7.8 e Pest Plugin Laravel 4.1.0;
- Tailwind CSS 4.3.0, Vite 8.0.14 e Laravel Vite Plugin 3.1.0.

Nenhum pacote de autenticação, UI, broadcasting ou teste em navegador foi adicionado.

O `post-update-cmd` já existente no projeto executou `boost:update` após a instalação. Por isso, o Laravel Boost atualizou automaticamente `AGENTS.md`, `boost.json`, a skill de inferência de convenções e adicionou a skill `livewire-development`. Essas alterações mantêm as orientações dos agentes alinhadas ao pacote instalado; não houve edição manual desses arquivos.

## Estratégia de integração frontend

Foi mantida a integração convencional e zero-config do Livewire 4:

- o `LivewireServiceProvider` é carregado pelo package auto-discovery do Laravel;
- os assets do Livewire são injetados automaticamente apenas em páginas que renderizam componentes Livewire;
- não foi publicado `config/livewire.php`, pois nenhuma configuração fora do padrão é necessária;
- não foram publicados assets do Livewire em `public/`;
- não foi criada importação manual do Livewire no Vite;
- `resources/js/app.js` não carrega Alpine.js, portanto não existe duplicação; componentes Livewire usarão o Alpine incluído e inicializado pelo próprio Livewire;
- `resources/css/app.css`, `resources/js/app.js`, `vite.config.js` e `public/js/game.js` permaneceram inalterados.

Como o componente de referência não foi incorporado a uma rota, à home ou às modalidades, as páginas existentes não recebem assets Livewire e mantêm seu HTML e JavaScript atuais. Quando uma página passar a renderizar Livewire em uma etapa futura, a injeção automática será suficiente; diretivas explícitas `@livewireStyles` e `@livewireScripts` só deverão ser adotadas caso o futuro layout exija controle de posição.

## Componente de referência

Foi adotado o formato single-file component (SFC), padrão recomendado pelo Livewire 4 quando o projeto ainda não possui convenção própria:

- componente: `resources/views/components/⚡reference-counter.blade.php`;
- teste colocalizado: `resources/views/components/⚡reference-counter.test.php`;
- nome Livewire: `reference-counter`.

O componente possui a propriedade pública tipada `count`, inicialmente igual a zero, e a Action `increment()`. O botão `wire:click="increment"` demonstra a ligação reativa entre a Action e o valor renderizado. O componente permanece deliberadamente isolado e não representa uma modalidade, layout definitivo ou regra de jogo.

Para que o Pest descubra testes SFC colocalizados, `tests/Pest.php` passou a incluir `resources/views`, e `phpunit.xml` recebeu a suíte `Components` com o sufixo `.test.php`, conforme a documentação do Livewire 4.

## Cobertura e resultados

O teste do componente cobre separadamente:

- renderização inicial;
- estado inicial da propriedade pública;
- execução da Action `increment`;
- atualização do estado e do HTML após a Action;
- alteração direta da propriedade pública e nova renderização.

Resultados finais:

- componente de referência: 4 testes, 8 asserções, todos aprovados;
- regressão `tests/Feature/ModalityPagesTest.php`: 39 testes, 176 asserções, todos aprovados;
- suíte completa: 45 testes, 186 asserções, todos aprovados;
- `vendor/bin/pint --dirty --format agent`: aprovado;
- `composer validate --strict`: manifesto e lockfile válidos;
- `npm run build`: concluído com Vite 8.0.14.

## Limitações

Os testes `Livewire::test()` exercitam renderização, hidratação, propriedade pública e Action no ambiente de testes do Livewire, mas não executam um navegador real. O projeto continua sem `pestphp/pest-plugin-browser` e Playwright; por isso não foi possível validar um clique físico, a requisição de atualização no navegador ou o console JavaScript sem adicionar dependências fora do escopo.

Essa limitação não afeta os testes de componente desta etapa, mas deverá ser coberta antes das migrações de interações mais complexas.

## Pontos de atenção para a Etapa 3

1. Definir o limite entre estado da interface Livewire e regras PHP independentes antes de migrar a primeira modalidade.
2. Escolher e documentar um layout Livewire comum antes de criar páginas completas; somente então decidir entre injeção automática e diretivas explícitas de assets.
3. Manter Alpine fornecido pelo Livewire como única instância, salvo necessidade futura de plugins que justifique bundling manual.
4. Preservar `public/js/game.js` e os scripts inline durante migrações incrementais, evitando montar Livewire sobre os mesmos IDs e listeners antes da retirada planejada de cada implementação antiga.
5. Adicionar teste de navegador somente com autorização para instalar o plugin Pest Browser e o Playwright.
