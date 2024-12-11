<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="text-center">Registro</h4>
                </div>
                <div class="card-body">
                    <form id="registerForm" onsubmit="event.preventDefault(); registerAccount();" novalidate>
                        @csrf
                        <div class="form-group">
                            <label for="registerName">Nome</label>
                            <input type="text" class="form-control" id="registerName" name="name" placeholder="Digite seu nome" required>
                            <div class="invalid-feedback">Por favor, insira seu nome.</div>
                        </div>
                        <div class="form-group">
                            <label for="registerEmail">Email</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" placeholder="Digite seu email" required>
                            <div class="invalid-feedback">Por favor, insira um email válido.</div>
                        </div>
                        <div class="form-group">
                            <label for="registerPassword">Senha</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Digite sua senha" required>
                            <div class="invalid-feedback">A senha deve ter pelo menos 8 caracteres.</div>
                        </div>
                        <div class="form-group">
                            <label for="registerPasswordConfirm">Confirmar Senha</label>
                            <input type="password" class="form-control" id="registerPasswordConfirm" name="password_confirmation" placeholder="Confirme sua senha" required>
                            <div class="invalid-feedback">As senhas não coincidem.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="registerSubmit" disabled>Registrar</button>
                    </form>
                    <div class="mt-3 d-flex justify-content-between">
                        <a href="/" class="btn btn-outline-secondary btn-sm">Voltar à Página Inicial</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Já Tenho Conta</a>
                    </div>
                </div>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        validateForm('registerForm', 'registerSubmit');
    });
</script>

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
