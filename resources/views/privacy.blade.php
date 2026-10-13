<!DOCTYPE html>
<html lang="pt-BR" data-design-system="experimental">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacidade — rascunho | Plataforma Jogo da Velha</title>
    <meta name="description" content="Informações preliminares sobre sessão, preferência de tema e registros técnicos da Plataforma Jogo da Velha.">
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
            <p class="text-sm font-black uppercase tracking-widest text-ds-turquoise">Informações institucionais</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Privacidade na plataforma</h1>
            <p class="mt-4 rounded-ds-control border border-ds-warning bg-ds-warning-surface p-3 text-sm font-bold text-ds-warning">Rascunho pendente de revisão antes do lançamento 1.0. Identificação e contato do responsável, provedor e prazos de retenção ainda precisam ser confirmados.</p>
            <div class="mt-6 space-y-6 text-base leading-relaxed text-ds-text-secondary">
                <section>
                    <h2 class="text-xl font-black text-ds-text">Partidas e cookie de sessão</h2>
                    <p class="mt-2">As partidas são locais, para duas pessoas no mesmo dispositivo. A aplicação usa um cookie de sessão para retomar o tabuleiro e o placar. O estado oficial da partida fica associado à sessão Laravel, no armazenamento configurado para a implantação; ele pode deixar de estar disponível quando a sessão expira ou é removida. Se o driver de sessão em banco for adotado, o Laravel também registra endereço IP, identificação do navegador e horário da última atividade na tabela de sessões.</p>
                </section>
                <section>
                    <h2 class="text-xl font-black text-ds-text">Preferência de tema</h2>
                    <p class="mt-2">A escolha entre tema claro, escuro e automático é guardada no armazenamento local do navegador. Ela não é usada como identificação de conta. Você pode removê-la limpando os dados deste site no navegador.</p>
                </section>
                <section>
                    <h2 class="text-xl font-black text-ds-text">Registros técnicos</h2>
                    <p class="mt-2">A aplicação pode gerar registros de erro e operação. O servidor e o serviço de hospedagem poderão registrar dados técnicos, como endereço IP, horário, endereço solicitado e informações do navegador. O conteúdo e o prazo desses registros dependem da configuração final da hospedagem.</p>
                </section>
                <section>
                    <h2 class="text-xl font-black text-ds-text">Análise e publicidade</h2>
                    <p class="mt-2">O código atual não inclui ferramentas de análise de visitas, publicidade ou rastreamento de terceiros. Essa informação deverá ser reavaliada se serviços externos forem adicionados no futuro.</p>
                </section>
                <section>
                    <h2 class="text-xl font-black text-ds-text">Antes da versão definitiva</h2>
                    <p class="mt-2">É necessário confirmar quem responde pela plataforma, um canal de contato, a infraestrutura de hospedagem, os registros técnicos e seus prazos de retenção. Após essa revisão, este aviso poderá ser publicado como política definitiva.</p>
                </section>
            </div>
        </article>
    </main>
</body>
</html>
