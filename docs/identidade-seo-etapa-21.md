# Identidade, SEO e compartilhamento — Etapa 21

## Metadados e arquivos

- `config/public_pages.php` define título e descrição específicos para o catálogo e as 14 modalidades. `resources/views/components/public-meta.blade.php` emite título HTML, description, canonical, Open Graph, Twitter/X Card, favicon e referência ao sitemap. `resources/views/welcome.blade.php` e as páginas de partida `experimental-classic`, `experimental-move-3x3`, `rotation-match`, `experimental-classic-4x3`, `experimental-move-4x3`, `block-4x3`, `hidden-4x3`, `experimental-move-4x4`, `two-lines-5x5`, `full-5x5`, `dice-match`, `crazy-match` e `ultimate-match` (todas `.blade.php`) usam esse componente; as rotas e os componentes de jogabilidade permaneceram iguais.
- `public/icon.svg` e `public/favicon.ico` substituem o favicon anterior, que estava vazio. `public/share-card.png` (1200×630) é a imagem Open Graph/Twitter; `public/share-card.svg` guarda a arte vetorial de origem. A imagem segue as cores e formas X/O do catálogo, sem recurso externo em tempo de execução.
- `routes/web.php` adiciona `/sitemap.xml`, `/robots.txt` e `/privacidade`; `resources/views/sitemap.blade.php` gera o XML; `resources/views/privacy.blade.php` traz o rascunho institucional. O antigo `public/robots.txt` estático foi substituído pela resposta dinâmica. `resources/views/welcome.blade.php` ganhou somente um link discreto no rodapé. `tests/Feature/PublicMetadataTest.php` cobre a entrega.

## Compartilhamento e indexação

Canonical, `og:url` e URLs de imagem usam `APP_URL` de `config/app.php` e o caminho da rota nomeada; nenhum domínio de produção está fixado. Antes de publicar, o proprietário deverá configurar `APP_URL` com o endereço HTTPS real e validar os metadados públicos. Todas as modalidades compartilham a mesma arte PNG; título e descrição são específicos por rota.

O sitemap lista **somente** a página inicial e as 14 modalidades. `/robots.txt` permite indexação e aponta ao sitemap com URL absoluta derivada de `APP_URL`; as páginas indexáveis também apontam para ele por `rel="sitemap"`. A página de privacidade está em rascunho, tem `noindex` e não entra no sitemap. Endpoints Livewire, rota de referência local e IDs de sessão também não entram.

## Privacidade e informações pendentes

O aviso em `/privacidade` descreve o cookie de sessão, o armazenamento local da preferência de tema (`jogo-da-velha.theme`) e os registros técnicos que a hospedagem pode gerar. Se o driver `database` for adotado, o handler de sessão do Laravel também grava IP, user agent e última atividade na tabela `sessions`. O código inspecionado não contém scripts de análise, publicidade ou rastreamento. Nenhum banner de consentimento foi incluído apenas pelo cookie de sessão. A necessidade de mecanismos adicionais deverá ser reavaliada se a implantação ou serviços futuros introduzirem outros tratamentos. O [guia da ANPD sobre cookies](https://www.gov.br/anpd/pt-br/centrais-de-conteudo/materiais-educativos-e-publicacoes/guia_orientativo_cookies_e_protecao_de_dados_pessoais) orienta a transparência e diferencia cookies necessários dos não necessários.

O proprietário pediu que a política permaneça **rascunho pendente de revisão**. Para finalizá-la, faltam exatamente: identificação de quem responde pela plataforma (pessoa ou entidade e dados de identificação aplicáveis), canal de contato para privacidade, domínio final, provedor e local de hospedagem, driver e prazo de retenção da sessão, categorias e prazos reais dos logs da VPS/proxy, eventuais serviços externos, e revisão da base legal e do atendimento a solicitações de titulares. Não foram inventados nome empresarial, CNPJ, endereço ou contato.

## Verificações

- `tests/Feature/PublicMetadataTest.php`: 18 casos cobrindo os 15 títulos, descrições, canonical, Open Graph/Twitter, sitemap, `robots.txt`, assets e aviso de privacidade.
- Testes direcionados existentes de páginas públicas e catálogo foram executados. Pint, build Vite e `git diff --check` concluídos.
- Suíte global e matriz Browser completa **não executadas**; ficam para a homologação 1.0.
