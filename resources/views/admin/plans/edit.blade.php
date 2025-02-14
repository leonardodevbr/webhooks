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

<div class="container mt-4 pb-4">
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

    @include('admin.plans._form', [
        'action' => route('plans.update', $plan->id),
        'method' => 'PUT',
        'plan' => $plan
    ])
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/plan-form.js') }}"></script>

{{--<script>--}}
{{--    function collectLimits() {--}}
{{--        const limits = [];--}}
{{--        $('#limits-table tr').each(function() {--}}
{{--            const limitData = $(this).find('.edit-limit').data('limit');--}}
{{--            if (limitData) {--}}
{{--                limits.push({--}}
{{--                    id: limitData.id || null,--}}
{{--                    resource: limitData.resource,--}}
{{--                    limit_value: limitData.limit_value,--}}
{{--                    description: limitData.description,--}}
{{--                    available: limitData.available--}}
{{--                });--}}
{{--            }--}}
{{--        });--}}
{{--        return limits;--}}
{{--    }--}}

{{--    $('form').on('submit', function(e) {--}}
{{--        e.preventDefault();--}}

{{--        const form = $(this);--}}
{{--        const url = form.attr('action');--}}
{{--        const limits = collectLimits();--}}

{{--        // Criar FormData com todos os campos--}}
{{--        const formData = new FormData(form[0]);--}}
{{--        formData.append('limits', JSON.stringify(limits));--}}

{{--        // Desabilitar o botão de submit para evitar duplo envio--}}
{{--        const submitButton = form.find('button[type="submit"]');--}}
{{--        const originalText = submitButton.text();--}}
{{--        submitButton.prop('disabled', true).text('Salvando...');--}}

{{--        // Limpar mensagens de erro anteriores--}}
{{--        $('.alert-danger').remove();--}}
{{--        $('.is-invalid').removeClass('is-invalid');--}}

{{--        $.ajax({--}}
{{--            url: url,--}}
{{--            method: 'POST',--}}
{{--            data: formData,--}}
{{--            processData: false,--}}
{{--            contentType: false,--}}
{{--            headers: {--}}
{{--                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
{{--            },--}}
{{--            success: function(response) {--}}
{{--                if (response.success) {--}}
{{--                    // Redirecionar com mensagem de sucesso--}}
{{--                    window.location.href = response.redirect;--}}
{{--                }--}}
{{--            },--}}
{{--            error: function(xhr) {--}}
{{--                submitButton.prop('disabled', false).text(originalText);--}}

{{--                if (xhr.status === 422) {--}}
{{--                    const errors = xhr.responseJSON.errors;--}}

{{--                    // Criar mensagem de erro geral--}}
{{--                    const errorHtml = '<div class="alert alert-danger"><ul>' +--}}
{{--                        Object.values(errors).map(error => `<li>${error[0]}</li>`).join('') +--}}
{{--                        '</ul></div>';--}}
{{--                    form.prepend(errorHtml);--}}

{{--                    // Marcar campos com erro--}}
{{--                    Object.keys(errors).forEach(field => {--}}
{{--                        const input = $(`[name="${field}"]`);--}}
{{--                        input.addClass('is-invalid');--}}
{{--                        input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);--}}
{{--                    });--}}

{{--                    // Scroll para o topo se houver erros--}}
{{--                    $('html, body').animate({--}}
{{--                        scrollTop: $('.alert-danger').offset().top - 20--}}
{{--                    }, 500);--}}
{{--                } else {--}}
{{--                    // Erro inesperado--}}
{{--                    form.prepend('<div class="alert alert-danger">Ocorreu um erro ao salvar o plano. Por favor, tente novamente.</div>');--}}
{{--                }--}}
{{--            }--}}
{{--        });--}}
{{--    });--}}

{{--    $('#save-limit').click(function() {--}}
{{--        const limitId = $('#limit-id').val();--}}
{{--        const limitData = {--}}
{{--            id: limitId || null,--}}
{{--            resource: $('#resource').val(),--}}
{{--            limit_value: $('#limit-value').val(),--}}
{{--            description: $('#description').val(),--}}
{{--            available: $('#available').prop('checked')--}}
{{--        };--}}

{{--        const limitRow = `--}}
{{--        <tr class="${limitData.available ? '' : 'text-muted'}">--}}
{{--            <td>${limitData.resource}</td>--}}
{{--            <td>${limitData.limit_value}</td>--}}
{{--            <td>${limitData.description}</td>--}}
{{--            <td>${limitData.available ? 'Sim' : 'Não'}</td>--}}
{{--            <td>--}}
{{--                <button type="button" class="btn btn-sm btn-primary edit-limit"--}}
{{--                        data-limit='${JSON.stringify(limitData)}'>--}}
{{--                    Editar--}}
{{--                </button>--}}
{{--                <button type="button" class="btn btn-sm btn-danger remove-limit"--}}
{{--                        data-id="${limitData.id}">--}}
{{--                    Remover--}}
{{--                </button>--}}
{{--            </td>--}}
{{--        </tr>--}}
{{--    `;--}}

{{--        if (limitId) {--}}
{{--            $(`button[data-id="${limitId}"]`).closest('tr').replaceWith(limitRow);--}}
{{--        } else {--}}
{{--            $('#limits-table').append(limitRow);--}}
{{--        }--}}

{{--        $('#limitModal').modal('hide');--}}
{{--    });--}}

{{--    async function logoutAccount() {--}}
{{--        try {--}}
{{--            const response = await fetch("{{ route('logout') }}", {--}}
{{--                method: 'POST',--}}
{{--                headers: {--}}
{{--                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')--}}
{{--                }--}}
{{--            });--}}
{{--            if(response.ok){--}}
{{--                location.reload();--}}
{{--            } else {--}}
{{--                alert('Erro ao fazer logout.');--}}
{{--            }--}}
{{--        } catch(error) {--}}
{{--            console.error('Erro ao fazer logout:', error);--}}
{{--        }--}}
{{--    }--}}
{{--</script>--}}

</body>
</html>
