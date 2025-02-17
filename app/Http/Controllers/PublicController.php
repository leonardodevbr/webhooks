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
        DB::beginTransaction();
        try {
            $ip = request()->ip();

            $url = Url::firstOrCreate(
                ['ip_address' => $ip],
                [
                    'slug' => Helper::generateShortHash(),
                    'expires_at' => now()->addDays(7),
                ]
            );

            if (!empty($url['account_id'])) {
                if (!Auth::check() || Auth::id() != $url['account_id']) {
                    return redirect()->route('auth.login')
                        ->with('info', 'Acesso não autorizado. Faça o login para continuar.');
                }
            }

            DB::commit();

            return $this->view($url->slug);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao exibir webhooks: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function view(string $urlSlug)
    {
        try {
            $url = Url::where('slug', $urlSlug)->firstOrFail();

            if (!empty($url['expires_at']) && $url['expires_at'] < now()) {
                return view('public.view', compact('url'))
                    ->with('warning', 'URL expirada. Solicite uma nova URL.');
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

            $webhook = WebhookRequest::create($requestData);
            if ($webhook) {
                // Dispara o evento para o Pusher
                $this->triggerPusherEvent(['id' => $webhook->id], 'new-webhook');
                $this->webPush->sendNotification($url, $webhook);

                // Verifica se existem URLs de retransmissão e processa
                $retransmissionUrls = $url->webhook_retransmission_urls()->get();
                if ($retransmissionUrls->isNotEmpty()) {
                    $this->retransmitWebhook($webhook->id);
                }

                return response()->json([
                    'message' => 'Webhook recebido.',
                    'data' => ['webhook_hash' => $webhook->hash]
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

    private function extractRequestData(Request $request, int $urlId)
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
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $formData = $request->except(array_keys($queryParams));
        }

        // Obtém o conteúdo bruto do corpo da requisição
        $body = $request->getContent();
        $decodedBody = null;

        if (strpos($contentType, 'application/json') !== false) {
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
    public function load(string $urlHash)
    {
        try {
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                return response()->json(['error' => 'URL inválida.'], 404);
            }

            $webhooks = $url->webhooks()->orderBy('created_at', 'desc')->get();

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
            $webhook = Webhook::where('id', $id)->first();

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

    public function deleteAll(string $urlHash)
    {
        try {
            // Verifica se o hash existe na tabela URLs
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                return response()->json(['error' => 'Hash de URL inválido'], 404);
            }

            // Remove todos os webhooks associados ao url_id
            $url->webhooks()->delete();

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
            $webhook = Webhook::where('id', $id)->first();

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
            $webhook = Webhook::where('id', $id)->first();
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
            $webhook = Webhook::where('id', $id)->first();
            return response()->json($webhook);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar webhook.'], 500);
        }
    }

    public function createNewUrl()
    {
        try {
            $hash = Str::uuid();
            $ip = request()->ip();

            $newUrl = Url::create([
                'hash' => $hash->toString(),
                'ip_address' => $ip
            ]);

            // Redireciona para a nova URL com uma mensagem de sucesso
            return redirect()->route('webhook.view', [$newUrl->hash])
                ->with('success', 'Nova URL criada com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao criar nova URL: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function retransmitWebhook(int $webhookId)
    {
        try {
            $webhook = Webhook::where('id', $webhookId)->first();

            if (!$webhook) {
                return response()->json(['error' => 'Nenhum webhook encontrado para retransmissão.'], 404);
            }

            $url = $webhook->url;
            if (!$url) {
                return response()->json(['error' => 'Webhook não está associado a nenhuma URL.'], 404);
            }

            // Se o usuário está autenticado, validar se o webhook pertence à conta do usuário
            if (auth()->check() && $url->account_id !== auth()->id()) {
                return response()->json(['error' => 'Acesso negado.'], 403);
            }

            $retransmissionUrls = $url->webhook_retransmission_urls()->get();
            if ($retransmissionUrls->isEmpty()) {
                return response()->json(['error' => 'Webhook não possui nenhuma URL de retransmissão cadastrada.'], 404);
            }

            foreach ($retransmissionUrls as $retransmissionUrl) {
                if (!$retransmissionUrl->is_online) {
                    $this->triggerPusherEvent(['id' => $webhookId, 'url' => $retransmissionUrl->url], 'local-retransmission');
                } else {
                    $queryParams = is_string($webhook->query_params)
                        ? json_decode($webhook->query_params, true)
                        : ($webhook->query_params ?? []);

                    $queryString = http_build_query($queryParams);
                    $fullUrl = $queryString ? "{$retransmissionUrl->url}?{$queryString}" : $retransmissionUrl->url;

                    $headers = is_string($webhook->headers)
                        ? json_decode($webhook->headers, true)
                        : ($webhook->headers ?? []);

                    // 🔹 Lista de headers que podem ser mantidos
                    $allowedHeaders = [
                        'content-length', 'accept-encoding', 'accept',
                        'user-agent', 'content-type', 'authorization'
                    ];

                    // 🔥 Filtra os headers antes de enviar a requisição
                    $filteredHeaders = [];
                    foreach ($headers as $key => $value) {
                        if (in_array(strtolower($key), $allowedHeaders)) {
                            $filteredHeaders[$key] = $value;
                        }
                    }

                    Log::info("🔍 Enviando requisição HTTP:", [
                        'method' => $webhook->method,
                        'url' => $fullUrl,
                        'headers' => $filteredHeaders,
                        'body' => $webhook->body,
                    ]);

                    try {
                        $response = Http::withHeaders($filteredHeaders)
                            ->send($webhook->method, $fullUrl, [
                                'body' => $webhook->body,
                            ]);

                        Log::info("📡 Resposta da API:", [
                            'status' => $response->status(),
                            'headers' => $response->headers(),
                            'body' => $response->body(),
                            'json' => $response->json(),
                            'reason' => $response->reason(),
                        ]);

                        if ($response->serverError()) {
                            Log::warning("⚠️ Webhook retransmitido, mas o servidor de destino retornou erro 500.", [
                                'url' => $fullUrl,
                                'response_status' => $response->status(),
                                'response_body' => $response->body(),
                            ]);
                        } elseif ($response->failed()) {
                            Log::error("❌ Falha na retransmissão para {$fullUrl}");
                        } else {
                            Log::info("✅ Webhook retransmitido com sucesso para {$fullUrl}");
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ Erro crítico ao retransmitir webhook para {$fullUrl}: " . $e->getMessage(), [
                            'exception' => $e,
                        ]);
                    }
                }
            }

            $this->markRetransmitted($webhookId);

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
