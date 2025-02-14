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
        <div class="mb-4">
            <h5>Limites do Plano</h5>
            <table class="table">
                <thead>
                <tr>
                    <th>Recurso</th>
                    <th>Valor</th>
                    <th>Descrição</th>
                    <th>Disponível</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody id="limits-table">
                @foreach ($plan->plan_limits ?? [] as $limit)
                    <tr class="{{ $limit->available ? '' : 'text-muted' }}">
                        <td>{{ ucfirst(str_replace('_', ' ', $limit->resource)) }}</td>
                        <td>{{ $limit->limit_value }}</td>
                        <td>{{ $limit->description }}</td>
                        <td>{{ $limit->available ? 'Sim' : 'Não' }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-limit"
                                    data-limit="{{ htmlspecialchars($limit->toJson()) }}">Editar</button>
                            <button type="button" class="btn btn-sm btn-danger remove-limit" data-id="{{ $limit->id }}">Remover</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-success" id="add-limit">Adicionar Limite</button>
        </div>

        <!-- Modal para edição/criação de limite -->
        <div class="modal fade" id="limitModal" tabindex="-1" role="dialog" aria-labelledby="limitModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="limitModalLabel">Editar Limite</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="limit-id">
                        <div class="form-group">
                            <label for="resource">Recurso</label>
                            <input type="text" class="form-control" id="resource" required>
                        </div>
                        <div class="form-group">
                            <label for="limit-value">Valor</label>
                            <input type="text" class="form-control" id="limit-value">
                        </div>
                        <div class="form-group">
                            <label for="description">Descrição</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="available">
                            <label class="form-check-label" for="available">Disponível</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" id="save-limit">Salvar</button>
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
        $(document).ready(function() {
            // Adicionar limite
            $('#add-limit').click(function() {
                $('#limitModal').modal('show');
                $('#limit-id').val('');
                $('#resource').val('');
                $('#limit-value').val('');
                $('#description').val('');
                $('#available').prop('checked', true);
            });

            // Editar limite
            $(document).on('click', '.edit-limit', function() {
                try {
                    var limitData = $(this).data('limit');

                    // Se já for objeto, use diretamente
                    if (typeof limitData === 'object') {
                        var limit = limitData;
                    } else {
                        // Decodifica os caracteres HTML e então faz o parse
                        var decodedData = $('<div/>').html(limitData).text();
                        var limit = JSON.parse(decodedData);
                    }

                    $('#limitModal').modal('show');
                    $('#limit-id').val(limit.id);
                    $('#resource').val(limit.resource);
                    $('#limit-value').val(limit.limit_value);
                    $('#description').val(limit.description);
                    $('#available').prop('checked', limit.available);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.log('Dados recebidos:', $(this).data('limit'));
                }
            });

            // Salvar limite
            $('#save-limit').click(function() {
                var limitId = $('#limit-id').val();
                var resource = $('#resource').val();
                var limitValue = $('#limit-value').val();
                var description = $('#description').val();
                var available = $('#available').prop('checked');

                var limitRow = `
                <tr class="${available ? '' : 'text-muted'}">
                    <td>${resource}</td>
                    <td>${limitValue}</td>
                    <td>${description}</td>
                    <td>${available ? 'Sim' : 'Não'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary edit-limit" data-limit='{"id":"${limitId}","resource":"${resource}","limit_value":"${limitValue}","description":"${description}","available":${available}}'>Editar</button>
                        <button type="button" class="btn btn-sm btn-danger remove-limit" data-id="${limitId}">Remover</button>
                    </td>
                </tr>
            `;

                if (limitId) {
                    // Atualizar limite existente
                    $(`tr[data-id="${limitId}"]`).replaceWith(limitRow);
                } else {
                    // Adicionar novo limite
                    $('#limits-table').append(limitRow);
                }

                $('#limitModal').modal('hide');
            });

            // Remover limite
            $(document).on('click', '.remove-limit', function() {
                $(this).closest('tr').remove();
            });
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
