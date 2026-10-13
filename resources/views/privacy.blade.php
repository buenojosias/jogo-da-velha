<!DOCTYPE html>
<html lang="pt-BR" data-design-system="experimental">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacidade — rascunho | Plataforma Jogo da Velha</title>
    <meta name="description" content="Aviso de privacidade da Plataforma Jogo da Velha: sessões, preferência de tema, registros técnicos e contato do responsável.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="{{ rtrim((string) config('app.url'), '/').route('privacy', [], false) }}">
    <link rel="icon" type="image/svg+xml" href="{{ rtrim((string) config('app.url'), '/') }}/icon.svg">
    <script>
        (() => {
            let preference = 'auto';
            try {
                const stored = localStorage.getItem('jogo-da-velha.theme');
                preference = stored === 'light' || stored === 'dark' ? stored : 'auto';
            } catch {}
            document.documentElement.dataset.themePreference = preference;
            document.documentElement.dataset.theme = preference === 'auto'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preference;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-ds-text">
    <main class="mx-auto min-h-screen w-full max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
        <a href="{{ route('welcome') }}" class="inline-flex min-h-11 items-center rounded-ds-control px-3 text-sm font-bold text-ds-primary hover:bg-ds-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">← Voltar ao catálogo</a>
        <article class="mt-5 rounded-ds-card border border-ds-border bg-ds-surface p-5 shadow-ds-card sm:p-8">
            <p class="text-sm font-black uppercase tracking-widest text-ds-turquoise">Plataforma Jogo da Velha</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Aviso de Privacidade</h1>
            <p class="mt-4 rounded-ds-control border border-ds-border bg-ds-surface-muted p-3 text-sm text-ds-text-secondary">Rascunho pendente de revisão. Os detalhes da hospedagem e os prazos de retenção serão confirmados antes da publicação.</p>
            <p class="mt-5 leading-relaxed text-ds-text-secondary">A sua privacidade é importante para nós. Este aviso explica, de forma simples, quais informações podem ser utilizadas durante o acesso à Plataforma Jogo da Velha, por que elas são necessárias e como você pode entrar em contato conosco.</p>

            <div class="mt-8 space-y-8 text-base leading-relaxed text-ds-text-secondary">
                <section aria-labelledby="privacy-platform" class="space-y-3">
                    <h2 id="privacy-platform" class="text-xl font-black text-ds-text">1. Sobre a plataforma e o responsável</h2>
                    <p>A Plataforma Jogo da Velha é um projeto independente, gratuito e mantido por <strong>Josias Pereira Bueno</strong>, pessoa física responsável pelo tratamento dos dados pessoais relacionados ao seu funcionamento.</p>
                    <p>A plataforma oferece diferentes modalidades de Jogo da Velha para duas pessoas jogarem no mesmo dispositivo, diretamente pelo navegador.</p>
                    <p>Não é necessário criar uma conta, informar seu nome ou realizar cadastro para jogar.</p>
                    <p><strong>Contato para assuntos de privacidade:</strong> <a href="mailto:velha@jwd.com.br" class="font-semibold text-ds-primary underline underline-offset-2 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">velha@jwd.com.br</a></p>
                </section>

                <section aria-labelledby="privacy-data" class="space-y-3">
                    <h2 id="privacy-data" class="text-xl font-black text-ds-text">2. Informações utilizadas</h2>
                    <p>A plataforma foi desenvolvida para funcionar com o mínimo necessário de informações.</p>
                    <p>Durante a utilização, poderão ser tratados:</p>
                    <ul class="list-disc space-y-2 ps-6">
                        <li><strong>Cookie de sessão Laravel:</strong> identificador técnico que associa a navegação à sessão.</li>
                        <li><strong>Estado temporário da partida:</strong> tabuleiro, jogadas, turnos e placar mantidos na sessão Laravel durante a partida.</li>
                        <li><strong>Preferência de aparência:</strong> a escolha entre tema claro, escuro ou automático é armazenada localmente no navegador para que a plataforma lembre sua preferência.</li>
                        <li><strong>Informações técnicas de acesso:</strong> o servidor e os serviços de hospedagem poderão registrar dados como endereço IP, data e horário de acesso, navegador utilizado e informações sobre eventuais falhas, para fins de funcionamento, segurança e diagnóstico.</li>
                    </ul>
                    <p>A plataforma não solicita nome, CPF, telefone, endereço ou e-mail para iniciar uma partida.</p>
                </section>

                <section aria-labelledby="privacy-purpose" class="space-y-3">
                    <h2 id="privacy-purpose" class="text-xl font-black text-ds-text">3. Finalidades do tratamento</h2>
                    <p>As informações técnicas são utilizadas para:</p>
                    <ul class="list-disc space-y-2 ps-6">
                        <li>Permitir o funcionamento das partidas e a continuidade do jogo durante a sessão.</li>
                        <li>Manter temporariamente as jogadas, os turnos e os placares.</li>
                        <li>Recordar a preferência de aparência escolhida no navegador.</li>
                        <li>Proteger o funcionamento da plataforma, identificar falhas e realizar manutenção técnica.</li>
                        <li>Atender solicitações relacionadas à privacidade e à proteção de dados.</li>
                    </ul>
                    <p>O tratamento deve observar as finalidades informadas e as bases legais aplicáveis da Lei Geral de Proteção de Dados Pessoais (LGPD).</p>
                </section>

                <section aria-labelledby="privacy-cookies" class="space-y-3">
                    <h2 id="privacy-cookies" class="text-xl font-black text-ds-text">4. Cookies e armazenamento local</h2>
                    <p>A plataforma utiliza mecanismos necessários ao seu funcionamento.</p>
                    <p>O <strong>cookie de sessão do Laravel</strong> permite reconhecer a sessão do navegador. O tabuleiro, os turnos e o placar são mantidos temporariamente pela sessão Laravel, no armazenamento que será definido para a implantação. Sem a sessão, funcionalidades como a continuidade da partida após atualizar a página podem não funcionar corretamente.</p>
                    <p>A preferência de tema é armazenada no próprio navegador, por meio de armazenamento local (<em>localStorage</em>). Essa preferência não exige cadastro e pode ser apagada pelas configurações do navegador.</p>
                    <p>Na versão atual do projeto, não foram identificados cookies próprios de publicidade ou ferramentas de análise comportamental. Essa informação será reavaliada caso novos serviços sejam adicionados.</p>
                </section>

                <section aria-labelledby="privacy-retention" class="space-y-3">
                    <h2 id="privacy-retention" class="text-xl font-black text-ds-text">5. Retenção das informações</h2>
                    <p>As informações das partidas permanecem associadas à sessão do navegador enquanto ela estiver válida e disponível no armazenamento utilizado pela plataforma.</p>
                    <p><strong>Pendente de confirmação operacional:</strong> o prazo de duração das sessões será definido na configuração de produção.</p>
                    <p>Os registros técnicos de acesso e de erros poderão ser mantidos conforme as necessidades de segurança, manutenção e as configurações reais da hospedagem. As categorias e os prazos desses registros também estão <strong>pendentes de confirmação operacional</strong> e serão informados neste aviso após sua definição.</p>
                    <p>A preferência de tema poderá permanecer no navegador até ser alterada ou apagada pelo próprio usuário.</p>
                </section>

                <section aria-labelledby="privacy-sharing" class="space-y-3">
                    <h2 id="privacy-sharing" class="text-xl font-black text-ds-text">6. Compartilhamento e serviços de infraestrutura</h2>
                    <p>As informações técnicas necessárias ao funcionamento da plataforma poderão ser processadas pelos serviços utilizados para hospedagem e infraestrutura, dentro das finalidades descritas neste aviso.</p>
                    <p>A implantação está planejada em uma VPS gerenciada pelo EasyPanel. <strong>Pendente de confirmação operacional:</strong> provedor, localização do servidor e dos backups, serviços auxiliares e condições relevantes de tratamento serão informados conforme a configuração definitiva.</p>
                    <p>A Plataforma Jogo da Velha não tem como finalidade comercializar dados pessoais de seus visitantes.</p>
                </section>

                <section aria-labelledby="privacy-children" class="space-y-3">
                    <h2 id="privacy-children" class="text-xl font-black text-ds-text">7. Privacidade de crianças e adolescentes</h2>
                    <p>A plataforma foi concebida para oferecer uma experiência de jogo acessível a diferentes faixas etárias, incluindo crianças e adolescentes.</p>
                    <p>Não exigimos cadastro, idade, nome ou outras informações pessoais para iniciar uma partida.</p>
                    <p>O tratamento de dados pessoais de crianças e adolescentes deverá respeitar a legislação aplicável e observar seu melhor interesse.</p>
                    <p>Pais ou responsáveis poderão entrar em contato pelo e-mail indicado neste aviso caso tenham dúvidas sobre privacidade.</p>
                </section>

                <section aria-labelledby="privacy-security" class="space-y-3">
                    <h2 id="privacy-security" class="text-xl font-black text-ds-text">8. Segurança</h2>
                    <p>Adotamos medidas técnicas e organizacionais proporcionais ao funcionamento da plataforma para proteger as informações tratadas contra acessos indevidos, alterações não autorizadas e outros incidentes.</p>
                    <p>Nenhum sistema conectado à internet é completamente livre de riscos. Caso seja identificada uma ocorrência relevante envolvendo dados pessoais, serão avaliadas as providências cabíveis conforme a legislação.</p>
                </section>

                <section aria-labelledby="privacy-rights" class="space-y-3">
                    <h2 id="privacy-rights" class="text-xl font-black text-ds-text">9. Direitos dos titulares</h2>
                    <p>Nos termos da LGPD e conforme as condições aplicáveis a cada solicitação, você poderá pedir informações sobre o tratamento dos seus dados pessoais, solicitar acesso, correção ou eliminação quando cabível e exercer outros direitos previstos em lei.</p>
                    <p>Para isso, use o contato indicado abaixo.</p>
                    <p>Poderemos solicitar informações estritamente necessárias para compreender e atender o pedido com segurança.</p>
                </section>

                <section aria-labelledby="privacy-updates" class="space-y-3">
                    <h2 id="privacy-updates" class="text-xl font-black text-ds-text">10. Atualizações e contato</h2>
                    <p>Este aviso poderá ser atualizado para refletir mudanças na plataforma, nos serviços utilizados ou nas obrigações legais.</p>
                    <p>A versão atualizada será disponibilizada nesta página, com a indicação da data de sua última atualização.</p>
                    <div class="rounded-ds-control border border-ds-border bg-ds-surface-muted p-4">
                        <p class="font-bold text-ds-text">Contato para assuntos de privacidade</p>
                        <a href="mailto:velha@jwd.com.br" class="mt-1 inline-flex min-h-11 items-center font-semibold text-ds-primary underline underline-offset-2 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">velha@jwd.com.br</a>
                    </div>
                </section>
            </div>

            <footer class="mt-8 space-y-1 border-t border-ds-border pt-5 text-sm leading-relaxed text-ds-text-secondary">
                <p><strong>Responsável:</strong> Josias Pereira Bueno</p>
                <p><strong>Projeto:</strong> Plataforma Jogo da Velha</p>
                <p><strong>Contato:</strong> <a href="mailto:velha@jwd.com.br" class="font-semibold text-ds-primary underline underline-offset-2 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ds-focus">velha@jwd.com.br</a></p>
                <p><strong>Última atualização:</strong> a informar na publicação da versão definitiva.</p>
            </footer>
        </article>
    </main>
</body>
</html>
