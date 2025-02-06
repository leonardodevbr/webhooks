<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Webhooks - Editar Plano</title>
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
                    <i class="fa fa-user"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                    <a class="dropdown-item" href="{{route('account.profile')}}">Perfil</a>
                    <a class="dropdown-item" href="{{ route('account.list-urls', ['account_slug' => auth()->user()->slug]) }}">Minhas URLs</a>
                    @if(auth()->user()->is_admin)
                        <a class="dropdown-item active" href="{{ route('plans.index') }}">Planos</a>
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
    <h3 class="mb-3">Editar Plano</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('plans.update', $plan->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $plan->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" class="form-control" id="slug" value="{{ old('slug', $plan->slug) }}" required>
        </div>
        <div class="form-group">
            <label for="price">Preço</label>
            <input type="number" step="0.01" name="price" class="form-control" id="price" value="{{ old('price', $plan->price) }}" required>
        </div>
        <div class="form-group">
            <label for="billing_cycle">Ciclo de Pagamento</label>
            <select name="billing_cycle" id="billing_cycle" class="form-control" required>
                <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Mensal</option>
                <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) == 'yearly' ? 'selected' : '' }}>Anual</option>
            </select>
        </div>
        <div class="form-group">
            <label for="max_urls">Máx URLs</label>
            <input type="number" name="max_urls" class="form-control" id="max_urls" value="{{ old('max_urls', $plan->max_urls) }}" required>
        </div>
        <div class="form-group">
            <label for="max_webhooks_per_url">Máx Webhooks por URL</label>
            <input type="number" name="max_webhooks_per_url" class="form-control" id="max_webhooks_per_url" value="{{ old('max_webhooks_per_url', $plan->max_webhooks_per_url) }}" required>
        </div>
        <div class="form-group">
            <label for="max_retransmission_urls">Máx URLs de Retransmissão</label>
            <input type="number" name="max_retransmission_urls" class="form-control" id="max_retransmission_urls" value="{{ old('max_retransmission_urls', $plan->max_retransmission_urls) }}" required>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" name="supports_custom_slugs" class="form-check-input" id="supports_custom_slugs" {{ old('supports_custom_slugs', $plan->supports_custom_slugs) ? 'checked' : '' }}>
            <label class="form-check-label" for="supports_custom_slugs">Suporta Custom Slugs</label>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" name="real_time_notifications" class="form-check-input" id="real_time_notifications" {{ old('real_time_notifications', $plan->real_time_notifications) ? 'checked' : '' }}>
            <label class="form-check-label" for="real_time_notifications">Notificações em Tempo Real</label>
        </div>
        <button type="submit" class="btn btn-success">Atualizar Plano</button>
        <a href="{{ route('plans.sync', $plan->id) }}" class="btn btn-warning ml-2" onclick="return confirm('Deseja sincronizar este plano com a Efí?');">
            <i class="fa fa-refresh"></i> Sincronizar com a Efí
        </a>
        <a href="{{ route('plans.index') }}" class="btn btn-secondary ml-2">Voltar</a>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
    async function logoutAccount() {
        try {
            const response = await fetch("{{ route('logout') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            if(response.ok){
                location.reload();
            } else {
                alert('Erro ao fazer logout.');
            }
        } catch(error) {
            console.error('Erro ao fazer logout:', error);
        }
    }
</script>
</body>
</html>
