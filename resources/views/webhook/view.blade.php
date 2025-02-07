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
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<nav class="navbar navbar-light bg-dark px-3">
    <span class="navbar-brand text-light">Monitor de Webhooks</span>

    <div>
        @if (auth()->check())
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user"></i> {{-- Ícone de usuário --}}
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                    <a class="dropdown-item" href="{{route('account.profile')}}">Perfil</a>
                    <a class="dropdown-item active" href="{{ route('account.list-urls', ['account_slug' => auth()->user()->slug]) }}">Minhas URLs</a>
                    @if(auth()->user()->is_admin)
                        <a class="dropdown-item" href="{{ route('plans.index') }}">Planos</a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="#" onclick="logoutAccount()">Sair</a>
                </div>
            </div>
        @else
            <button class="btn btn-light btn-sm" id="accountButton" data-toggle="modal" data-target="#accountModal">
                Fazer Login
            </button>
        @endif
    </div>
</nav>
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
                    @if(!empty($url['slug']))
                        <a href="javascript:;" class="text-decoration-none text-info" id="copyUrl" data-toggle="tooltip" title="Clique para copiar">
                            {{ route('webhook.custom-listener', ['url_slug' => $url['slug'], 'url_hash' => $url['hash']]) }}
                        </a>
                    @else
                        <a href="javascript:;" class="text-decoration-none text-info" id="copyUrl" data-toggle="tooltip" title="Clique para copiar">
                            {{ route('webhook.listener', ['url_hash' => $url['hash']]) }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="dropdown-divider my-2"></div>
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
                    <div class="dropdown">
                        <button class="btn btn-link text-secondary" type="button" id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-gears"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                            <a class="dropdown-item" id="toggleNotifications" href="#" onclick="toggleNotifications(event)">
                                Desativar notificações
                            </a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#retransmitUrlsModal">
                                Gerenciar URLs de Retransmissão
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dropdown-divider my-3"></div>
            <div id="webhookDetails" class="webhook-details">Selecione um webhook para visualizar os
                detalhes.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalLabel">Gerenciar Conta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <!-- Tabs para alternar entre Login e Registro -->
                <ul class="nav nav-tabs" id="accountTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="login-tab" data-toggle="tab" href="#login" role="tab" aria-controls="login" aria-selected="true">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab" aria-controls="register" aria-selected="false">Registrar</a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <!-- Login -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
                        <form id="loginForm">
                            <div class="form-group">
                                <label for="loginEmail">E-mail</label>
                                <input autocomplete="new-email" type="email" class="form-control" id="loginEmail" placeholder="Digite seu e-mail" required>
                            </div>
                            <div class="form-group">
                                <label for="loginPassword">Senha</label>
                                <input autocomplete="new-password" type="password" class="form-control" id="loginPassword" placeholder="Digite sua senha" required>
                            </div>
                            <button type="button" class="btn btn-primary" id="loginSubmit" disabled onclick="loginAccount()">Entrar</button>
                        </form>
                    </div>
                    <!-- Registro -->
                    <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                        <form id="registerForm" onsubmit="event.preventDefault(); registerAccount();">
                            <div class="form-group">
                                <label for="registerName">Nome</label>
                                <input type="text" class="form-control" id="registerName" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="registerEmail">Email</label>
                                <input autocomplete="new-email" type="email" class="form-control" id="registerEmail" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="registerPassword">Senha</label>
                                <input autocomplete="new-password" type="password" class="form-control" id="registerPassword" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="registerPasswordConfirm">Confirmar Senha</label>
                                <input autocomplete="new-password" type="password" class="form-control" id="registerPasswordConfirm" name="password_confirmation" required>
                            </div>
                            <button type="submit" class="btn btn-primary" id="registerSubmit" disabled>Registrar</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>



<!-- Modal -->
<div class="modal fade" id="retransmitUrlsModal" tabindex="-1" role="dialog" aria-labelledby="retransmitUrlsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <!-- Cabeçalho do Modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="retransmitUrlsModalLabel">URLs de Retransmissão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Corpo do Modal -->
            <div class="modal-body">
                <div class="table-responsive url-list">
                    <table class="table table-striped table-borderless">
                        <thead>
                        <tr>
                            <th>URL</th>
                            <th>Online</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody id="urlList">
                        <!-- As linhas serão adicionadas dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="dropdown-divider my-3"></div>
                <div id="retransmitUrlsContainer" class="mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Input de URL -->
                        <input type="text" class="form-control" id="retransmitUrlInput" placeholder="http://localhost"
                               aria-label="URL para retransmissão">

                        <!-- Select Local/Remoto -->
                        <select id="retransmitTypeSelect" class="form-control w-auto mx-3">
                            <option value="0">Local</option>
                            <option value="1">Remoto</option>
                        </select>

                        <div class="">
                            <button onclick="addRetransmissionUrl()" class="btn btn-primary">Salvar</button>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Rodapé do Modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="featureModal" tabindex="-1" aria-labelledby="featureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="featureModalLabel">🚀 Bem-vindo ao Monitor de Webhooks!</h5>
            </div>
            <div class="modal-body">
                <p>Este sistema foi desenvolvido para facilitar a captura, gerenciamento e retransmissão de webhooks em tempo real.
                    Abaixo, você encontrará uma visão geral das principais funcionalidades:</p>

                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <strong>📡 Captura e Exibição de Webhooks</strong><br>
                        <ul>
                            <li>Receba e visualize webhooks recebidos em tempo real.</li>
                            <li>Exiba detalhes completos de cada requisição, incluindo <strong>Método HTTP</strong>, <strong>Headers</strong>, <strong>Query Params</strong> e <strong>Corpo da Requisição</strong>.</li>
                            <li>As requisições recentes aparecem no topo para facilitar a análise.</li>
                        </ul>
                    </li>

                    <li class="list-group-item">
                        <strong>🔔 Notificações em Tempo Real</strong><br>
                        <ul>
                            <li>Ative notificações para ser alertado sempre que um novo webhook chegar.</li>
                            <li>Cada URL pode ter notificações ativadas ou desativadas individualmente.</li>
                            <li>Clique em <span class="text-primary">"Ok! Habilite as notificações"</span> abaixo para permitir notificações no navegador.</li>
                        </ul>
                    </li>

                    <li class="list-group-item">
                        <strong>🔄 Retransmissão Automática de Webhooks</strong><br>
                        <ul>
                            <li>Configure <strong>URLs de retransmissão</strong> para encaminhar automaticamente os webhooks recebidos.</li>
                            <li>Caso a URL de destino esteja offline, o sistema permite reenviar manualmente.</li>
                        </ul>
                    </li>

                    <li class="list-group-item">
                        <strong>📁 Gerenciamento de Múltiplas URLs</strong><br>
                        <ul>
                            <li>Cada usuário pode criar e gerenciar <strong>múltiplas URLs</strong> para monitoramento de webhooks.</li>
                            <li>Personalize cada URL com um <strong>slug</strong> para facilitar a identificação.</li>
                            <li>Acesse todas as suas URLs na página <strong>Minhas URLs de Webhooks</strong>.</li>
                        </ul>
                    </li>

                    <li class="list-group-item">
                        <strong>⚙️ Configuração Personalizada</strong><br>
                        <ul>
                            <li>Utilize o menu de configurações para gerenciar retransmissões, notificações e detalhes da URL.</li>
                            <li>Modifique o <strong>slug</strong> da URL para facilitar seu uso.</li>
                        </ul>
                    </li>
                </ul>

                <p class="text-muted">
                    <small>Habilite as notificações para acompanhar os eventos em tempo real e aproveite ao máximo o sistema!</small>
                </p>
            </div>
            <div class="modal-footer">
                <button id="understoodButton" class="btn btn-secondary">✔ Entendido</button>
                <button id="enableNotifications" class="btn btn-primary">🔔 Ok! Habilite as notificações</button>
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
        VAPID_PUBLIC_KEY: "{{ env('VAPID_PUBLIC_KEY') }}"
    };
</script>
<script>
    window.urlHash = '{{ $url->hash ?? null}}';
    window.urlId = '{{ $url->id ?? null}}';
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
        document.addEventListener("DOMContentLoaded", function () {
            const notifications = document.getElementById("notifications");

            if (!notifications) return;

            let timeout = setTimeout(() => {
                fadeOut(notifications);
            }, 5000);

            // Interrompe o desaparecimento se o mouse estiver sobre a notificação
            notifications.addEventListener("mouseenter", () => clearTimeout(timeout));

            // Retoma o desaparecimento quando o mouse sai
            notifications.addEventListener("mouseleave", () => {
                timeout = setTimeout(() => fadeOut(notifications), 3000);
            });
        });

        document.querySelectorAll('.alert-dismissible .close').forEach(button => {
            button.addEventListener('click', function () {
                const alert = this.closest('.alert');
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); // Remove o elemento após a transição
            });
        });

        function fadeOut(element) {
            element.style.transition = "opacity 0.5s";
            element.style.opacity = "0";
            setTimeout(() => element.remove(), 500);
        }
    </script>
@endif
</body>
</html>
