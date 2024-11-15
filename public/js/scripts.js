document.addEventListener("DOMContentLoaded", () => {
    const pusherKey = window.env.PUSHER_KEY;
    const pusherCluster = window.env.PUSHER_CLUSTER;
    const pusherChannel = window.env.PUSHER_CHANNEL;

    const notificationsEnabled = localStorage.getItem("notificationsEnabled") === "true";
    const showInitialNotification = localStorage.getItem("showInitialNotification") === "true";
    const hasSeenModal = localStorage.getItem("hasSeenFeatureModal") === "true";

    // Configuração do Modal para ser exibido apenas uma vez
    if (!hasSeenModal) {
        $('#featureModal').modal({
            backdrop: 'static',
            keyboard: false
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
        cluster: pusherCluster,
        forceTLS: true
    });
    const channel = pusher.subscribe(pusherChannel);

    channel.bind('new-webhook', function (data) {
        addWebhookToTop(data);

        if (notificationsEnabled && Notification.permission === "granted") {
            showNotification(data);
        }
    });

    loadRetransmitUrls();
    loadWebhooks();

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
});

function toggleNotifications() {
    const notificationsEnabled = localStorage.getItem("notificationsEnabled") === "true";

    if (!notificationsEnabled) {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                localStorage.setItem("notificationsEnabled", "true");
                localStorage.setItem("showInitialNotification", "true");
                updateNotificationButton(true);
                location.reload();
            } else {
                alert("Permissão para notificações negada. Clique em Ok para recarregar a página.");
                location.reload();
            }
        });
    } else {
        localStorage.setItem("notificationsEnabled", "false");
        updateNotificationButton(false);
        alert("Notificações desativadas. Clique em Ok para recarregar a página.");
        location.reload();
    }
}

function updateNotificationButton(isEnabled) {
    const button = document.getElementById("toggleNotifications");
    button.innerHTML = isEnabled
        ? "<i class='fa fa-bell-slash-o'></i>"
        : "<i class='fa fa-bell-o'></i>";

    button.setAttribute('data-toggle', 'tooltip');
    button.setAttribute('data-placement', 'left');
    button.setAttribute('title', isEnabled ? 'Desativar Notificações' : 'Ativar Notificações');
}

function showNotification(data) {
    const options = {
        body: `Método: ${data.method}\nHost: ${data.host}`,
        icon: "/apple-touch-icon.png",
        tag: data.id
    };

    new Notification("Novo Webhook Recebido", options);
}

let retransmitUrls = [];

function loadRetransmitUrls() {
    const urls = document.cookie.split('; ').find(row => row.startsWith('retransmitUrls='));
    retransmitUrls = urls ? JSON.parse(decodeURIComponent(urls.split('=')[1])) : [];
    displayRetransmitUrls();
}

function saveRetransmitUrls() {
    document.cookie = `retransmitUrls=${encodeURIComponent(JSON.stringify(retransmitUrls))}; path=/`;
}

function addRetransmitUrl() {
    const url = document.getElementById('retransmitUrlInput').value.trim();
    if (url && !retransmitUrls.includes(url)) {
        retransmitUrls.push(url);
        saveRetransmitUrls();
        displayRetransmitUrls();
        document.getElementById('retransmitUrlInput').value = '';
    }
    loadWebhooks();
}

function removeRetransmitUrl(url) {
    retransmitUrls = retransmitUrls.filter(u => u !== url);
    saveRetransmitUrls();
    displayRetransmitUrls();
    loadWebhooks();
}

function displayRetransmitUrls() {
    const urlList = document.getElementById('urlList');
    urlList.innerHTML = '';
    retransmitUrls.forEach(url => {
        const urlItem = document.createElement('div');
        urlItem.innerHTML = `<div class="align-items-center d-flex mb-2 justify-content-between">
                                        <span class="small">${url}</span>
                                        <div>
                                            <button onclick="removeRetransmitUrl('${url}')" class="btn btn-danger btn-sm">
                                                <i class="fa fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>`;
        urlList.appendChild(urlItem);
    });
}

async function fetchWebhooks() {
    const response = await fetch(route('webhook.load', {url_hash: window.urlHash}));
    return await response.json();
}

async function loadWebhooks() {
    const webhooks = await fetchWebhooks();
    const webhookList = document.getElementById('webhookList');
    webhookList.innerHTML = '';

    // Renderiza os webhooks na lista
    webhooks.forEach((webhook) => {
        const item = createWebhookItem(webhook);
        webhookList.appendChild(item);
    });

    updateResetButtonVisibility();

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
                        <span class="mr-auto">#${webhook.id.substring(0, 6)} ${webhook.host}</span>
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


function addWebhookToTop(webhook) {
    if (!webhook.retransmitted) {
        retransmitWebhook(webhook.id);
    }
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

function showWebhookDetails(webhook, item) {
    const details = document.getElementById('webhookDetails');
    try {
        let queryParams = "";
        let body = "";
        let formData = "";

        // Verifica e parseia os parâmetros de consulta, body e form_data
        try {
            queryParams = webhook.query_params ? JSON.stringify(JSON.parse(webhook.query_params), null, 2) : null;
        } catch (error) {
            console.warn("Query params não estão em JSON válido:", webhook.query_params);
            queryParams = webhook.query_params || null;
        }

        try {
            body = webhook.body ? JSON.stringify(JSON.parse(webhook.body), null, 2) : null;
        } catch (error) {
            console.warn("Corpo não está em JSON válido:", webhook.body);
            body = webhook.body || null;
        }

        try {
            formData = webhook.form_data ? JSON.stringify(JSON.parse(webhook.form_data), null, 2) : null;
        } catch (error) {
            console.warn("Form data não está em JSON válido:", webhook.form_data);
            formData = webhook.form_data || null;
        }

        // Ordem de exibição: body primeiro, seguido por form_data e query_params
        const bodySection = body ? `<div class='mt-3'><strong>Body:</strong><pre>${body}</pre></div>` : "";
        const formDataSection = formData ? `<div class='mt-3'><strong>Form Data:</strong><pre>${formData}</pre></div>` : "";
        const queryParamsSection = queryParams ? `<div class='mt-3'><strong>Query Parameters:</strong><pre>${queryParams}</pre></div>` : "";

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
                        ${webhook.id ? `<tr><td><strong>ID:</strong> ${webhook.id}</td></tr>` : ""}
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class='table-responsive'>
                    <table class="compact-table table table-borderless table-sm table-striped">
                        <tr><td><strong>Headers</strong></td></tr>
                        ${webhook.headers ?
            Object.entries(JSON.parse(webhook.headers)).map(([header, value]) => `
                                <tr><td><strong>${header}:</strong> ${value}</td></tr>
                            `).join('')
            : "<tr><td>Não recebido</td></tr>"}
                    </table>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                ${bodySection || ""}
                ${formDataSection || ""}
                ${queryParamsSection || ""}
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


async function removeWebhook(uuid) {
    document.getElementById(`item-${uuid}`).remove();
    await fetch(route('webhook.delete', {uuid: uuid}), {method: 'DELETE'});
}

async function clearAllWebhooks() {
    if (confirm("Tem certeza de que deseja remover todas as requests?")) {
        await fetch(route('webhook.delete-all', {url_hash: urlHash}), {method: 'DELETE'});
        loadWebhooks();
    }
}

async function retransmitWebhook(uuid) {
    try {
        // Busca os dados do webhook a partir da request 'load-single'
        const response = await fetch(route('webhook.load-single', {id: uuid}), {method: 'GET'});

        if (!response.ok) {
            throw new Error("Erro ao carregar o webhook.");
        }

        const webhook = await response.json(); // Parseia a resposta JSON com os dados do webhook

        let retransmitted = false;

        for (const url of retransmitUrls) {
            const queryParams = new URLSearchParams(JSON.parse(webhook.query_params)).toString();
            const fullUrl = queryParams ? `${url}?${queryParams}` : url;

            const xhr = new XMLHttpRequest();
            xhr.open(webhook.method, fullUrl);

            if (['POST', 'PUT', 'PATCH'].includes(webhook.method)) {
                let formData;

                // Se o corpo é um JSON, converte para form-data
                try {
                    const jsonBody = JSON.parse(webhook.body);
                    formData = new FormData();

                    for (const key in jsonBody) {
                        if (jsonBody.hasOwnProperty(key)) {
                            formData.append(key, jsonBody[key]);
                        }
                    }

                    xhr.send(formData); // Envia como form-data

                } catch (error) {
                    // Se o corpo não é JSON válido, envia como texto simples
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.send(webhook.body);
                }

            } else {
                xhr.send(); // Para requisições GET, DELETE, etc.
            }

            retransmitted = true;
        }

        // Marca como retransmitido após sucesso
        if (retransmitted) {
            await markRetransmitted(webhook.id);
        }
    } catch (error) {
        console.error("Erro:", error);
    }
}


async function markRetransmitted(id) {
    if (retransmitUrls.length > 0) {
        try {
            const response = await fetch(route('webhook.mark-retransmitted', {id: id}), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
            });

            if (!response.ok) {
                throw new Error("Erro ao marcar o webhook como retransmitido.");
            }

            const result = await response.json();
            console.log(result.message); // Exibe a mensagem de sucesso

            // Atualiza o item no DOM para exibir o rótulo de "Encaminhada"
            const item = document.getElementById(`item-${id}`);
            if (item) {
                const forwardedLabel = item.querySelector('.forwarded');
                if (forwardedLabel) {
                    forwardedLabel.classList.remove('d-none'); // Remove a classe d-none para exibir o rótulo
                }
            }

        } catch (error) {
            console.error("Erro:", error);
        }
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
            const response = await fetch(route('url.create-new-url'), {
                method: 'POST',
                headers: {
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
