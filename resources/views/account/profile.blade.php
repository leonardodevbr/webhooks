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
<div class="container pt-4">
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
                <!-- Assinatura ativa -->
                <h5>Plano Atual</h5>
                <p><strong>Plano:</strong> {{ $subscription->plan->name }}</p>
                <p><strong>Valor:</strong>
                    R$ {{ number_format($subscription->plan->price, 2, ',', '.') }}
                    /{{ ucfirst($subscription->plan->billing_cycle) }}</p>
                <p><strong>Status:</strong> {{$subscription->is_active ? "Ativa" : "Pendente"}}</p>

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
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->external_payment_id }}</td>
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

                <form action="{{ route('subscription.cancel',['subscription_id' => $subscription->id]) }}" method="POST"
                      class="text-right">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Cancelar Assinatura</button>
                </form>
            @elseif($pendingPayment)
                <div class="row">
                    <div class="col-md-6">
                        <h6>💳 Pague via Boleto</h6>
                        <p><strong>Vencimento:</strong> {{ $pendingPayment->details['expire_at'] }}</p>
                        <p><strong>Código de Barras:</strong> <br>
                            <span
                                style="font-size: 18px; font-family: monospace;">{{ $pendingPayment->details['barcode'] }}</span>
                        </p>
                        <a href="{{ $pendingPayment->details['billet_link'] }}" class="btn btn-primary" target="_blank">
                            🔗 Visualizar Boleto
                        </a>
                        <a href="{{ $pendingPayment->details['pdf'] }}" class="btn btn-secondary" target="_blank">
                            📄 Baixar PDF
                        </a>
                    </div>

                    <div class="col-md-6">
                        <h6>🔗 Pague via PIX</h6>
                        <p><strong>Escaneie o QR Code:</strong></p>
                        <img src="{{ $pendingPayment->details['pix']['qrcode_image'] }}" alt="QR Code PIX"
                             class="img-fluid" width="250">
                        <p><strong>Código PIX:</strong></p>
                        <textarea class="form-control" rows="2" onclick="this.select(); document.execCommand('copy');">
                {{ $pendingPayment->details['pix']['qrcode'] }}
            </textarea>
                        <small class="text-muted">Clique para copiar o código</small>
                    </div>
                </div>
            @else
                <div>
                    <form id="subscription-form">
                        @csrf
                        <!-- Seção de Planos -->
                        <div id="plans-section">
                            <h5 class="text-center fw-bold fs-4 mb-4">Escolha um Plano</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                @foreach($plans as $index => $plan)
                                    <div class="col">
                                        <div class="card shadow-sm text-center h-100 plan-card">
                                            <div class="card-header bg-transparent border-bottom fw-bold">
                                                <h5 class="mb-1">{{ $plan->name }}</h5>
                                                <p class="small text-muted mb-0">{{ $plan->description ?? '' }}</p>
                                            </div>
                                            <div class="card-body">
                                                <h4 class="fw-bold text-dark">
                                                    R$ {{ number_format($plan->price, 2, ',', '.') }}
                                                </h4>
                                                <p class="text-muted small">
                                                    por {{ $plan->billing_cycle == 'monthly' ? 'mês' : 'ano' }}
                                                </p>
                                                <hr>
                                                <ul class="text-left list-unstyled">
                                                    @foreach($plan->plan_limits as $limit)
                                                        <li class="mb-2 {{ $limit->available ? '' : 'text-muted' }}">
                                                            <i class="fas {{ $limit->available ? 'fa-check text-success' : 'fa-times text-danger' }} mr-2"></i>
                                                            {{ ucfirst(str_replace('_', ' ', $limit->resource)) }}
                                                            @if($limit->available && $limit->limit_value)
                                                                : {{ $limit->limit_value }}
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <div class="card-footer bg-transparent border-top-0">
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
                                <div id="card-type-section" class="mb-4">
                                    <h6 class="mb-3">Escolha uma opção</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card card-type-option selected" data-type="saved">
                                                <div class="card-body">
                                                    <h6><i class="fas fa-credit-card mr-2"></i>Usar Cartão Salvo</h6>
                                                    <small class="text-muted">Escolher um cartão já cadastrado</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card card-type-option" data-type="new">
                                                <div class="card-body">
                                                    <h6><i class="fas fa-plus-circle mr-2"></i>Novo Cartão</h6>
                                                    <small class="text-muted">Adicionar um novo cartão de
                                                        crédito</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cartões Salvos -->
                                <div id="saved-cards-container" class="mb-4">
                                    <h6 class="mb-3">Selecione o cartão</h6>
                                    <div class="row">
                                        @foreach($savedCards as $card)
                                            <div class="col-md-6">
                                                <div class="card mb-2 flex-row saved-card-option"
                                                     data-card-id="{{ $card->id }}" data-payment-token="{{$card->payment_token}}">
                                                    <div class="card-body">
                                                        <span class="card-info">{{ $card->card_mask }}</span>
                                                    </div>
                                                    <div
                                                        class="card-footer d-flex align-content-center align-items-center border-0">
                                                        <span class="card-brand {{$card->card_brand}}"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Novo Cartão -->
                            <div id="new-card-container" class="mb-4 {{!$savedCards->isEmpty() ? 'd-none' :  ''}}">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
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
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="card_holder" class="form-label">Nome no Cartão</label>
                                        <input type="text" class="form-control" id="card_holder" name="card_holder">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="expiry" class="form-label">Validade</label>
                                        <input type="text" class="form-control" id="expiry" name="expiry" maxlength="7"
                                               data-mask="00/0000">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" name="cvv" maxlength="4"
                                               data-mask="0000">
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="save_card" name="save_card">
                                    <label class="form-check-label" for="save_card">Salvar cartão para futuras
                                        compras</label>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="selected_plan" name="plan_id">

                        <button type="submit" class="btn btn-primary mt-4" id="confirm-subscription" disabled>Confirmar
                            Assinatura
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
        const subscriptionTab = document.getElementById('subscription-tab');
        const userForm = document.querySelector('#user form');
        const requiredFields = userForm.querySelectorAll('[required]');
        const subscriptionForm = document.getElementById('subscription-form');
        const confirmButton = document.getElementById('confirm-subscription');
        const selectedPlanInput = document.getElementById('selected_plan');
        const paymentMethodSection = document.getElementById('payment-method-section');
        const creditCardSection = document.getElementById('credit-card-section');
        const savedCardsContainer = document.getElementById('saved-cards-container');
        const newCardContainer = document.getElementById('new-card-container');

        let selectedCard = null;

        function checkUserFormCompletion() {
            const isComplete = Array.from(requiredFields).every(field => field.value.trim() !== '');
            subscriptionTab.classList.toggle('disabled', !isComplete);
        }

        userForm?.addEventListener('input', checkUserFormCompletion);
        checkUserFormCompletion(); // Verifica o estado inicial ao carregar a página

        subscriptionTab?.addEventListener('click', function (event) {
            if (this.classList.contains('disabled')) {
                event.preventDefault();
                alert('Por favor, preencha todos os campos na aba "Dados do Usuário" antes de acessar a aba "Assinatura".');
            }
        });

        let selectedPaymentMethod = null;

        // Desativa o botão no início
        confirmButton?.setAttribute('disabled', 'true');

        /** ⬇️ Seleção de Plano */
        document.querySelectorAll('.select-plan-btn').forEach(button => {
            button?.addEventListener('click', function () {
                document.querySelectorAll('.select-plan-btn').forEach(btn => {
                    btn.closest('.card').classList.remove('selected');
                });

                this.closest('.card').classList.add('selected');
                selectedPlanInput.value = this.dataset.planId;

                paymentMethodSection.style.display = 'block';
                creditCardSection.style.display = 'none';
                selectedPaymentMethod = null;
                confirmButton?.setAttribute('disabled', 'true');

                paymentMethodSection.scrollIntoView({behavior: 'smooth'});
            });
        });

        /** ⬇️ Seleção de Método de Pagamento */
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card?.addEventListener('click', function () {
                document.querySelectorAll('.payment-method-card').forEach(c => {
                    c.classList.remove('selected');
                });

                this.classList.add('selected');
                selectedPaymentMethod = this.dataset.method;

                if (selectedPaymentMethod === 'credit_card') {
                    creditCardSection.style.display = 'block';
                    confirmButton?.setAttribute('disabled', 'true');
                    validateCreditCardForm();
                } else {
                    creditCardSection.style.display = 'none';
                    confirmButton?.removeAttribute('disabled');
                }
            });
        });

        /** ⬇️ Seleção entre cartão novo ou existente */
        document.querySelectorAll('.card-type-option').forEach(cardType => {
            cardType?.addEventListener('click', function () {
                document.querySelectorAll('.card-type-option').forEach(c => {
                    c.classList.remove('selected');
                });

                this.classList.add('selected');
                const selectedType = this.dataset.type;

                if (selectedType === 'saved') {
                    savedCardsContainer?.classList.remove('d-none');
                    newCardContainer?.classList.add('d-none');
                    validateSavedCardSelection(); // Chama a validação ao trocar para cartão salvo
                } else {
                    savedCardsContainer?.classList.add('d-none');
                    newCardContainer?.classList.remove('d-none');
                    selectedCard = null;
                    document.querySelectorAll('.saved-card-option').forEach(c => {
                        c.classList.remove('selected');
                    });
                    confirmButton?.setAttribute('disabled', 'true'); // Desativa o botão ao trocar para novo cartão
                }
            });
        });

        /** ⬇️ Seleção de cartão existente */
        document.querySelectorAll('.saved-card-option').forEach(card => {
            card?.addEventListener('click', function () {
                document.querySelectorAll('.saved-card-option').forEach(c => {
                    c.classList.remove('selected');
                });

                this.classList.add('selected');
                selectedCard = this.dataset.cardId;
                validateSavedCardSelection(); // Chama a validação ao selecionar um cartão
            });
        });

        /** ⬇️ Validação do Cartão */
        function validateCreditCardForm() {
            if (selectedPaymentMethod !== 'credit_card') return;

            const selectedCard = document.querySelector('.card-type-option.selected').dataset.type;

            if (selectedCard === 'saved') {
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

            if (!isValid) {
                confirmButton?.setAttribute('disabled', 'true');
            } else {
                confirmButton?.removeAttribute('disabled');
            }
        }

        // Validação de Cartão Salvo
        function validateSavedCardSelection() {
            const selectedCard = document.querySelector('.saved-card-option.selected');
            if (!selectedCard) {
                confirmButton?.setAttribute('disabled', 'true');
            } else {
                confirmButton?.removeAttribute('disabled');
            }
        }

        // Monitora mudanças no cartão salvo
        document.querySelectorAll('[name="saved_card_id"]').forEach(radio => {
            radio?.addEventListener('change', validateSavedCardSelection);
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
        subscriptionForm?.addEventListener("submit", async function (event) {
            event.preventDefault(); // Impede o envio até termos o token
            const overlay = document.createElement('div');
            overlay.className = 'overlay';
            overlay.innerHTML = `<div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Processando...</span>
                </div>
            <p>Processando assinatura...</p>`;
            document.body.appendChild(overlay);

            const submitButton = this.querySelector("button[type=submit]");
            let requestData = {
                plan_id: document.getElementById("selected_plan").value,
                payment_method: selectedPaymentMethod
            };

            submitButton.disabled = true;
            submitButton.innerText = "Processando...";

            try {
                // 🔹 Se for cartão de crédito e novo cartão, gera token antes de enviar ao backend
                if (selectedPaymentMethod === 'credit_card') {
                    let isNewCard = newCardContainer.classList.contains('d-none') === false;

                    if (isNewCard) {
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
                    } else {
                        // Lógica para cartão existente
                        const selectedCard = document.querySelector('.saved-card-option.selected');
                        if (selectedCard) {
                            requestData.payment_token = selectedCard.dataset.paymentToken;
                        }
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

                const result = await response.json();
                console.log(result);

                if (response.ok) {
                    document.getElementById('payment-response').innerHTML = `
                <div class="alert alert-success">
                    ${result.message}
                    ${result.redirect ? '<br>Redirecionando...' : ''}
                </div>`;

                    if (result.redirect) {
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 2000);
                    } else {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    document.getElementById('payment-response').innerHTML = `
                <div class="alert alert-danger">
                    ${result.error || "Ocorreu um erro inesperado. Por favor, tente novamente mais tarde."}
                </div>`;
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
                alert("Erro ao processar a assinatura. Por favor, tente novamente mais tarde.");
            }

            document.body.removeChild(overlay);
            submitButton.disabled = false;
            submitButton.innerText = "Confirmar Assinatura";
        });


        // Máscaras para os inputs
        function applyInputMask(input, pattern) {
            input?.addEventListener('input', function () {
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
    });
</script>

</body>
</html>
