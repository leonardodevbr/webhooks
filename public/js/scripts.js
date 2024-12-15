document.addEventListener("DOMContentLoaded", async () => {
    const pusherKey = window.env.PUSHER_KEY;
    const pusherCluster = window.env.PUSHER_CLUSTER;
    const pusherChannel = window.env.PUSHER_CHANNEL;

    const notificationsEnabled = localStorage.getItem("notificationsEnabled") === "true";
    const showInitialNotification = localStorage.getItem("showInitialNotification") === "true";
    const hasSeenModal = localStorage.getItem("hasSeenFeatureModal") === "true";
    window.retransmitUrls = await loadRetransmissionUrls();
    // Pusher.logToConsole = true;

    // Configuração do Modal para ser exibido apenas uma vez
    if (!hasSeenModal) {
        $('#featureModal').modal({
            backdrop: 'static', keyboard: false
        }).modal('show');

        function markModalAsSeen() {
            localStorage.setItem("hasSeenFeatureModal", "true");
            $('#featureModal').modal('hide');
        }

        // Botão "Entendido"
        document.getElementById("understoodButton").addEventListener("click", markModalAsSeen);

        // Botão "Ok! Habilite as notificações"
        document.getElementById("enableNotifications").addEventListener("click", async () => {
            const permission = await Notification.requestPermission();
            if (permission === "granted") {
                localStorage.setItem("notificationsEnabled", "true");
                localStorage.setItem("showInitialNotification", "true");
                updateNotificationButton(true);

                // Marca o modal como visto e recarrega a página para exibir a notificação inicial
                markModalAsSeen();
                location.reload();
            } else {
                alert("Permissão para notificações foi negada.");
            }
        });
    }

    // Configuração de Pusher para notificar sobre novos webhooks
    const pusher = new Pusher(pusherKey, {
        cluster: pusherCluster, forceTLS: true
    });
    const channel = pusher.subscribe(pusherChannel);

    channel.bind('new-webhook', async function (eventData) {
        try {// Fazendo a requisição com o ID do webhook recebido
            const response = await fetch(route('webhook.load-single', {id: eventData.id}), {method: 'GET'});

            // Parseando os dados recebidos
            const data = await response.json();

            // Adicionando o webhook ao topo da lista
            addWebhookToTop(data);

            // Exibindo notificação, se permitido
            if (notificationsEnabled && Notification.permission === "granted") {
                showNotification(data);
            }
        } catch (error) {
            console.error("Erro ao carregar o webhook:", error);
        }
    });

    channel.bind('webhook-retransmitted', async function (eventData) {
        showForwardedInfo(eventData.id);
    });

    channel.bind('local-retransmission', async function (eventData) {
        // Busca os dados do webhook a partir do backend
        const webhookResponse = await fetch(route('webhook.load-single', {id: eventData.id}), {method: 'GET'});
        if (!webhookResponse.ok) {
            throw new Error("Erro ao carregar o webhook.");
        }
        const webhook = await webhookResponse.json();

        const queryParams = webhook.query_params ? new URLSearchParams(webhook.query_params).toString() : "";
        const fullUrl = queryParams ? `${eventData.url}?${queryParams}` : eventData.url;

        const xhr = new XMLHttpRequest();
        xhr.open(webhook.method, fullUrl);

        // Configura os headers válidos
        if (webhook.headers && typeof webhook.headers === 'object') {
            const unsafeHeaders = ["host", "cookie", "connection", "user-agent", "content-length", "accept-encoding",];

            Object.entries(webhook.headers).forEach(([key, value]) => {
                if (!unsafeHeaders.includes(key.toLowerCase())) {
                    const headerValue = Array.isArray(value) ? value.join(", ") : value;
                    xhr.setRequestHeader(key, headerValue);
                }
            });
        }

        // Verifica o método HTTP e envia os dados adequados
        if (['POST', 'PUT', 'PATCH'].includes(webhook.method)) {
            if (webhook.body) {
                try {
                    const jsonBody = JSON.parse(webhook.body); // Testa se é JSON válido
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.send(JSON.stringify(jsonBody)); // Envia como JSON
                } catch (error) {
                    xhr.setRequestHeader('Content-Type', 'text/plain');
                    xhr.send(webhook.body); // Envia o corpo bruto como texto
                }
            } else if (webhook.form_data) {
                const formData = new FormData();
                Object.entries(webhook.form_data).forEach(([key, value]) => {
                    formData.append(key, value);
                });
                xhr.send(formData); // Envia como FormData
            } else {
                xhr.send(); // Envia requisição sem corpo
            }
        } else if (['GET', 'DELETE'].includes(webhook.method)) {
            xhr.send(); // Envia requisição sem corpo
        } else {
            xhr.send(); // Envia requisição genérica
        }
    });

    // Exibe a notificação inicial ao recarregar a página e autorizar
    if (showInitialNotification && notificationsEnabled && Notification.permission === "granted") {
        try {
            new Notification("Notificações Ativadas", {
                body: "Você receberá atualizações em tempo real para cada novo webhook.",
                icon: "/apple-touch-icon.png",
                requireInteraction: true
            });
            // Remover o item após exibir a notificação
            localStorage.removeItem("showInitialNotification");
        } catch (error) {
            console.error("Erro ao exibir notificação:", error);
        }
    }

    updateNotificationButton(notificationsEnabled);
    $('[data-toggle="tooltip"]').tooltip();

    $('#retransmitUrlsModal').on('shown.bs.modal', () => {
        displayRetransmissionUrls(retransmitUrls);
    });

    $('#accountModal').on('shown.bs.modal', () => {
        validateForm('loginForm', 'loginSubmit');
        validateForm('registerForm', 'registerSubmit');
    });
});

function displayRetransmissionUrls(retransmitUrls) {
    const tbody = document.getElementById('urlList'); // Corpo da tabela
    tbody.innerHTML = ''; // Limpa a tabela atual

    if (retransmitUrls.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Nenhuma URL cadastrada.</td></tr>';
        return;
    }

    retransmitUrls.forEach(url => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${url.url}</td>
            <td>${url.is_online ? 'Sim' : 'Não'}</td>
            <td>
                <button onclick="removeRetransmissionUrl(${url.id})" class="btn btn-danger btn-sm">Remover</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}


async function removeRetransmissionUrl(id) {
    try {
        const response = await fetch(route('webhook.retransmission.remove', {id}), {
            method: 'DELETE',
        });

        if (!response.ok) {
            throw new Error("Erro ao remover a URL de retransmissão.");
        }

        console.log(`URL ID ${id} removida com sucesso.`);
        window.retransmitUrls = await loadRetransmissionUrls();
    } catch (error) {
        console.error("Erro ao remover a URL de retransmissão:", error);
    }
}

async function addRetransmissionUrl() {
    const urlInput = document.getElementById('retransmitUrlInput');
    const retransmitTypeSelect = document.getElementById('retransmitTypeSelect');
    const saveButton = document.querySelector('#retransmitUrlsContainer button');

    const url = urlInput.value.trim();
    const isOnline = retransmitTypeSelect.value === "1"; // Converte 1 para true e 0 para false

    if (!url) {
        alert("URL não pode estar vazia.");
        return;
    }

    // Desabilitar o botão enquanto processa
    saveButton.disabled = true;
    saveButton.innerText = "Salvando...";

    try {
        const response = await fetch(route('webhook.retransmission.add'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                url,
                is_online: isOnline, // Envia true ou false com base no valor do select
                url_id: urlId, // URL pai associada
            }),
        });

        if (!response.ok) {
            throw new Error("Erro ao adicionar a URL de retransmissão.");
        }

        // Limpa os campos
        urlInput.value = '';
        retransmitTypeSelect.value = "0"; // Restaura para "Local" (valor padrão)

        // Atualiza a lista
        window.retransmitUrls = await loadRetransmissionUrls();
        displayRetransmissionUrls(window.retransmitUrls);
    } catch (error) {
        console.error("Erro ao adicionar a URL de retransmissão:", error);
        alert("Erro ao adicionar a URL de retransmissão. Tente novamente.");
    } finally {
        // Reabilita o botão
        saveButton.disabled = false;
        saveButton.innerText = "Salvar";
    }
}


async function loadRetransmissionUrls() {
    if(urlId === "" || urlId === undefined){
        return;
    }
    try {
        const response = await fetch(route('webhook.retransmission.list-for-url', {url_id: urlId}), {method: 'GET'});

        if (!response.ok) {
            throw new Error("Erro ao carregar URLs de retransmissão.");
        }

        const retransmitUrls = await response.json();
        displayRetransmissionUrls(retransmitUrls);
        loadWebhooks();
        return retransmitUrls
    } catch (error) {
        console.error("Erro ao carregar URLs de retransmissão:", error);
        return [];
    }
}

async function retransmitWebhook(id) {
    try {
        // Envia para o backend processar URLs online
        const backendResponse = await fetch(route('webhook.retransmit', {id: id}), {
            method: 'POST', headers: {
                'Content-Type': 'application/json',
            },
        });

        if (!backendResponse.ok) {
            console.error(`Erro ao retransmitir para as URLs onlines.`);
        }

    } catch (error) {
        console.error("Erro ao retransmitir webhook:", error);
    }
}

function showForwardedInfo(webhookId) {
    const item = document.getElementById(`item-${webhookId}`);
    if (item) {
        const forwardedLabel = item.querySelector('.forwarded');
        if (forwardedLabel) {
            forwardedLabel.classList.remove('d-none'); // Remove a classe d-none para exibir o rótulo
        }
    }
}

async function createNewUrl() {
    // Exibe uma caixa de confirmação para o usuário
    const confirmation = confirm("Ao confirmar, uma nova URL será gerada e você será redirecionado para a página correspondente. Deseja continuar?");

    if (confirmation) {
        // Exibe uma tela de carregamento antes do redirecionamento
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'white';
        overlay.style.display = 'flex';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        overlay.style.zIndex = '10000';

        const loadingMessage = document.createElement('div');
        loadingMessage.style.textAlign = 'center';

        const loadingText = document.createElement('p');
        loadingText.innerText = 'Redirecionando para nova URL...';
        loadingText.style.fontSize = '24px';
        loadingText.style.marginBottom = '20px';

        const loadingIcon = document.createElement('div');
        loadingIcon.className = 'spinner-border text-primary';
        loadingIcon.setAttribute('role', 'status');

        const spinnerText = document.createElement('span');
        spinnerText.className = 'sr-only';
        spinnerText.innerText = 'Loading...';
        loadingIcon.appendChild(spinnerText);

        loadingMessage.appendChild(loadingText);
        loadingMessage.appendChild(loadingIcon);
        overlay.appendChild(loadingMessage);
        document.body.appendChild(overlay);

        try {
            const response = await fetch(route('webhook.create-new-url'), {
                method: 'POST', headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.redirected) {
                // Redireciona para a nova URL gerada
                window.location.href = response.url;
            } else {
                const data = await response.json();
                alert("Erro ao criar nova URL: " + (data.error || "Erro desconhecido."));
                document.body.removeChild(overlay); // Remove a sobreposição em caso de erro
            }
        } catch (error) {
            console.error("Erro:", error);
            alert("Ocorreu um erro ao tentar criar a nova URL.");
            document.body.removeChild(overlay); // Remove a sobreposição em caso de erro
        }
    } else {
        console.log("A criação de uma nova URL foi cancelada pelo usuário.");
    }
}

function copyToClipboard() {
    const urlEle = document.getElementById("copyUrl");
    const urlText = urlEle.innerText;

    navigator.clipboard.writeText(urlText)
        .then(() => {
            // Salva o texto original do tooltip
            const originalTitle = urlEle.getAttribute('data-original-title') || 'Copiar';

            // Altera o texto do tooltip para "Copiado com sucesso"
            $(urlEle)
                .tooltip('hide')
                .attr('data-original-title', 'Copiado com sucesso')
                .tooltip('show');

            // Restaura o texto original do tooltip após 1.5 segundos
            setTimeout(() => {
                $(urlEle)
                    .tooltip('hide')
                    .attr('data-original-title', originalTitle);
            }, 1500); // Tempo de exibição do "Copiado com sucesso"
        })
        .catch(err => {
            console.error("Erro ao copiar URL: ", err);
        });
}

async function removeWebhook(id) {
    document.getElementById(`item-${id}`).remove();
    await fetch(route('webhook.delete', {id: id}), {method: 'DELETE'});
}

async function clearAllWebhooks() {
    if (confirm("Tem certeza de que deseja remover todas as requests?")) {
        await fetch(route('webhook.delete-all', {url_hash: urlHash}), {method: 'DELETE'});
        loadWebhooks();
    }
}

function showWebhookDetails(webhook, item) {
    const details = document.getElementById('webhookDetails');
    try {
        let queryParams = "";
        let body = "";
        let formData = "";
        let headers = "";

        // Verifica e parseia os campos principais do webhook
        const parseJSON = (data) => {
            try {
                return JSON.stringify(JSON.parse(data), null, 2);
            } catch (error) {
                console.warn("Dado não está em JSON válido:", data);
                return data || null;
            }
        };

        try {
            body = webhook.body ? JSON.stringify(JSON.parse(webhook.body), null, 2) : null;
        } catch (error) {
            console.warn("Corpo não está em JSON válido:", webhook.body);
            body = webhook.body || null; // Exibe o conteúdo bruto como fallback
        }

        formData = webhook.form_data && webhook.form_data.length ? JSON.stringify(webhook.form_data, null, 2) : null;

        queryParams = webhook.query_params ? JSON.stringify(webhook.query_params, null, 2) : null;
        headers = webhook.headers && webhook.headers.length ? JSON.stringify(webhook.headers, null, 2) : null;

        // Ordem de exibição dos dados
        const bodySection = `
            <div class='mt-3'>
                <strong>Body:</strong>
                <pre>${body}</pre>
            </div>`;
        const formDataSection = `
            <div class='mt-3'>
                <strong>Form Data:</strong>
                <pre>${formData}</pre>
            </div>`;
        const queryParamsSection = `
            <div class='mt-3'>
                <strong>Query Parameters:</strong>
                <pre>${queryParams}</pre>
            </div>`;
        const headersSection = `
            <div class='mt-3'>
                <strong>Headers:</strong>
                <pre>${headers}</pre>
            </div>`;

        // Monta os detalhes do webhook
        details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class='table-responsive'>
                    <table class="compact-table table table-borderless table-sm table-striped">
                        <tr><td><strong>Request Details</strong></td></tr>
                        <tr><td><span class="badge badge-${webhook.method.toLowerCase()}">${webhook.method}</span></td></tr>
                        ${webhook.host ? `<tr><td><strong>Host:</strong> ${webhook.host}</td></tr>` : ""}
                        ${webhook.timestamp ? `<tr><td><strong>Data:</strong> ${webhook.timestamp}</td></tr>` : ""}
                        ${webhook.size ? `<tr><td><strong>Tamanho:</strong> ${webhook.size} bytes</td></tr>` : ""}
                        ${webhook.hash ? `<tr><td><strong>Hash:</strong> ${webhook.hash}</td></tr>` : ""}
                        ${webhook.retransmitted !== undefined ? `<tr><td><strong>Retransmitido:</strong> ${webhook.retransmitted ? "Sim" : "Não"}</td></tr>` : ""}
                        ${webhook.viewed !== undefined ? `<tr><td><strong>Visualizado:</strong> ${webhook.viewed ? "Sim" : "Não"}</td></tr>` : ""}
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class='table-responsive'>
                    <table class="compact-table table table-borderless table-sm table-striped">
                        <tr><td><strong>Headers</strong></td></tr>
                        ${Object.entries(webhook.headers || {}).map(([header, value]) => `
                            <tr><td><strong>${header}:</strong> ${Array.isArray(value) ? value.join(', ') : value}</td></tr>
                        `).join('') || "<tr><td>Não recebido</td></tr>"}
                    </table>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                ${body && body !== '[]' && body !== undefined ? bodySection : ""}
                ${formData && formData !== '[]' && formData !== undefined ? formDataSection : ""}
                ${queryParams && queryParams !== '[]' && queryParams !== undefined ? queryParamsSection : ""}
            </div>
        </div>
    `;

        // Marca o item como ativo
        const activeItems = Array.from(document.getElementsByClassName('webhook-item active'));
        activeItems.forEach(activeItem => activeItem.classList.remove('active'));
        item.classList.add('active');
        markAsViewed(webhook);
    } catch (error) {
        console.error("Erro ao exibir detalhes do webhook:", error);
        details.innerHTML = "<p>Erro ao exibir detalhes do webhook.</p>";
    }
}

function addWebhookToTop(webhook) {
    const webhookList = document.getElementById('webhookList');
    const item = createWebhookItem(webhook);

    // Insere o novo item no topo da lista
    webhookList.prepend(item);

    // Apenas exibe o novo item se nenhum item já estiver ativo
    const activeItems = webhookList.getElementsByClassName('active');
    if (activeItems.length === 0) {
        setTimeout(() => showWebhookDetails(webhook, item), 0);
    }

    const waiting = document.querySelectorAll('.waiting-el');
    if (waiting.length) {
        waiting.forEach(el => el.remove());
    }
    updateResetButtonVisibility();
}

function updateResetButtonVisibility() {
    const resetButtonContainer = document.querySelector('#resetButtonContainer');
    const hasWebhooks = document.getElementById('webhookList').childElementCount > 0;
    resetButtonContainer.style.display = hasWebhooks ? "inline-block" : "none";
}

async function markAsViewed(webhook) {
    if (!webhook.viewed) {
        const response = await fetch(route('webhook.mark-viewed', {id: webhook.id}), {
            method: 'PATCH'
        });

        if (response.ok) {
            const item = document.getElementById(`item-${webhook.id}`);
            if (item) {
                item.classList.remove('unviewed'); // Remove a classe 'unviewed' do item
                webhook.viewed = true; // Atualiza o estado de 'viewed' para evitar chamadas adicionais
            }
        }
    }
}

function createWebhookItem(webhook) {
    const item = document.createElement('div');
    item.className = `webhook-item d-flex justify-content-center flex-column ${webhook.viewed ? '' : 'unviewed'}`;

    item.addEventListener('click', () => {
        showWebhookDetails(webhook, item);
    });
    item.id = 'item-' + webhook.id;

    item.innerHTML = `
        <small class="forwarded ${webhook.retransmitted ? 'show' : 'd-none'}">
            <img src="/forwarded.png" alt=">>"> Encaminhada
        </small>
        <div class='d-flex'>
            <div class="w-100">
                <div class="d-flex">
                    <div class="w-100 align-items-center d-flex justify-content-between">
                        <div class="div-badge">
                            <span class="badge badge-${webhook.method.toLowerCase()}">${webhook.method}</span>
                        </div>
                        <span class="mr-auto">#${webhook.hash.substring(0, 6)} ${webhook.host}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <small>${new Date(webhook.timestamp).toLocaleString()}</small>
                    ${!webhook.viewed ? '<span class="new"><i class="fa fa-circle"></i></span>' : ''}
                </div>
            </div>
            <div class="remove-container d-none justify-content-center align-items-center">
                ${retransmitUrls.length > 0 ? `
                <button class="btn btn-sm btn-info forward-btn" onclick="retransmitWebhook('${webhook.id}')">
                    <img src="/forwarded.png" alt=">>">
                </button>` : ''}
                <button class="btn btn-sm btn-danger remove-btn" onclick="removeWebhook('${webhook.id}')">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `;

    // Configura o botão de remover e retransmitir ao passar o mouse
    const removeContainer = item.querySelector('.remove-container');
    item.addEventListener('mouseover', () => {
        removeContainer.classList.remove('d-none');
        removeContainer.classList.add('d-flex');
    });
    item.addEventListener('mouseleave', () => {
        removeContainer.classList.remove('d-flex');
        removeContainer.classList.add('d-none');
    });

    return item;
}

async function loadWebhooks() {
    const webhooks = await fetchWebhooks();
    const webhookList = document.getElementById('webhookList');
    webhookList.innerHTML = '';

    // Renderiza os webhooks na lista
    for (const webhook of webhooks) {
        const item = await createWebhookItem(webhook);
        webhookList.appendChild(item);
    }

    // Exibe os detalhes do primeiro item apenas se não houver nenhum ativo
    const activeItems = webhookList.getElementsByClassName('active');
    if (activeItems.length === 0) {
        const firstItem = webhookList.firstChild;
        if (firstItem) {
            firstItem.click(); // Simula o clique para exibir os detalhes do primeiro item
        }
    }

    // Exibe mensagem caso não haja webhooks
    if (webhooks.length === 0) {
        const webhookList = document.getElementById('webhookList');
        const webhookDetails = document.getElementById('webhookDetails');

        webhookList.innerHTML = `
                <p class="waiting-el small text-muted d-flex align-items-center">
                    <img src="/waiting.svg" width="25" height="25" class="mr-2">
                    Aguardando a primeira request
                </p>
            `;

        webhookDetails.innerHTML = `
                <p class="waiting-el small text-muted d-flex align-items-center">
                    <img src="/waiting.svg" width="25" height="25" class="mr-2">
                    Aguardando a primeira request
                </p>
            `;

        return;
    }

    updateResetButtonVisibility();
}

async function fetchWebhooks() {
    const response = await fetch(route('webhook.load', {url_hash: urlHash}));
    return await response.json();
}

function updateNotificationButton(isEnabled) {
    if(urlId === "" || urlId === undefined){
        return;
    }

    const button = document.getElementById("toggleNotifications");
    button.innerHTML = isEnabled ? "<i class='fa fa-bell-slash-o'></i>" : "<i class='fa fa-bell-o'></i>";

    button.setAttribute('data-toggle', 'tooltip');
    button.setAttribute('data-placement', 'left');
    button.setAttribute('title', isEnabled ? 'Desativar Notificações' : 'Ativar Notificações');
}

function showNotification(data) {
    const options = {
        body: `Método: ${data.method}\nHost: ${data.host}`, icon: "/apple-touch-icon.png", tag: data.id
    };

    new Notification("Novo Webhook Recebido", options);
}

function toggleNotifications(event) {
    if (event) event.stopPropagation(); // Previne o fechamento do dropdown

    const notificationsEnabled = localStorage.getItem("notificationsEnabled") === "true";

    if (!notificationsEnabled) {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                localStorage.setItem("notificationsEnabled", "true");
                alert("Notificações ativadas!");
            } else {
                alert("Permissão para notificações negada.");
            }
        });
    } else {
        localStorage.setItem("notificationsEnabled", "false");
        alert("Notificações desativadas.");
    }
}

// Validação dinâmica dos formulários
function validateForm(formId, submitButtonId) {
    const form = document.getElementById(formId);
    const button = document.getElementById(submitButtonId);

    form.addEventListener('input', () => {
        // Filtra apenas os campos de entrada do formulário
        const fields = Array.from(form.elements).filter(input =>
            input.tagName === 'INPUT' || input.tagName === 'TEXTAREA'
        );

        // Verifica se todos os campos estão preenchidos
        const allFieldsFilled = fields.every(input => input.value.trim() !== '');

        // Ativa ou desativa o botão
        button.disabled = !allFieldsFilled;
    });
}

async function loginAccount() {
    const button = document.getElementById('loginSubmit');
    button.disabled = true;
    button.innerHTML = 'Processando...';

    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value.trim();

    try {
        const response = await fetch(route('login'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password }),
        });

        const data = await response.json();

        if (response.ok) {
            updateAccountButton(data.user); // Atualiza o botão dinamicamente
            $('#accountModal').modal('hide'); // Fecha o modal
        } else {
            alert(data.error || 'Erro ao fazer login.');
        }
    } catch (error) {
        console.error('Erro ao fazer login:', error);
        alert('Erro ao processar o login.');
    } finally {
        button.disabled = false;
        button.innerHTML = 'Entrar';
    }
}

async function registerAccount() {
    const button = document.getElementById('registerSubmit');
    button.disabled = true;
    button.innerHTML = 'Processando...';

    const name = document.getElementById('registerName').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value.trim();
    const passwordConfirm = document.getElementById('registerPasswordConfirm').value.trim();

    clearErrors(); // Limpa os erros anteriores

    if (password !== passwordConfirm) {
        showError('registerPasswordConfirm', 'As senhas não coincidem!');
        button.disabled = false;
        button.innerHTML = 'Registrar';
        return;
    }

    try {
        const response = await fetch(route('register'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name,
                email,
                password,
                password_confirmation: passwordConfirm
            }),
        });

        const data = await response.json();

        if (response.ok) {
            $('#accountModal').modal('hide'); // Fecha o modal
            window.location.href = data.redirect; // Redireciona para a URL retornada
        } else if (data.errors) {
            // Exibe erros nos campos
            Object.keys(data.errors).forEach(field => {
                showError(`register${capitalize(field)}`, data.errors[field][0]);
            });
        } else {
            alert(data.error || 'Erro ao registrar conta.');
        }
    } catch (error) {
        console.error('Erro ao registrar conta:', error);
        alert('Erro ao processar o registro.');
    } finally {
        button.disabled = false;
        button.innerHTML = 'Registrar';
    }
}


// Helper para exibir mensagens de erro
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.innerText = message;
        field.parentNode.appendChild(errorDiv);
    }
}

// Helper para limpar mensagens de erro
function clearErrors() {
    document.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(errorDiv => errorDiv.remove());
}

// Helper para capitalizar a primeira letra (para mapear campos do Laravel para IDs do formulário)
function capitalize(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function updateAccountButton(user) {
    const accountButton = document.getElementById('accountButton');
    accountButton.outerHTML = `
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                ${user.name}
            </button>
            <div class="dropdown-menu" aria-labelledby="accountDropdown">
                <a class="dropdown-item" href="#">Perfil</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" onclick="logoutAccount()">Sair</a>
            </div>
        </div>
    `;
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

