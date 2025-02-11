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
    <link rel="stylesheet" href="/css/card-brand.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<nav class="navbar navbar-light bg-dark px-3">
    <span class="navbar-brand logo"></span>
    <div>
        @if (auth()->check())
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="accountDropdown"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                    <a class="dropdown-item active" href="{{route('account.profile')}}">Perfil</a>
                    <a class="dropdown-item"
                       href="{{ route('account.list-urls', ['account_slug' => auth()->user()->slug]) }}">Minhas URLs</a>
                    <a class="dropdown-item" href="{{ route('plans.index') }}">Planos</a>
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
    <h3 class="mb-3">Meu Perfil</h3>

    <!-- Exibe mensagens de sucesso ou erro -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Abas de navegação -->
    <ul class="nav nav-tabs" id="profileTabs">
        <li class="nav-item">
            <a class="nav-link active" id="user-tab" data-toggle="tab" href="#user">Dados do Usuário</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="subscription-tab" data-toggle="tab" href="#subscription">Assinatura</a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <!-- Aba: Dados do Usuário -->
        <div class="tab-pane fade show active" id="user">
            <form action="{{ route('account.profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <h5>Dados Pessoais</h5>
                        <div class="form-group">
                            <label for="name">Nome Completo</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name', auth()->user()->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email', auth()->user()->email) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="cpf">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" data-mask="###.###.###-##" maxlength="14"
                                   value="{{ old('cpf', auth()->user()->cpf ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="birth_date">Data de Nascimento</label>
                            <input type="text" class="form-control datepicker" id="birth_date" name="birth_date" data-mask="##/##/####" maxlength="10"
                                   value="{{ old('birth_date', auth()->user()->birth_date ? \Carbon\Carbon::parse(auth()->user()->birth_date)->format('d/m/Y') : '') }}"
                                   required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" data-mask="(##) #####-####" maxlength="15"
                                   value="{{ old('phone', auth()->user()->phone ?? '') }}" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5>Endereço</h5>
                        <div class="form-group">
                            <label for="street">Rua</label>
                            <input type="text" class="form-control" id="street" name="street"
                                   value="{{ old('street', auth()->user()->street ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="number">Número</label>
                            <input type="text" class="form-control" id="number" name="number"
                                   value="{{ old('number', auth()->user()->number ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="neighborhood">Bairro</label>
                            <input type="text" class="form-control" id="neighborhood" name="neighborhood"
                                   value="{{ old('neighborhood', auth()->user()->neighborhood ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="zipcode">CEP</label>
                            <input type="text" class="form-control" id="zipcode" name="zipcode" data-mask="#####-###" maxlength="9"
                                   value="{{ old('zipcode', auth()->user()->zipcode ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="city">Cidade</label>
                            <input type="text" class="form-control" id="city" name="city"
                                   value="{{ old('city', auth()->user()->city ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="state">Estado</label>
                            <input type="text" class="form-control" id="state" name="state"
                                   value="{{ old('state', auth()->user()->state ?? '') }}" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Salvar</button>
            </form>
        </div>

        <!-- Aba: Assinatura -->
        <div class="tab-pane fade pb-5" id="subscription">
            @if(auth()->user()->subscription)
                <!-- Assinatura ativa -->
                <h5>Plano Atual</h5>
                <p><strong>Plano:</strong> {{ auth()->user()->subscription->plan->name }}</p>
                <p><strong>Valor:</strong>
                    R$ {{ number_format(auth()->user()->subscription->plan->price, 2, ',', '.') }}
                    /{{ ucfirst(auth()->user()->subscription->plan->billing_cycle) }}</p>
                <p><strong>Status:</strong> Ativo</p>

                <form action="{{ route('subscription.cancel') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-danger">Cancelar Assinatura</button>
                </form>

                <h5 class="mt-4">Histórico de Pagamentos</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>ID Transação</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Método</th>
                            <th>Data do Pagamento</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse(auth()->user()->payments as $payment)
                            <tr>
                                <td>{{ $payment->gateway_reference }}</td>
                                <td>
                                    @if($payment->status == 'approved' || $payment->status == 'paid')
                                        <span class="badge badge-success">Pago</span>
                                    @elseif($payment->status == 'pending')
                                        <span class="badge badge-warning">Pendente</span>
                                    @elseif($payment->status == 'failed')
                                        <span class="badge badge-danger">Falhou</span>
                                    @elseif($payment->status == 'expired')
                                        <span class="badge badge-secondary">Expirado</span>
                                    @elseif($payment->status == 'refunded')
                                        <span class="badge badge-info">Reembolsado</span>
                                    @else
                                        <span class="badge badge-dark">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td>R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                                <td>{{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Nenhum pagamento registrado.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            @else
                <!-- Sem assinatura, exibir planos -->
                <h5>Escolha um Plano</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Plano</th>
                            <th>Preço</th>
                            <th>URLs</th>
                            <th>Webhooks por URL</th>
                            <th>Retransmissões</th>
                            <th>Notificações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td>{{ $plan->name }}</td>
                                <td>R$ {{ number_format($plan->price, 2, ',', '.') }}
                                    /{{ ucfirst($plan->billing_cycle) }}</td>
                                <td>{{ $plan->max_urls }}</td>
                                <td>{{ $plan->max_webhooks_per_url }}</td>
                                <td>{{ $plan->max_retransmission_urls }}</td>
                                <td>{{ $plan->real_time_notifications ? 'Sim' : 'Não' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <form action="{{ route('subscription.subscribe') }}" id="form_subscribe" method="POST">
                    <div class="form-group">
                        <label for="plan">Selecione um Plano</label>
                        <select class="form-control" id="plan" name="plan_id" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} -
                                    R$ {{ number_format($plan->price, 2, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Forma de Pagamento</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="boleto">Boleto Bancário</option>
                            <option value="credit_card">Cartão de Crédito</option>
                        </select>
                    </div>

                    <!-- Campos do Cartão -->
                    <div id="card_fields" style="display: none;">
                        <h5 class="mt-3">Dados do Cartão</h5>

                        @if(!empty($savedCards))
                            <!-- Escolher um cartão salvo -->
                            <div class="form-group">
                                <label for="saved_cards">Usar Cartão Salvo</label>
                                <select class="form-control" id="saved_cards">
                                    <option value="">Novo Cartão</option>
                                    @foreach($savedCards as $card)
                                        <option value="{{ $card->payment_token }}">{{ $card->card_mask }}
                                            - {{ $card->card_brand }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Campos do Novo Cartão -->
                        <div id="new_card_fields">
                            <div class="form-group">
                                <label for="card_number">Número do Cartão <span id="card_brand_info"></span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="card_number" name="card_number"
                                           maxlength="19">
                                    <div id="card_brand_div" class="input-group-append d-none">
                                        <div class="input-group-text brand-append">
                                            <span id="card_brand_icon" class="card-brand"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="card_holder">Nome no Cartão</label>
                                <input type="text" class="form-control" id="card_holder" name="card_holder">
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="expiration">Validade (MM/AA)</label>
                                        <input type="text" class="form-control" id="expiration" name="expiration">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="cvv">CVV</label>
                                        <input type="text" class="form-control" id="cvv" name="cvv">
                                    </div>
                                </div>
                            </div>

                            <!-- Input oculto para armazenar o token e card_mask -->
                            <input type="hidden" id="payment_token" name="payment_token">
                            <input type="hidden" id="card_mask" name="card_mask">
                            <input type="hidden" id="card_brand" name="card_brand">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="save_card" name="save_card">
                                <label class="form-check-label" for="save_card">Salvar cartão para futuras
                                    transações</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Confirmar Assinatura</button>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://cdn.jsdelivr.net/gh/efipay/js-payment-token-efi/dist/payment-token-efi-umd.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const cardNumberInput = document.getElementById("card_number");
        const cardBrandInput = document.getElementById("card_brand");
        const cardBrandInfo = document.getElementById("card_brand_info");
        const cardBrandDiv = document.getElementById("card_brand_div");
        const cardBrandIcon = document.getElementById("card_brand_icon");

        // Detectar a bandeira quando o usuário digitar os primeiros 4 números
        cardNumberInput.addEventListener("input", async function () {
            const cardNumber = this.value.replace(/\D/g, ""); // Remove caracteres não numéricos

            if (cardNumber.length >= 4) {
                try {
                    const brand = await EfiPay.CreditCard.setCardNumber(cardNumber).verifyCardBrand();
                    if (brand === 'unsupported' || brand === 'undefined' ) {
                        cardBrandIcon.className = "card-brand";
                        cardBrandInfo.innerHTML = " (Bandeira não aceita)";
                        return;
                    }
                    cardBrandInfo.innerHTML = " (" + brand + ")";
                    console.log("Bandeira: ", brand);

                    cardBrandInput.value = brand;
                    cardBrandDiv.classList.remove('d-none');
                    cardBrandIcon.className = "card-brand";
                    cardBrandIcon.classList.add(brand);
                } catch (error) {
                    console.error("Erro ao identificar a bandeira:", error);
                    cardBrandInput.value = "";
                    cardBrandIcon.className = "card-brand";
                    cardBrandDiv.classList.add('d-none');
                    cardBrandInfo.innerHTML = "";
                }
            } else {
                cardBrandInput.value = "";
                cardBrandIcon.className = "card-brand";
                cardBrandInfo.innerHTML = "";
            }
        });

        const requiredFields = document.querySelectorAll("#user input[required]");
        const subscriptionTab = document.getElementById("subscription-tab");

        function checkFormCompletion() {
            let allFilled = true;
            requiredFields.forEach(input => {
                if (!input.value.trim()) {
                    allFilled = false;
                }
            });

            if (allFilled) {
                subscriptionTab.classList.remove("disabled");
            } else {
                subscriptionTab.classList.add("disabled");
            }
        }

        requiredFields.forEach(input => {
            input.addEventListener("input", checkFormCompletion);
        });

        checkFormCompletion();

        // Função para gerar o payment_token antes de enviar o formulário
        document.getElementById("form_subscribe").addEventListener("submit", async function (event) {
            event.preventDefault(); // Impede o envio até termos o token

            const cardData = {
                brand: document.getElementById("card_brand").value,
                number: cardNumberInput.value.replace(/\D/g, ""),
                expirationMonth: document.getElementById("expiration").value.split("/")[0],
                expirationYear: "20" + document.getElementById("expiration").value.split("/")[1],
                cvv: document.getElementById("cvv").value,
                holderName: document.getElementById("card_holder").value,
                holderDocument: document.getElementById("cpf").value,
                reuse: true
            };

            try {
                const result = await EfiPay.CreditCard
                    .setAccount("{{env('EFI_PAY_ACCOUNT_ID')}}")
                    .setEnvironment("{{env('EFI_PAY_ENV', 'sandbox')}}")
                    .setCreditCardData(cardData)
                    .getPaymentToken();

                document.getElementById("payment_token").value = result.payment_token;
                document.getElementById("card_mask").value = result.card_mask;

                this.submit(); // Agora pode enviar
            } catch (error) {
                console.error("Erro ao gerar token do cartão:", error);
                alert("Erro ao processar o cartão.");
            }
        });

        function applyMask(input, mask) {
            input.addEventListener("input", () => formatInput(input, mask));
            input.addEventListener("paste", () => formatInput(input, mask));

            if (input.value) {
                formatInput(input, mask);
            }
        }

        function formatInput(input, mask) {
            let value = input.value.replace(/\D/g, ""); // Remove tudo que não for número
            let maskedValue = "";
            let maskIndex = 0;
            let valueIndex = 0;

            while (maskIndex < mask.length && valueIndex < value.length) {
                if (mask[maskIndex] === "#") {
                    maskedValue += value[valueIndex++];
                } else {
                    maskedValue += mask[maskIndex];
                }
                maskIndex++;
            }

            input.value = maskedValue;
        }

        // Aplica máscaras automaticamente em todos os inputs com [data-mask]
        document.querySelectorAll("[data-mask]").forEach(input => {
            applyMask(input, input.getAttribute("data-mask"));
        });
    });

    $(document).ready(function () {
        $('.datepicker').datepicker({
            dateFormat: 'dd/mm/yy',  // Exibe no formato PT-BR
            changeMonth: true,
            changeYear: true,
            yearRange: "1900:2025",
            showMonthAfterYear: true,
            showButtonPanel: true,
            closeText: "Fechar",
            prevText: "Anterior",
            nextText: "Próximo",
            currentText: "Hoje",
            monthNames: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
            monthNamesShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
                "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
            dayNames: ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"],
            dayNamesShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
            dayNamesMin: ["D", "S", "T", "Q", "Q", "S", "S"],
            weekHeader: "Sm",
            firstDay: 0,
            isRTL: false,
            yearSuffix: "",
            autoclose: true
        });
    });

    document.getElementById("payment_method").addEventListener("change", function () {
        let paymentMethod = this.value;
        let cardFields = document.getElementById("card_fields");

        cardFields.style.display = paymentMethod === "credit_card" ? "block" : "none";
    });

    async function logoutAccount() {
        try {
            const response = await fetch("{{ route('logout') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            if (response.ok) {
                location.reload();
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
