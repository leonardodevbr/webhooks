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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                            <input type="text" class="form-control" id="cpf" name="cpf" data-mask="###.###.###-##"
                                   maxlength="14"
                                   value="{{ old('cpf', auth()->user()->cpf ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="birth_date">Data de Nascimento</label>
                            <input type="text" class="form-control datepicker" id="birth_date" name="birth_date"
                                   data-mask="##/##/####" maxlength="10"
                                   value="{{ old('birth_date', auth()->user()->birth_date ? \Carbon\Carbon::parse(auth()->user()->birth_date)->format('d/m/Y') : '') }}"
                                   required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" data-mask="(##) #####-####"
                                   maxlength="15"
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
                            <input type="text" class="form-control" id="zipcode" name="zipcode" data-mask="#####-###"
                                   maxlength="9"
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

        <!-- Substituir a seção de assinatura existente por este código -->
        <div class="tab-pane fade pb-5" id="subscription">
            @if($subscription)
                <!-- Mantém o código existente para assinatura ativa -->
            @elseif($pendingPayment)
                <!-- Mantém o código existente para pagamento pendente -->
            @else
                <div>
                    <form id="subscription-form">
                        @csrf
                        <input type="hidden" id="selected_plan" name="plan_id">

                        <!-- Seção de Planos -->
                        <div id="plans-section">
                            <h5 class="text-center fw-bold fs-4 mb-4">Escolha um Plano</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                @foreach($plans as $plan)
                                    <div class="col">
                                        <div class="card shadow-sm text-center h-100 plan-card">
                                            <div class="card-header bg-light border-bottom fw-bold">
                                                <h5 class="mb-1">{{ $plan->name }}</h5>
                                                <p class="small text-muted mb-0">{{ $plan->description ?? '' }}</p>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="fw-bold text-dark">
                                                    R$ {{ number_format($plan->price, 2, ',', '.') }}
                                                </h4>
                                                <p class="text-muted small">
                                                    por {{ $plan->billing_cycle == 'monthly' ? 'mês' : 'ano' }}</p>
                                                <hr>
                                                <ul class="list-unstyled text-start">
                                                    @foreach($plan->plan_limits as $limit)
                                                        <li class="mb-2">
                                                            <i class="fas fa-check text-success mr-2"></i>
                                                            {{ is_bool($limit->limit_value)
                                                                ? ucfirst(str_replace('_', ' ', $limit->resource))
                                                                : ucfirst(str_replace('_', ' ', $limit->resource)) . ': ' . $limit->limit_value
                                                            }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <div class="card-footer bg-white border-top-0">
                                                <button type="button" class="btn btn-primary w-100 select-plan-btn"
                                                        data-plan-id="{{ $plan->id }}">
                                                    Escolher {{ $plan->name }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Seção de Método de Pagamento (inicialmente oculta) -->
                        <div id="payment-method-section" class="mt-4" style="display: none;">
                            <h5 class="mb-3">Escolha a forma de pagamento</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card payment-method-card" data-method="credit_card">
                                        <div class="card-body">
                                            <h6><i class="fas fa-credit-card mr-2"></i>Cartão de Crédito</h6>
                                            <small class="text-muted">Pagamento processado imediatamente</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card payment-method-card" data-method="banking_billet">
                                        <div class="card-body">
                                            <h6><i class="fas fa-qrcode mr-2"></i>Pix ou Boleto Bancário</h6>
                                            <small class="text-muted">Vencimento em 3 dias úteis</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção de Cartão de Crédito (inicialmente oculta) -->
                        <div id="credit-card-section" class="mt-4" style="display: none;">
                            <h5 class="mb-3">Dados do Cartão</h5>

                            @if(!$savedCards->isEmpty())
                                <div class="mb-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="card_type" id="new_card"
                                               value="new" checked>
                                        <label class="form-check-label" for="new_card">
                                            Usar novo cartão
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="card_type" id="saved_card"
                                               value="saved">
                                        <label class="form-check-label" for="saved_card">
                                            Usar cartão salvo
                                        </label>
                                    </div>
                                </div>

                                <!-- Cartões Salvos -->
                                <div id="saved-cards-container" class="mb-4" style="display: none;">
                                    @foreach($savedCards as $card)
                                        <div class="card mb-2 saved-card-option">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="saved_card_id"
                                                           id="card_{{ $card->id }}" value="{{ $card->id }}">
                                                    <label class="form-check-label" for="card_{{ $card->id }}">
                                                        <i class="fab fa-cc-{{ strtolower($card->card_brand) }} mr-2"></i>
                                                        **** **** **** {{ $card->card_last_four }}
                                                        <small class="text-muted">{{ $card->card_holder_name }}</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Novo Cartão -->
                            <div id="new-card-container">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label for="card_number">Número do Cartão <span id="card_brand_info"></span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="card_number"
                                                       name="card_number" maxlength="19"
                                                       data-mask="0000 0000 0000 0000">
                                                <div id="card_brand_div" class="input-group-append d-none">
                                                    <div class="input-group-text brand-append">
                                                        <span id="card_brand_icon" class="card-brand"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card_holder" class="form-label">Nome no Cartão</label>
                                        <input type="text" class="form-control" id="card_holder" name="card_holder">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="expiry" class="form-label">Validade</label>
                                        <input type="text" class="form-control" id="expiry" name="expiry"
                                               maxlength="7" data-mask="00/0000">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" name="cvv"
                                               maxlength="4" data-mask="0000">
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="save_card" name="save_card">
                                    <label class="form-check-label" for="save_card">
                                        Salvar cartão para futuras compras
                                    </label>
                                </div>
                            </div>
                        </div>
                        <input type="text" id="payment_token" name="payment_token" hidden="hidden">
                        <input type="text" id="card_mask" name="card_mask" hidden="hidden">

                        <button type="submit" class="btn btn-primary mt-4" id="confirm-subscription" disabled>
                            Confirmar Assinatura
                        </button>
                    </form>

                    <div id="payment-response" class="mt-4"></div>
                </div>
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
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('subscription-form');
        const confirmButton = document.getElementById('confirm-subscription');
        const selectedPlanInput = document.getElementById('selected_plan');
        const paymentMethodSection = document.getElementById('payment-method-section');
        const creditCardSection = document.getElementById('credit-card-section');

        let selectedPaymentMethod = null;

        // Desativa o botão no início
        confirmButton.disabled = true;

        /** ⬇️ Seleção de Plano */
        document.querySelectorAll('.select-plan-btn').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelectorAll('.select-plan-btn').forEach(btn => {
                    btn.closest('.card').classList.remove('border-primary');
                });

                this.closest('.card').classList.add('border-primary');
                selectedPlanInput.value = this.dataset.planId;

                paymentMethodSection.style.display = 'block';
                creditCardSection.style.display = 'none';
                selectedPaymentMethod = null;
                confirmButton.disabled = true; // Reinicia a validação

                paymentMethodSection.scrollIntoView({behavior: 'smooth'});
            });
        });

        /** ⬇️ Seleção de Método de Pagamento */
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', function () {
                document.querySelectorAll('.payment-method-card').forEach(c => {
                    c.classList.remove('selected');
                });

                this.classList.add('selected');
                selectedPaymentMethod = this.dataset.method;

                if (selectedPaymentMethod === 'credit_card') {
                    creditCardSection.style.display = 'block';
                    confirmButton.disabled = true; // Aguarda a validação do cartão
                    validateCreditCardForm();
                } else {
                    creditCardSection.style.display = 'none';
                    confirmButton.disabled = false; // Ativa para boleto/Pix
                }
            });
        });

        /** ⬇️ Validação do Cartão */
        function validateCreditCardForm() {
            if (selectedPaymentMethod !== 'credit_card') return;

            const cardType = document.querySelector('[name="card_type"]:checked')?.value;

            if (cardType === 'saved') {
                validateSavedCardSelection();
                return;
            }

            const cardNumber = document.getElementById('card_number').value.replace(/\D/g, '');
            const cardHolder = document.getElementById('card_holder').value.trim();
            const expiry = document.getElementById('expiry').value.replace(/\D/g, '');
            const cvv = document.getElementById('cvv').value.replace(/\D/g, '');

            const isValid = cardNumber.length >= 13 &&
                cardHolder.length >= 3 &&
                expiry.length === 6 &&
                cvv.length >= 3;

            confirmButton.disabled = !isValid; // Ativa o botão quando tudo estiver certo
        }

        // Validação de Cartão Salvo
        function validateSavedCardSelection() {
            const selectedCard = document.querySelector('[name="saved_card_id"]:checked');
            confirmButton.disabled = !selectedCard;
        }

        // Monitora mudanças no cartão salvo
        document.querySelectorAll('[name="saved_card_id"]').forEach(radio => {
            radio.addEventListener('change', validateSavedCardSelection);
        });

        // Monitora mudanças no formulário do cartão
        ['card_number', 'card_holder', 'expiry', 'cvv'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', validateCreditCardForm);
        });

        /** ⬇️ Detecção da Bandeira do Cartão */
        const cardNumberInput = document.getElementById('card_number');
        const cardBrandIcon = document.getElementById('card_brand_icon');
        const cardBrandDiv = document.getElementById('card_brand_div');
        const cardBrandText = document.getElementById('card_brand_info');

        cardNumberInput?.addEventListener('input', async function () {
            const cardNumber = this.value.replace(/\D/g, '');

            if (cardNumber.length >= 6) {
                try {
                    const brand = await EfiPay.CreditCard.setCardNumber(cardNumber).verifyCardBrand();

                    if (brand === 'unsupported' || brand === 'undefined') {
                        cardBrandText.innerHTML = ' (Bandeira não aceita)';
                        cardBrandIcon.className = 'card-brand';
                        cardBrandDiv.classList.add('d-none');
                        return;
                    }

                    cardBrandText.innerHTML = ` (${brand})`;
                    cardBrandIcon.className = `card-brand ${brand.toLowerCase()}`;
                    cardBrandDiv.classList.remove('d-none');
                } catch (error) {
                    cardBrandText.innerHTML = '';
                    cardBrandIcon.className = 'card-brand';
                    cardBrandDiv.classList.add('d-none');
                    console.error('Erro ao identificar bandeira:', error);
                }
            } else {
                cardBrandText.innerHTML = '';
                cardBrandIcon.className = 'card-brand';
                cardBrandDiv.classList.add('d-none');
            }
        });

        // 🔹 Envio do formulário via AJAX com tokenização correta
        document.getElementById("subscription-form").addEventListener("submit", async function (event) {
            event.preventDefault(); // Impede o envio até termos o token

            const submitButton = this.querySelector("button[type=submit]");
            let requestData = {
                plan_id: document.getElementById("selected_plan").value,
                payment_method: selectedPaymentMethod
            };

            submitButton.disabled = true;
            submitButton.innerText = "Processando...";

            try {
                let cardTypeInput = document.querySelector('[name="card_type"]:checked');
                let isNewCard = !cardTypeInput || cardTypeInput.value === 'new'; // Se não existir, assume 'new'

                // 🔹 Se for cartão de crédito e novo cartão, gera token antes de enviar ao backend
                if (selectedPaymentMethod === 'credit_card' && isNewCard) {
                    const cardData = {
                        brand: cardBrandIcon.classList[1], // Bandeira do cartão
                        number: cardNumberInput.value.replace(/\D/g, ""), // Número do cartão (sem espaços)
                        expirationMonth: document.getElementById("expiry").value.split("/")[0], // Mês de expiração
                        expirationYear: "20" + document.getElementById("expiry").value.split("/")[1].slice(-2), // 🔥 Corrigido!
                        cvv: document.getElementById("cvv").value, // Código de segurança
                        holderName: document.getElementById("card_holder").value, // Nome do titular
                        holderDocument: document.getElementById("cpf").value, // CPF do titular
                        reuse: true // Permite reutilização do cartão salvo
                    };

                    try {
                        // 🔹 Gera o token corretamente com a API da EfiPay
                        const result = await EfiPay.CreditCard
                            .setAccount("{{env('EFI_PAY_ACCOUNT_ID')}}")
                            .setEnvironment("{{env('EFI_PAY_ENV', 'sandbox')}}")
                            .setCreditCardData(cardData)
                            .getPaymentToken();

                        requestData.payment_token = result.payment_token;
                        requestData.card_holder = cardData.holderName;
                        requestData.card_brand = cardData.brand;
                        requestData.card_mask = result.card_mask;
                        requestData.save_card = cardData.reuse;

                    } catch (error) {
                        console.error("Erro ao gerar token do cartão:", error);
                        alert("Erro ao processar o cartão.");
                        submitButton.disabled = false;
                        submitButton.innerText = "Confirmar Assinatura";
                        return;
                    }
                }

                console.log("JSON Enviado:", requestData);

                const response = await fetch("{{ route('subscription.subscribe') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(requestData)
                });

                console.log(response);

                const result = await response.json();
                submitButton.disabled = false;
                submitButton.innerText = "Confirmar Assinatura";

                if (response.ok) {
                    document.getElementById('payment-response').innerHTML = `
                <div class="alert alert-success">
                    ${result.message}
                    ${result.redirect ? '<br>Redirecionando...' : ''}
                </div>`;

                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                } else {
                    alert(result.error || "Ocorreu um erro inesperado.");
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
                alert("Erro ao processar a assinatura.");
            }
        });


        // Máscaras para os inputs
        function applyInputMask(input, pattern) {
            input.addEventListener('input', function () {
                let value = this.value.replace(/\D/g, '');
                let maskedValue = '';
                let index = 0;

                for (let i = 0; i < pattern.length && index < value.length; i++) {
                    if (pattern[i] === '0') {
                        maskedValue += value[index];
                        index++;
                    } else {
                        maskedValue += pattern[i];
                    }
                }

                this.value = maskedValue;
            });
        }

        const maskConfigs = {
            'card_number': '0000 0000 0000 0000',
            'expiry': '00/0000',
            'cvv': '0000'
        };

        Object.entries(maskConfigs).forEach(([id, mask]) => {
            const input = document.getElementById(id);
            if (input) applyInputMask(input, mask);
        });
    })
    ;
</script>

</body>
</html>
