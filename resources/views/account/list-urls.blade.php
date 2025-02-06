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

<div class="container mt-4">
    <h3 class="mb-3">Minhas URLs de Webhooks</h3>

    @if (count($urls) > 0)
        <div class="table-responsive">
            <table class="table table-striped urls-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>URL</th>
                    <th>Slug</th>
                    <th>Notificações</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($urls as $index => $url)
                    <tr>
                        <td>{{ $url['id']}}</td>
                        <td>
                            <div class="small">
                                <a href="{{ route('account.webhook.view', ['account_slug' => auth()->user()->slug, 'url_hash' => $url['hash']]) }}" target="_blank">
                                    {{ route('webhook.listener', ['url_hash' => $url['hash']]) }}
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control" id="slug-{{ $url['id'] }}" value="{{ $url['slug'] ?? '' }}" placeholder="Adicionar Slug">
                                <div class="input-group-append">
                                    <button onclick="updateSlug('{{auth()->user()->slug}}', {{ $url['id'] }})" class="btn btn-info"><i class="fa fa-check"></i></button>
                                </div>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm {{ $url['notifications_enabled'] ? 'btn-success' : 'btn-secondary' }}"
                                    onclick="toggleNotification({{ $url['id'] }}, this)">
                                {{ $url['notifications_enabled'] ? 'Ativadas' : 'Desativadas' }}
                            </button>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($url['created_at'])->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <a href="{{ route('account.webhook.view', ['account_slug' => auth()->user()->slug, 'url_hash' => $url['hash']]) }}" class="btn btn-sm btn-info">
                                <i class="fa fa-eye"></i> Visualizar
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-warning">Nenhuma URL cadastrada ainda.</div>
    @endif
</div>

@routes
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    async function toggleNotification(urlId, button) {
        try {
            const response = await fetch(route('webhook.toggle-notifications', { id: urlId }), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                button.classList.toggle('btn-success', data.notifications_enabled);
                button.classList.toggle('btn-secondary', !data.notifications_enabled);
                button.innerHTML = data.notifications_enabled ? 'Ativadas' : 'Desativadas';
            } else {
                console.error('Erro ao alterar notificação:', await response.text());
                alert('Erro ao alterar notificação.');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }

    async function updateSlug(accountSlug, urlId) {
        const input = document.getElementById(`slug-${urlId}`);
        const newSlug = input.value.trim();

        try {
            const response = await fetch(route('account.url.update-slug', {account_slug: accountSlug, id: urlId }), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ slug: newSlug })
            });

            if (!response.ok) {
                throw new Error("Erro ao atualizar slug.");
            }

            const data = await response.json();
            if (data.success) {
                alert("Slug atualizado com sucesso!");
            } else {
                alert("Erro ao atualizar slug.");
            }
        } catch (error) {
            console.error("Erro ao atualizar slug:", error);
            alert("Ocorreu um erro ao tentar atualizar o slug.");
        }
    }

    async function logoutAccount() {
        try {
            const response = await fetch(route('logout'), { method: 'POST' });
            if (response.ok) {
                location.reload(); // Recarrega a página para exibir o botão de login novamente
            } else {
                alert('Erro ao fazer logout.');
            }
        } catch (error) {
            console.error('Erro ao fazer logout:', error);
        }
    }


</script>

</body>
</html>
