<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Webhooks</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96"/>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg"/>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png"/>
    <meta name="apple-mobile-web-app-title" content="Webhooks"/>
    <link rel="manifest" href="/site.webmanifest"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div id="sidebar" class="col-12 col-lg-3 col-sm-4">
            <div class="d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between">
                    <h5>URL para compartilhamento</h5>
                    <div>
                        <button onclick="createNewUrl()" class="btn btn-dark btn-sm">Gerar Nova</button>
                    </div>
                </div>
                <div class="dropdown-divider my-2"></div>
                <div class="text-info small">
                    <a href="javascript:;" class="text-decoration-none text-info" id="copyUrl"
                       onclick="copyToClipboard()" data-toggle="tooltip" title="Copiar">
                        <b>{{ route('webhook.listener', [$url->hash]) }}</b>
                    </a>
                </div>
            </div>
            <div class="dropdown-divider my-2"></div>
            <h6>URLs de Retransmissão</h6>
            <div id="urlList"></div>
            <div class="dropdown-divider my-3"></div>
            <div id="retransmitUrlsContainer">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="retransmitUrlInput" placeholder="http://localhost"
                           aria-label="URL para retransmissão" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button onclick="addRetransmissionUrl()" class="btn btn-outline-secondary" type="button">
                            Adicionar URL
                        </button>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="isOnlineCheckbox">
                    <label class="form-check-label" for="isOnlineCheckbox">Online</label>
                </div>
            </div>

            <div class="dropdown-divider my-3"></div>
            <div class="d-flex justify-content-between">
                <h5>Webhooks Recebidos</h5>
                <div id="resetButtonContainer">
                    <button onclick="clearAllWebhooks()" class="btn btn-danger btn-sm">Resetar</button>
                </div>
            </div>
            <div class="dropdown-divider"></div>
            <div id="webhookList"></div>
        </div>
        <div id="content" class="col-12 col-lg-9  col-sm-8">
            <div class="d-flex justify-content-between">
                <h5>Detalhes da requisição:</h5>
                <div>
                    <button class="btn m-0 btn-info btn-sm" id="toggleNotifications"
                            onclick="toggleNotifications()">
                        <i class="fa fa-bell-o"></i>
                    </button>
                </div>
            </div>
            <div class="dropdown-divider my-3"></div>
            <div id="webhookDetails" class="webhook-details">Selecione um webhook para visalizar os
                detalhes.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="featureModal" tabindex="-1" aria-labelledby="featureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="featureModalLabel">Bem-vindo ao Sistema de Monitoramento de Webhooks</h5>
            </div>
            <div class="modal-body">
                <p>Este sistema foi projetado para otimizar o gerenciamento e a visualização de webhooks em tempo real.
                    Abaixo, você encontrará uma visão geral das funcionalidades principais:</p>
                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <strong>Notificações em Tempo Real</strong><br>
                        Receba alertas instantâneos de webhooks recebidos. <span class="text-primary">Clique em "Ok! Habilite as notificações"</span>
                        abaixo para permitir notificações no navegador e receber alertas sempre que um novo webhook
                        chegar.
                    </li>
                    <li class="list-group-item">
                        <strong>Retransmissão Automática de Webhooks</strong><br>
                        Configure URLs específicas para retransmitir automaticamente os webhooks recebidos. Com esta
                        função, você garante que todos os webhooks sejam encaminhados rapidamente para os sistemas
                        designados.
                    </li>
                    <li class="list-group-item">
                        <strong>Visualização Detalhada dos Webhooks</strong><br>
                        Examine cada webhook individualmente, com visualização completa de detalhes como cabeçalhos,
                        parâmetros de consulta e payloads.
                    </li>
                    <li class="list-group-item">
                        <strong>Forçar Retransmissão</strong><br>
                        A funcionalidade de retransmissão manual permite que você encaminhe webhooks específicos para URLs configuradas a qualquer momento. Com isso, você tem controle adicional sobre o envio, garantindo que todos os webhooks sejam processados conforme necessário.
                    </li>
                </ul>
                <p class="text-muted"><small>Habilite as notificações para receber atualizações em tempo real sempre que
                        um webhook chegar.</small></p>
            </div>
            <div class="modal-footer">
                <button id="understoodButton" class="btn btn-secondary">Entendido</button>
                <button id="enableNotifications" class="btn btn-primary">Ok! Habilite as notificações</button>
            </div>
        </div>
    </div>
</div>

@routes
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    window.env = {
        PUSHER_KEY: "{{ env('PUSHER_KEY') }}",
        PUSHER_CLUSTER: "{{ env('PUSHER_CLUSTER') }}",
        PUSHER_CHANNEL: "{{ env('PUSHER_CHANNEL') }}",
    };
</script>
<script>
    window.urlHash = '{{ $url->hash }}';
    window.urlId = '{{ $url->id }}';
</script>
<script src="{{ asset('js/scripts.js') }}"></script>

@if(session('error') || session('success') || session('info'))
    <div id="notifications" class="position-fixed top-0 right-0 p-3" style="z-index: 1055;">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('info') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>

    <script>
        // Temporizador para esconder as notificações automaticamente
        setTimeout(() => {
            const notifications = document.getElementById('notifications');
            if (notifications) {
                notifications.style.transition = 'opacity 0.5s';
                notifications.style.opacity = '0';
                setTimeout(() => notifications.remove(), 500); // Remove o elemento após a animação de transição
            }
        }, 5000); // Exibe a notificação por 5 segundos

        // Evento para fechar a notificação manualmente ao clicar no botão "fechar"
        document.querySelectorAll('.alert-dismissible .close').forEach(button => {
            button.addEventListener('click', function () {
                const alert = this.closest('.alert');
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); // Remove o elemento após a transição
            });
        });
    </script>
@endif
</body>
</html>
