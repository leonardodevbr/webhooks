// public/js/plan-form.js
$(document).ready(function() {
    $('form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const url = form.attr('action');
        const limits = collectLimits();

        // Validação básica dos limites
        if (limits.length === 0) {
            form.prepend('<div class="alert alert-danger">É necessário adicionar pelo menos um limite ao plano.</div>');
            return false;
        }

        const formData = new FormData(form[0]);
        formData.append('limits', JSON.stringify(limits));

        // Desabilitar botão e mostrar loading
        const submitButton = form.find('button[type="submit"]');
        const originalText = submitButton.text();
        submitButton.prop('disabled', true).text('Criando plano...');

        // Limpar mensagens de erro anteriores
        $('.alert-danger').remove();
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    localStorage.setItem('flash_success', response.message);
                    window.location.href = response.redirect;
                }
            },
            error: function(xhr) {
                submitButton.prop('disabled', false).text(originalText);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;

                    // Criar mensagem de erro geral
                    const errorHtml = '<div class="alert alert-danger"><ul>' +
                        Object.values(errors).map(error => `<li>${error[0]}</li>`).join('') +
                        '</ul></div>';
                    form.prepend(errorHtml);

                    // Marcar campos com erro
                    Object.keys(errors).forEach(field => {
                        const input = $(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
                    });

                    // Scroll para o topo em caso de erro
                    $('html, body').animate({
                        scrollTop: $('.alert-danger').offset().top - 20
                    }, 500);
                } else {
                    // Erro inesperado ou da API externa
                    const errorMessage = xhr.responseJSON?.message || 'Ocorreu um erro ao criar o plano. Por favor, tente novamente.';
                    form.prepend(`<div class="alert alert-danger">${errorMessage}</div>`);
                }
            }
        });
    });

    // Adicionar limite
    $('#add-limit').click(function() {
        $('#limitModal').modal('show');
        clearModalFields();
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

            fillModalFields(limit);
            $('#limitModal').modal('show');
        } catch (e) {
            console.error('Erro ao fazer parse do JSON:', e);
            console.log('Dados recebidos:', $(this).data('limit'));
        }
    });

    // Salvar limite
    $('#save-limit').click(function() {
        var limitData = collectModalData();
        var limitRow = createLimitRow(limitData);

        if (limitData.id) {
            // Atualizar limite existente
            $(`button[data-id="${limitData.id}"]`).closest('tr').replaceWith(limitRow);
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

function collectLimits() {
    const limits = [];
    $('#limits-table tr').each(function() {
        // Tenta pegar os dados do data-limit primeiro (para itens editados)
        const limitData = $(this).find('.edit-limit').data('limit');

        if (limitData) {
            // Se tiver data-limit, usa esses dados (item editado)
            limits.push({
                id: limitData.id || null,
                resource: limitData.resource,
                limit_value: limitData.limit_value,
                description: limitData.description,
                available: limitData.available
            });
        } else {
            // Se não tiver data-limit, coleta dados diretamente das células
            const cells = $(this).find('td');
            if (cells.length > 0) {
                limits.push({
                    id: $(this).find('.remove-limit').data('id') || null,
                    resource: cells.eq(0).text().toLowerCase().replace(/ /g, '_'), // Reverte a transformação do ucfirst e str_replace
                    limit_value: parseFloat(cells.eq(1).text()) || 0,
                    description: cells.eq(2).text(),
                    available: cells.eq(3).text().toLowerCase() === 'sim'
                });
            }
        }
    });
    return limits;
}

// Funções auxiliares
function clearModalFields() {
    $('#limit-id').val('');
    $('#resource').val('');
    $('#limit-value').val('');
    $('#description').val('');
    $('#available').prop('checked', true);
}

function fillModalFields(limit) {
    $('#limit-id').val(limit.id);
    $('#resource').val(limit.resource);
    $('#limit-value').val(limit.limit_value);
    $('#description').val(limit.description);
    $('#available').prop('checked', limit.available);
}

function collectModalData() {
    return {
        id: $('#limit-id').val(),
        resource: $('#resource').val(),
        limit_value: $('#limit-value').val(),
        description: $('#description').val(),
        available: $('#available').prop('checked')
    };
}

function createLimitRow(limitData) {
    return `
        <tr class="${limitData.available ? '' : 'text-muted'}">
            <td>${limitData.resource}</td>
            <td>${limitData.limit_value}</td>
            <td>${limitData.description}</td>
            <td>${limitData.available ? 'Sim' : 'Não'}</td>
            <td>
                <button type="button" class="btn btn-sm btn-primary edit-limit"
                        data-limit='${JSON.stringify(limitData)}'>
                    Editar
                </button>
                <button type="button" class="btn btn-sm btn-danger remove-limit"
                        data-id="${limitData.id}">
                    Remover
                </button>
            </td>
        </tr>
    `;
}

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
