<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Webhooks - Planos</title>
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
    <h3 class="mb-3">Planos</h3>
    <a href="{{ route('plans.create') }}" class="btn btn-primary mb-3"><i class="fa fa-plus"></i> Novo Plano</a>

    @if (count($plans) > 0)
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Ciclo</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($plans as $plan)
                    <tr>
                        <td>{{ $plan->id }}</td>
                        <td>{{ $plan->name }}</td>
                        <td>{{ number_format($plan->price, 2, ',', '.') }}</td>
                        <td>{{ ucfirst($plan->billing_cycle) }}</td>
                        <td>
                            <a href="{{ route('plans.edit', $plan->id) }}" class="btn btn-sm btn-info">
                                <i class="fa fa-pencil"></i> Editar
                            </a>
                            <a href="{{ route('plans.sync', $plan->id) }}" class="btn btn-sm btn-warning" onclick="return confirm('Deseja sincronizar este plano com a Efí?');">
                                <i class="fa fa-refresh"></i> Sincronizar
                            </a>
                            <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#limitsModal{{ $plan->id }}">
                                <i class="fa fa-eye"></i> Ver Limites
                            </button>
                            <form action="{{ route('plans.destroy', $plan->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Deseja realmente excluir este plano?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">
                                    <i class="fa fa-trash"></i> Excluir
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal para exibir os limites do plano -->
                    <div class="modal fade" id="limitsModal{{ $plan->id }}" tabindex="-1" role="dialog" aria-labelledby="limitsModalLabel{{ $plan->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="limitsModalLabel{{ $plan->id }}">{{ $plan->name }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <ul class="list-unstyled">
                                        @foreach ($plan->plan_limits as $limit)
                                            <li class="mb-2">
                                                <i class="fas {{ $limit->available ? 'fa-check text-success' : 'fa-times text-danger' }} mr-2"></i>
                                                {{ ucfirst(str_replace('_', ' ', $limit->resource)) }}
                                                @if($limit->available && $limit->limit_value)
                                                    : {{ $limit->limit_value }}
                                                @endif
                                                @if($limit->description)
                                                    <small class="text-muted d-block">{{ $limit->description }}</small>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-warning">Nenhum plano cadastrado ainda.</div>
    @endif
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
