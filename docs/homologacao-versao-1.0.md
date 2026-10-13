# Homologação pré-publicação da versão 1.0

## Decisão

**Apta para homologação na VPS.** Os testes e o build passaram no ambiente de desenvolvimento após a correção de um defeito visual reproduzido em telas horizontais baixas. Esta decisão autoriza a próxima fase de instalação e verificação no ambiente real; **não libera o lançamento público**. O aviso de privacidade continua como rascunho, e domínio, infraestrutura, tratamento efetivo de dados e concorrência com o driver de sessão da VPS ainda dependem de validação.

## Verificações e resultados

| Verificação | Resultado |
| --- | --- |
| Suíte global inicial: `php artisan test --compact` | 1.001 testes, 993 aprovados e 8 falhas de layout no Chromium: Clássico 3×3 e Maluca em 740×360 e 800×360, temas claro e escuro. |
| Testes Browser direcionados dos oito cenários afetados | 8 aprovados, 76 asserções, após a correção. |
| Suíte global de confirmação | **1.001 testes aprovados, 6.305 asserções.** Executada uma vez após concluir a correção. |
| Browser adicional do catálogo e das instruções | 6 testes e 25 asserções aprovados; inclui catálogo em 360×800, 740×360, 800×360 e 1280×900, ida e volta da partida com retomada, e abertura de “Como jogar” no Ultimate em 740×360. |
| Laravel Pint | Aprovado. |
| `npm run build` | Aprovado; `public/build/manifest.json` gerado e `public/hot` ausente. |
| `composer check-platform-reqs --no-dev` | Aprovado no PHP local 8.4.25. Repetir na VPS com o driver PDO escolhido. |
| `git diff --check` | Aprovado. |

A suíte existente cobre rotas e links das 14 modalidades, instruções, jogadas, reinício, próxima rodada, nova partida, retomada, isolamento entre sessões, placar, temas, metadados, sitemap e fluxos Browser de Dados, Maluca e Ultimate. A homologação Browser usa Chromium automatizado; ela não substitui testes em dispositivos físicos nem avaliação com leitor de tela.

## Correção necessária

As falhas mostraram que a segunda linha do cabeçalho compartilhado empurrava tabuleiro e controles para fora da área visível em 360 px de altura. `resources/views/components/design-system/match-header.blade.php` passou a agrupar “Como jogar” com o título; `resources/css/app.css` o posiciona ao lado do título somente em telas horizontais baixas. Um teste Browser em `tests/Browser/PublicCatalogueBrowserTest.php` verifica a abertura das instruções sem rolagem horizontal. Não houve mudança em motores, regras, sessões, rotas públicas ou dependências.

## Preparação de produção e privacidade

- A configuração recomendada em `docs/preparacao-producao-etapa-20.md` permanece aplicável: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS real, `APP_KEY` estável, cookies seguros, sessão e cache persistentes, logs protegidos, raiz HTTP em `public/`, diretórios graváveis e assets de produção. A escolha do banco, do driver de sessão/cache e dos prazos ainda depende da VPS. O `.env.example` contém valores locais e não deve ser usado como configuração final.
- A inspeção sob `APP_ENV=production` confirmou as 14 rotas de jogo e a ausência de `livewire-reference`. Com dependências de desenvolvimento instaladas localmente, apareceu `POST /_boost/browser-logs`; a instalação de produção deve usar `composer install --no-dev`, descobrir os pacotes novamente e confirmar que essa rota não existe antes de gerar ou ativar o cache de rotas. As rotas privadas `/storage/{path}` do Laravel exigem URL assinada. Nenhuma rota técnica aparece no sitemap.
- Títulos, descriptions, canonical, Open Graph, Twitter/X, ícones, imagem PNG e sitemap são cobertos pelos testes. As URLs absolutas dependem de `APP_URL`; nenhum domínio público definitivo foi fixado no código. `.env` está ignorado e a inspeção dos arquivos versionados não encontrou chaves ou credenciais; `.env.example` contém apenas exemplos.
- `/privacidade` permanece visivelmente marcado **“Rascunho pendente de revisão”**, com `noindex` e fora do sitemap. Após esta homologação, o proprietário forneceu o nome do responsável, Josias Pereira Bueno, pessoa física, e o contato `velha@jwd.com.br`; o aviso foi atualizado com o texto fornecido. Ainda faltam domínio, provedor e local de hospedagem, driver e prazo de retenção das sessões, categorias e prazos dos logs da VPS/proxy, confirmação de eventuais serviços externos, revisão da base legal e do atendimento a solicitações de titulares, e data da versão definitiva. **Não publicar** antes de revisar o aviso definitivo e as configurações reais de tratamento de dados.

## Checklist para implantação e homologação na VPS

1. Definir domínio e HTTPS, servidor HTTP com raiz exclusiva em `public/`, processo PHP, banco persistente, driver PDO, armazenamento de sessão/cache e locais de logs. Instalar PHP 8.4 e verificar os requisitos com Composer.
2. Criar `.env` privado com valores reais, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `APP_KEY` gerada uma vez e preservada, cookies seguros e duração de sessão deliberada. Instalar dependências com `--no-dev`, publicar o build Vite, conceder escrita apenas onde necessário, executar migrations aplicáveis e `php artisan optimize` após fixar o ambiente.
3. Confirmar HTTPS, ausência de debug e de `/livewire-reference` e `/_boost/browser-logs`, indisponibilidade HTTP de `.env`, `composer.json` e `storage/`, manifest e assets servidos, metadados/canonical/robots/sitemap com domínio real e cookie com `Secure`, `HttpOnly` e `SameSite` esperados.
4. Em navegador real, verificar catálogo e uma partida em celular e desktop, jogada, atualização da página, retorno ao catálogo, placar, nova partida, temas, instruções e resultado. Usar outra sessão para confirmar isolamento. Inspecionar logs e erros 419/500.
5. Validar duas abas e requisições simultâneas com o **driver real** de sessão e cache. O bloqueio nativo não foi configurado especulativamente; se a concorrência observada exigir, definir e testar locks para as rotas de entrada e atualizações Livewire antes da publicação.
6. Revisar e substituir o rascunho de privacidade com dados reais antes de liberar o site ao público. Para atualizações, preservar `APP_KEY`, banco/sessões, cookies e armazenamento compartilhado; preparar release separado e reversão conforme a Etapa 20, sem limpar sessões como rotina.

Nenhum deploy, alteração de infraestrutura ou commit foi realizado nesta etapa.
