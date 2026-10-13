# Preparação para produção em VPS — Etapa 20

## 1. Requisitos da VPS

- Linux com servidor HTTP capaz de encaminhar PHP ao processo PHP (por exemplo, PHP-FPM), HTTPS e raiz pública apontada **somente** para `public/`. O provedor, a distribuição e o servidor HTTP permanecem em aberto. Nunca publicar a raiz do repositório, `.env`, `storage/`, `vendor/` ou `database/` como diretório web.
- PHP **8.4**, conforme o projeto, e Composer. `composer.json` aceita `^8.3`, mas a versão instalada e usada no desenvolvimento é 8.4. Confirmar as extensões com `composer check-platform-reqs --no-dev` na VPS. O conjunto usado inclui `ctype`, `filter`, `hash`, `mbstring`, `openssl`, `session`, `tokenizer`, `json` e PDO com o driver da base escolhida (por exemplo, `pdo_mysql` para MySQL/MariaDB ou `pdo_sqlite` para SQLite). `fileinfo`, `curl` e `zip` são úteis para operação e instalação. A extensão Redis só é necessária se Redis for escolhido.
- Para gerar assets na VPS: Node compatível com Vite 8 (`^20.19.0` ou `>=22.12.0`) e npm. Alternativamente, gerar `public/build` em ambiente de build confiável e publicá-lo junto com a versão; o servidor não precisa de Node nesse caso.
- Se forem escolhidos `SESSION_DRIVER=database` e `CACHE_STORE=database`, manter um banco persistente e executar as migrations que já criam `sessions`, `cache` e `cache_locks`. A escolha e o dimensionamento do banco ficam para a infraestrutura.
- Usuário do processo PHP com escrita em `storage/` e `bootstrap/cache/`; demais arquivos apenas com as permissões necessárias para leitura. O arquivo `.env` deve permanecer fora de acesso HTTP e legível apenas pelas contas operacionais necessárias. Não usar permissões globais `777`.

## 2. Configuração recomendada

Definir no ambiente real, sem versionar valores ou segredos:

| Variável ou recurso | Recomendação | Motivo |
| --- | --- | --- |
| `APP_ENV`, `APP_DEBUG` | `production`, `false` | Erros internos não devem aparecer ao público; a rota `livewire-reference` só é registrada em `local` ou `testing`. |
| `APP_KEY` | Gerar **uma vez**, guardar com segurança e reutilizar em todas as versões | Troca da chave invalida cookies criptografados e pode interromper sessões. Nunca copiar uma chave de exemplo. |
| `APP_URL` | URL pública real em `https://` | Links absolutos e comandos CLI devem refletir o domínio definitivo. |
| `SESSION_DRIVER` | Preferir `database` numa VPS simples, com banco persistente | A tabela já existe na migration; sobrevive a trocas de release e dispensa Redis. |
| `CACHE_STORE` | `database` se adotado o banco acima | A tabela `cache_locks` já existe e o driver suporta locks atômicos. Não ativar bloqueio de sessão sem validar o store. |
| `SESSION_LIFETIME` | Escolher conscientemente o tempo ocioso da partida; valor atual: 120 minutos | Expiração encerra o acesso ao estado oficial. |
| `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE` | `true`, `true`, `lax` sob HTTPS | Restringe envio a HTTPS e acesso do JavaScript ao cookie; preserva navegação normal. |
| `SESSION_DOMAIN` | Deixar sem domínio explícito, salvo necessidade demonstrada | Mantém o cookie restrito ao host. |
| `LOG_CHANNEL`, `LOG_LEVEL` | Por exemplo, `daily` e `warning`, com retenção definida | Reduz volume e evita debug em produção. Proteger e rotacionar `storage/logs`. |

A `.env.example` é de desenvolvimento: `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost` e `LOG_LEVEL=debug` **não** são valores de produção. `APP_KEY`, endereço, credenciais, domínio e duração final da sessão dependem do ambiente real. Se HTTPS terminar em proxy, configurar apenas os proxies e cabeçalhos confiáveis no Laravel após conhecer a topologia; validar que o aplicativo reconhece a requisição como segura. Não confiar indiscriminadamente em cabeçalhos enviados pelo cliente.

### Comparação dos drivers de sessão

| Driver | Persistência e operação | Concorrência e implicação |
| --- | --- | --- |
| `file` | Simples em um único host; exige `storage/framework/sessions` compartilhado entre versões e preservado em atualizações. Inadequado sem armazenamento compartilhado se houver mais de um nó. | Não serializa requisições por padrão. Locks podem usar cache `file` num único host, mas exigem diretório de locks persistente. |
| `database` | Recomendado para a primeira VPS se houver banco operacional; usa a tabela `sessions`, facilita backup e mantém partidas entre releases. | Também não serializa por padrão. Cache `database` permite locks atômicos pela tabela `cache_locks`, se o bloqueio for adotado. |
| `redis` | Pode ser útil se já houver Redis bem operado; exige serviço e cliente PHP, configuração de persistência e política de memória para não perder partidas. | Suporta locks atômicos via cache Redis configurado. Adiciona serviço e cuidados operacionais sem necessidade demonstrada agora. |

`array` não persiste entre requisições. `cookie` não serve ao bloqueio nativo de sessão e deslocaria o estado oficial para o navegador. Nenhum driver de sessão acima ativa o bloqueio automaticamente.

### Ciclo de vida da partida

- **Atualizar a página:** o gerenciador retoma a partida ativa da sessão e preserva tabuleiro e placar, desde que o cookie e o armazenamento continuem válidos.
- **Duas abas:** compartilham cookie e estado oficial. Uma nova partida torna a aba antiga inativa para aquela modalidade; há testes dessa proteção. Jogadas realmente simultâneas ainda podem disputar a mesma sessão sem lock.
- **Expiração por inatividade:** a próxima visita começa outra partida; o estado e placar anteriores deixam de estar disponíveis. Uma aba antiga deve recarregar para obter o novo estado.
- **Limpeza do armazenamento:** apagar `sessions`, o diretório de sessões ou a base usada elimina as partidas em andamento. Limpar somente caches de configuração/rotas/views não deve apagar a tabela de sessões, mas `optimize:clear` também limpa o cache padrão; evitar durante uso ativo, especialmente se houver locks.
- **Atualização da aplicação:** preserva partidas se `APP_KEY`, cookie, driver, base/diretório persistente e formato de sessão permanecerem compatíveis. Uma versão futura que mude a serialização do estado precisa de plano de migração ou janela de expiração; nesta etapa não houve mudança de formato.

## 3. Concorrência e alterações implementadas

**Nenhuma alteração de código ou configuração foi necessária nesta etapa.** O `SessionMatchStore::save()` rejeita certas escritas sobre rodadas encerradas na visão já carregada da sessão, mas não fornece controle de versão nem lock entre requisições.

Laravel 13 oferece `Route::...->block($lockSeconds, $waitSeconds)`, desde que o cache suporte locks atômicos e o driver de sessão não seja `cookie`. `database`, `file` e `redis` podem atender aos requisitos com seus stores apropriados. O lock vale por **ID de sessão**, portanto duas abas do mesmo navegador esperariam uma pela outra, inclusive em modalidades distintas; o tempo de espera precisa ser medido para não prejudicar a resposta.

As páginas públicas são `GET`, mas as jogadas vão ao endpoint **POST interno do Livewire**. Bloquear só as 14 rotas públicas não cobriria essas atualizações. Uma implantação futura do bloqueio deve incluir o endpoint Livewire por sua API de rota de atualização e os `GET` que criam/retomam estado; deve preservar os middlewares `web`, CSRF e o guard de cabeçalhos do Livewire. Precisa ainda escolher e confirmar `SESSION_DRIVER`, `CACHE_STORE`, tabela/local dos locks, durações do lock e comportamento de timeout na VPS. Como esses dados e um teste real de requisições concorrentes ainda faltam, não foi inserido um bloqueio parcial ou especulativo. Ele não sincronizaria a interface de abas já abertas; uma aba pode precisar recarregar após a jogada da outra.

## 4. Segurança operacional verificada

- `APP_DEBUG` tem fallback `false` em `config/app.php`, mas o exemplo local ativa debug. Conferir ambiente efetivo **após** gerar o cache de configuração.
- A rota `livewire-reference` depende de `local`/`testing`. Gerar cache de rotas somente depois de definir `APP_ENV=production` e confirmar que a rota não aparece. O endpoint de saúde `/up` é intencional.
- As ações Livewire usam o endpoint padrão com middleware `web`, proteção CSRF e exigência de cabeçalhos do Livewire. Os identificadores das partidas são propriedades travadas e o armazenamento valida formato e posse pela sessão. Não remover esses middlewares em eventual rota personalizada.
- Os motores e o armazenamento limitam índices de casas, formato dos IDs e formato do estado. Livewire verifica limite do payload de atualização. Não foi identificado formulário público de upload ou operação externa que justifique middleware genérico novo.
- Não há chamadas próprias de `Log::` ou `logger()` nas partidas. Exceções do framework ainda podem conter contexto operacional; restringir acesso aos logs, evitar nível `debug` e não registrar payloads Livewire, cookies ou IDs de sessão em proxy, observabilidade ou logs personalizados.
- Diretório público correto, HTTPS, cookie seguro e arquivos privados fora da raiz web são condições de implantação, não garantias que o repositório possa impor sem conhecer o servidor.

## 5. Checklist de implantação

1. Escolher domínio, servidor HTTP, processo PHP, banco e locais persistentes de sessão; preparar HTTPS. Documentar a topologia de proxy se houver.
2. Preparar uma cópia nova da versão e o `.env` privado. Fixar `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` com o domínio HTTPS real, `APP_KEY` estável, driver de banco, sessão, cache, cookies e logs. Na primeira instalação, com `.env` já criado e sem chave, executar `php artisan key:generate` uma vez; guardar a chave com segurança e **não** regenerá-la nas atualizações.
3. Instalar dependências da versão: `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`; validar `composer check-platform-reqs --no-dev`. Gerar assets com `npm ci` e `npm run build` no ambiente de build escolhido. Publicar `public/build`; garantir ausência de `public/hot` em produção.
4. Apontar a raiz do servidor HTTP para `public/`. Conceder escrita ao processo PHP em `storage/` e `bootstrap/cache/`; garantir que sessões e banco persistam fora de qualquer diretório de release descartável.
5. Com as variáveis definitivas carregadas, executar `php artisan migrate --force` se o driver escolhido precisar das tabelas existentes. Executar `php artisan optimize` na versão nova. Não executar `php artisan optimize:clear` ou `php artisan cache:clear` como rotina de deploy: o primeiro também limpa o cache padrão, que pode conter locks e outros dados operacionais.
6. Conferir com `php artisan config:show app.env`, `php artisan config:show app.debug`, `php artisan config:show session.driver`, `php artisan config:show cache.default` e `php artisan route:list --except-vendor` sem imprimir segredos. Confirmar que `livewire-reference` não está registrado. Ativar a nova versão e recarregar os processos PHP conforme o servidor escolhido.

## 6. Checklist após publicação

- HTTPS válido, redirecionamento HTTP→HTTPS, `APP_DEBUG=false`, cookie de sessão com `Secure`, `HttpOnly` e `SameSite=Lax`; links e assets servidos com esquema HTTPS.
- `/up`, catálogo e uma partida pública respondem; manifest de `public/build` disponível e sem referências a servidor Vite de desenvolvimento.
- Fazer uma jogada, atualizar a página, ir ao catálogo e voltar; confirmar tabuleiro e placar. Conferir duas abas e sessão nova em navegador isolado sem acesso à partida alheia.
- Confirmar que `livewire-reference` retorna 404 e que `.env`, `composer.json`, `storage/` e outros arquivos fora de `public/` não são servidos.
- Conferir logs recentes sem conteúdo de partidas, cookies ou tokens; monitorar erros 419 do Livewire, falhas de conexão com banco e crescimento do armazenamento de sessões.

## 7. Atualização e reversão

1. Preparar a nova versão em diretório separado, com dependências, assets e caches gerados sob o ambiente de produção. Manter a mesma `APP_KEY`, configuração de cookie e armazenamento de sessões. Preservar `storage/` compartilhado e banco; não apagar `sessions` ou `cache_locks`.
2. Fazer backup compatível com a política operacional antes de migrations; aplicar apenas migrations revisadas para serem compatíveis com a versão anterior enquanto a reversão for necessária. Trocar o release ativo e recarregar processos PHP.
3. Se a verificação falhar, apontar o servidor à versão anterior e recarregar processos. Reversão de código **não** desfaz migrations automaticamente; se houver mudança de esquema incompatível, seguir plano de restauração específico. Nesta etapa não há migration nova.

## 8. Verificações desta etapa e homologação

A inspeção foi dirigida a `composer.json`/lockfile, `package.json`/Vite, configuração de aplicação, sessão, cache, logs e banco, migrations de sessão/cache, `bootstrap/app.php`, rotas, `SessionMatchStore`, endpoint de atualização Livewire instalado e testes existentes de sessão e isolamento. `php artisan route:list --except-vendor` confirmou as rotas públicas no ambiente local; **não** representa o cache de rotas futuro em produção. Nenhum código, configuração ou asset foi alterado; por isso não foram executados testes novos, Pint, build Vite, suíte Browser ou suíte global. `git diff --check` foi executado para esta documentação.

**Pendente para homologação 1.0:** executar a suíte global, validar o ambiente real da VPS e os fluxos acima por HTTPS. Se a concorrência simultânea for requisito operacional, escolher o store de locks e validar bloqueio de `GET` e atualização Livewire com testes concorrentes antes da publicação.

## Referências

- [Laravel 13 — sessões e bloqueio](https://laravel.com/framework/docs/13.x/session#session-blocking)
- [Laravel 13 — implantação e otimização](https://laravel.com/framework/docs/13.x/deployment)
- [Laravel 13 — proxies confiáveis](https://laravel.com/framework/docs/13.x/requests#configuring-trusted-proxies)
- [Livewire 4 — segurança](https://livewire.laravel.com/docs/4.x/security)
