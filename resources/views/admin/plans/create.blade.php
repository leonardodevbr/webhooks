<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Webhooks - Criar Plano</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96"/>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg"/>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png"/>
    <meta name="apple-mobile-web-app-title" content="Webhooks"/>
    <link rel="manifest" href="/site.webmanifest"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<nav class="navbar navbar-light bg-dark px-3">
    <span class="navbar-brand logo"></span>
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
    <h3 class="mb-3">Criar Novo Plano</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('plans.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" class="form-control" id="slug" value="{{ old('slug') }}" required>
        </div>
        <div class="form-group">
            <label for="price">Preço</label>
            <input type="number" step="0.01" name="price" class="form-control" id="price" value="{{ old('price') }}" required>
        </div>
        <div class="form-group">
            <label for="billing_cycle">Ciclo de Pagamento</label>
            <select name="billing_cycle" id="billing_cycle" class="form-control" required>
                <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                <option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Anual</option>
            </select>
        </div>
        <div id="limits-container">
            <div class="form-group">
                <label>Limites do Plano</label>
                <div class="input-group mb-2">
                    <input type="text" name="limits[]" class="form-control" placeholder="Nome do limite" required>
                    <input type="text" name="limit_values[]" class="form-control" placeholder="Valor">
                    <input type="text" name="descriptions[]" class="form-control" placeholder="Descrição">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <input type="checkbox" name="availables[]" checked>
                        </div>
                        <button type="button" class="btn btn-success add-limit">+</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">Salvar Plano</button>
            <a href="{{ route('plans.index') }}" class="btn btn-secondary ml-2">Voltar</a>
        </div>

    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('.add-limit').click(function() {
            var limitGroup = `
                <div class="input-group mb-2">
                    <input type="text" name="limits[]" class="form-control" placeholder="Nome do limite" required>
                    <input type="text" name="limit_values[]" class="form-control" placeholder="Valor">
                    <input type="text" name="descriptions[]" class="form-control" placeholder="Descrição">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <input type="checkbox" name="availables[]" checked>
                        </div>
                        <button type="button" class="btn btn-danger remove-limit">-</button>
                    </div>
                </div>
            `;
            $('#limits-container').append(limitGroup);
        });

        $(document).on('click', '.remove-limit', function() {
            $(this).closest('.input-group').remove();
        });
    });

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
