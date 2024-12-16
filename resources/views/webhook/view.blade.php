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
                    @if (auth()->check())
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-user"></i> {{-- Ícone de usuário --}}
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                                <a class="dropdown-item" href="#">Perfil</a>
                                <a class="dropdown-item" href="#" onclick="toggleNotifications(event)">
                                    Notificações <i class="fa fa-bell-o"></i>
                                </a>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#retransmitUrlsModal">
                                    Gerenciar URLs de Retransmissão <i class="fa fa-link"></i>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" onclick="logoutAccount()">Sair</a>
                            </div>
                        </div>
                    @else
                        <button class="btn m-0 btn-info btn-sm" id="toggleNotifications"
                                onclick="toggleNotifications()">
                            <i class="fa fa-bell-o"></i>
                        </button>
                        <button class="btn m-0 btn-primary btn-sm" data-toggle="modal" data-target="#retransmitUrlsModal">
                            Gerenciar URLs de Retransmissão
                        </button>
                        <button class="btn btn-outline-info btn-sm" id="accountButton" data-toggle="modal" data-target="#accountModal">
                            Fazer Login
                        </button>
                    @endif
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
                                <input type="email" class="form-control" id="loginEmail" placeholder="Digite seu e-mail" required>
                            </div>
                            <div class="form-group">
                                <label for="loginPassword">Senha</label>
                                <input type="password" class="form-control" id="loginPassword" placeholder="Digite sua senha" required>
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
                                <input type="email" class="form-control" id="registerEmail" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="registerPassword">Senha</label>
                                <input type="password" class="form-control" id="registerPassword" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="registerPasswordConfirm">Confirmar Senha</label>
                                <input type="password" class="form-control" id="registerPasswordConfirm" name="password_confirmation" required>
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
