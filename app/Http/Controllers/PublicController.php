<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Url;
use App\Models\WebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pusher\Pusher;

class PublicController extends Controller
{
    protected WebPushController $webPush;

    public function __construct(WebPushController $webPush)
    {
        $this->webPush = $webPush;
    }

    public function index()
    {
        $ip = request()->ip();
        $urls = Url::where('ip_address', $ip)->get();
        return view('public.index', compact('urls'));
    }

    public function view(string $urlSlug)
    {
        try {
            $url = Url::where('slug', $urlSlug)->firstOrFail();

            if (!empty($url['account_id'])) {
                if (!Auth::check() || Auth::id() != $url['account_id']) {
                    return redirect()->route('auth.login')
                        ->with('info', 'Acesso não autorizado. Faça o login para continuar.');
                }
            }

            if (!empty($url['expires_at']) && $url['expires_at'] < now()) {
                return redirect()->route('public.index')
                    ->with('info', 'URL expirada. Solicite uma nova URL.');
            }

            $webhookRequests = $url->webhook_requests()->orderBy('created_at', 'desc')->get();

            return view('public.view', compact('url', 'webhookRequests'));

        } catch (\Exception $e) {
            Log::error('Erro ao visualizar URL ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function listener(Request $request, string $urlSlug)
    {
        $url = Url::where('slug', $urlSlug)->first();

        if (!$url) {
            return response()->json(['status' => 'error', 'message' => 'URL não encontrada.'], 404);
        }

        return $this->processWebhook($request, $url);
    }

    private function processWebhook(Request $request, Url $url)
    {
        try {
            $requestData = $this->extractRequestData($request, $url->id);
            $webhookRequest = WebhookRequest::create($requestData);

            if ($webhookRequest) {
                // Dispara o evento para o Pusher
                $this->triggerPusherEvent(['id' => $webhookRequest->id], 'new-webhook-request');
                $this->webPush->sendNotification($url, $webhookRequest);

                // Verifica se existem URLs de retransmissão e processa
                $retransmissionUrls = $url->webhook_retransmission_urls()->get();
                if ($retransmissionUrls->isNotEmpty()) {
                    $this->retransmitWebhook($webhookRequest->id);
                }

                return response()->json([
                    'message' => 'Webhook recebido.',
                    'data' => ['webhook_hash' => $webhookRequest->hash]
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Erro ao registrar webhook'], 500);
        } catch (\Exception $e) {
            Log::error('Erro no listener de webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Erro interno no servidor'], 500);
        }
    }

    private function extractRequestData(Request $request, int $urlId): array
    {
        $headers = $request->headers->all();
        $queryParams = $request->query(); // Obtém apenas os parâmetros da URL (query string)
        $contentType = $request->header('Content-Type');
        $formData = [];

        Log::info("📥 Recebendo requisição:", [
            'method' => $request->method(),
            'content_type' => $contentType,
            'query_params' => $queryParams
        ]);

        // Se for multipart/form-data, captura os dados corretamente
        if (str_contains($contentType, 'multipart/form-data')) {
            $formData = $request->except(array_keys($queryParams));
        }

        // Obtém o conteúdo bruto do corpo da requisição
        $body = $request->getContent();
        $decodedBody = null;

        if (str_contains($contentType, 'application/json')) {
            $decodedBody = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("🚨 JSON inválido detectado!", [
                    'raw_body' => $body,
                    'error' => json_last_error_msg()
                ]);

                // Salva o JSON inválido no formato solicitado
                $body = ['invalid_body' => $body];
            } else {
                $body = is_array($decodedBody) ? $decodedBody : [];
            }
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $body = $request->all();
        } else {
            $body = $body ?: [];
        }

        // Monta os cabeçalhos no formato string para calcular o tamanho
        $headersString = '';
        foreach ($headers as $key => $value) {
            $headersString .= $key . ': ' . implode(', ', (array)$value) . "\r\n";
        }
        $headersSize = strlen($headersString);

        // Calcula o tamanho da query string
        $queryString = http_build_query($queryParams);
        $querySize = strlen($queryString);

        // Calcula o tamanho total da requisição
        $totalSize = $headersSize + $querySize + strlen($request->getContent());

        // Log final para debug
        Log::info("📦 Dados extraídos da requisição:", [
            'method' => $request->method(),
            'size' => $totalSize,
            'processed_body' => $body
        ]);

        // Monta e retorna os dados da requisição
        return [
            'timestamp' => now(),
            'method' => $request->method(),
            'headers' => $headers,
            'query_params' => $queryParams,
            'body' => json_encode($body, JSON_UNESCAPED_SLASHES),
            'form_data' => $formData,
            'host' => $request->ip(),
            'size' => $totalSize,
            'hash' => Str::uuid(),
            'url_id' => $urlId,
            'retransmitted' => false,
            'raw_request' => $request->getContent()
        ];
    }

    private function triggerPusherEvent($data, $eventName)
    {
        $config = config('services.pusher');
        $pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            ['cluster' => $config['cluster']]
        );

        $pusher->trigger($config['channel'], $eventName, $data);
    }

    /**
     * Carrega os webhooks de uma URL pública.
     */
    public function load(string $urlSlug)
    {
        try {
            $url = Url::where('slug', $urlSlug)->first();

            if (!$url) {
                return response()->json(['error' => 'URL inválida.'], 404);
            }

            $webhooks = $url->webhook_requests()->orderBy('created_at', 'desc')->get();

            return response()->json($webhooks);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar webhooks: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    private function parseFormData($body)
    {
        $data = [];
        // Divide o form-data e extrai os campos
        preg_match_all('/Content-Disposition: form-data; name="(.*?)"\s+([^\r\n]*)/s', $body, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $data[$match[1]] = $match[2];
        }
        return $data;
    }

    public function delete(int $id)
    {
        try {
            // Verifica se o UUID do webhook foi fornecido
            if (!$id) {
                return response()->json(['error' => 'ID do webhook não especificado'], 400);
            }

            // Busca o webhook pelo UUID e tenta deletá-lo
            $webhook = WebhookRequest::where('id', $id)->first();

            if (!$webhook) {
                return response()->json(['error' => 'Webhook não encontrado'], 404);
            }

            $webhook->delete();

            return response()->json(['status' => 'Webhook deletado com sucesso']);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação'], 500);
        }
    }

    public function deleteAll(string $urlSlug)
    {
        try {
            // Verifica se o hash existe na tabela URLs
            $url = Url::where('slug', $urlSlug)->first();

            if (!$url) {
                return response()->json(['error' => 'Hash de URL inválido'], 404);
            }

            // Remove todos os webhooks associados ao url_id
            $url->webhook_requests()->delete();

            return response()->json(['status' => 'Todos os webhooks associados foram deletados com sucesso']);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar todos os webhooks: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação'], 500);
        }
    }

    public function markRetransmitted(int $id)
    {
        try {
            // Encontra o webhook pelo ID
            $webhook = WebhookRequest::where('id', $id)->first();

            // Atualiza o status de 'retransmitted' para true
            $webhook->update(['retransmitted' => true]);
            $this->triggerPusherEvent(['id' => $webhook->id], 'webhook-retransmitted');

            return response()->json(['status' => 'success', 'message' => 'Webhook marcado como retransmitido']);
        } catch (\Exception $e) {
            Log::error('Erro ao marcar webhook como retransmitido: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erro ao processar a solicitação'], 500);
        }
    }

    public function markAsViewed(int $id)
    {
        try {
            $webhook = WebhookRequest::where('id', $id)->first();
            $webhook->viewed = true;
            $webhook->save();

            return response()->json(['message' => 'Webhook marcado como visualizado.']);
        } catch (\Exception $e) {
            Log::error('Erro ao marcar webhook como visualizado: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao marcar como visualizado.'], 500);
        }
    }

    public function loadSingle(int $id)
    {
        try {
            $webhook = WebhookRequest::where('id', $id)->first();
            return response()->json($webhook);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar webhook.'], 500);
        }
    }

    public function createNewUrl()
    {
        try {
            $ip = request()->ip();
            $newUrl = Url::create([
                    'ip_address' => $ip,
                    'slug' => Helper::generateShortHash(),
                    'expires_at' => now()->addDays(7),
                ]);

            // Redireciona para a nova URL com uma mensagem de sucesso
            return redirect()->route('public.view', [$newUrl->slug])
                ->with('success', 'Nova URL criada com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao criar nova URL: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function retransmitWebhook(int $webhookRequestId)
    {
        try {
            $webhookRequest = WebhookRequest::where('id', $webhookRequestId)->first();

            if (!$webhookRequest) {
                return response()->json(['error' => 'Nenhum webhook encontrado para retransmissão.'], 404);
            }

            $url = $webhookRequest->url;
            if (!$url) {
                return response()->json(['error' => 'Webhook não está associado a nenhuma URL.'], 404);
            }

            if (auth()->check() && is_object($url) && $url->account_id !== auth()->id()) {
                return response()->json(['error' => 'Acesso negado.'], 403);
            }

            $retransmissionUrls = $url->webhook_retransmission_urls()->get();
            if ($retransmissionUrls->isEmpty()) {
                return response()->json(['error' => 'Webhook não possui nenhuma URL de retransmissão cadastrada.'], 404);
            }

            foreach ($retransmissionUrls as $retransmissionUrl) {
                $queryParams = is_string($webhookRequest->query_params)
                    ? json_decode($webhookRequest->query_params, true)
                    : (is_array($webhookRequest->query_params) ? $webhookRequest->query_params : []);

                $queryString = http_build_query($queryParams);
                $fullUrl = $queryString ? "{$retransmissionUrl->url}?{$queryString}" : $retransmissionUrl->url;

                $headers = is_string($webhookRequest->headers)
                    ? json_decode($webhookRequest->headers, true)
                    : (is_array($webhookRequest->headers) ? $webhookRequest->headers : []);

                $allowedHeaders = ['content-length', 'accept-encoding', 'accept', 'user-agent', 'content-type', 'authorization'];
                $filteredHeaders = array_intersect_key($headers, array_flip($allowedHeaders));

                if (!isset($filteredHeaders['Content-Type'])) {
                    $filteredHeaders['Content-Type'] = 'application/json';
                }
                if (!isset($filteredHeaders['Origin'])) {
                    $filteredHeaders['Origin'] = url('/');
                }
                if (!isset($filteredHeaders['User-Agent'])) {
                    $filteredHeaders['User-Agent'] = 'Webhook.Now/1.0';
                }
                if (!isset($filteredHeaders['Accept'])) {
                    $filteredHeaders['Accept'] = '*/*';
                }

                Log::info("🔍 Enviando requisição HTTP:", [
                    'method' => $webhookRequest->method,
                    'url' => $fullUrl,
                    'headers' => $filteredHeaders,
                    'body' => $webhookRequest->body,
                ]);

                try {
                    $options = [];

                    if (!empty($webhookRequest->body)) {
                        if (str_contains($headers['content-type'], 'application/json')) {
                            $options['json'] = json_decode($webhookRequest->body, true);
                        } elseif (str_contains($headers['content-type'], 'application/x-www-form-urlencoded')) {
                            parse_str($webhookRequest->body, $formParams);
                            $options['form_params'] = $formParams;
                        } else {
                            $options['body'] = $webhookRequest->body; // Mantém o body intocado para XML, binários etc.
                        }
                    }

                    if (!isset($filteredHeaders['Content-Length']) && isset($options['body'])) {
                        $filteredHeaders['Content-Length'] = strlen($options['body']);
                    }

                    $response = Http::withOptions(['verify' => false])
                        ->withHeaders($filteredHeaders)
                        ->send($webhookRequest->method, $fullUrl, $options);

                    Log::info("📡 Resposta da API:", [
                        'status' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                        'json' => $response->json(),
                        'reason' => $response->reason(),
                    ]);

                   if ($response->failed()) {
                        Log::error("❌ Falha na retransmissão para {$fullUrl}", [
                            'status' => $response->status(),
                             'body' => $response->body(),
                        ]);
                    } else {
                        Log::info("✅ Webhook retransmitido com sucesso para {$fullUrl}");
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Erro crítico ao retransmitir webhook para {$fullUrl}: " . $e->getMessage(), [
                        'exception' => $e,
                    ]);
                }
            }

            $this->markRetransmitted($webhookRequestId);
            return response()->json(['success' => 'Retransmissão concluída.']);
        } catch (\Exception $e) {
            Log::error("❌ Erro na requisição:", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function listUrls()
    {
        $urls = Auth::user()->urls()->get();
        return view('account.list-urls', compact('urls'));
    }

    public function updateSlug(Request $request, int $id)
    {
        try {
            $url = Auth::user()->urls()->find($id);
            $url->slug = $request->input('slug') ?: null;
            $url->save();

            return response()->json(['success' => true, 'slug' => $url->slug]);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar slug: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar slug.'], 500);
        }
    }

    public function getNotificationStatus(int $id)
    {
        try {
            $url = Url::findOrFail($id);
            return response()->json([
                'notifications_enabled' => $url->notifications_enabled
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar status de notificações: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar status de notificações.'], 500);
        }
    }

    public function toggleNotifications(int $id)
    {
        $url = Url::where('id', $id)->where('account_id', auth()->id())->first();

        if (!$url) {
            return response()->json(['error' => 'URL não encontrada'], 404);
        }

        $url->notifications_enabled = !$url->notifications_enabled;
        $url->save();

        return response()->json([
            'success' => true,
            'notifications_enabled' => $url->notifications_enabled
        ]);
    }

}
