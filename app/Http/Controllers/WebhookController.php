<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pusher\Pusher;

class WebhookController extends Controller
{
    public function createUrl()
    {
        try {
            $ip = request()->ip();

            $url = Url::firstOrCreate(
                ['ip_address' => $ip],
                ['hash' => Str::uuid()]
            );

            return redirect()->route('webhook.view', $url->hash)
                ->with('info', 'URL de monitoramento criada.');
        } catch (\Exception $e) {
            Log::error('Erro ao criar URL: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    /**
     * Exibe os webhooks associados a uma URL vinculada a uma conta.
     */
    public function view(string $urlHash)
    {
        try {
            $url = Url::where('hash', $urlHash)
                ->first();

            if (!$url) {
                return redirect()->route('webhook.create')
                    ->with('info', 'A URL solicitada não existe. Criamos uma nova URL para você.');
            }

            $webhooks = $url->webhooks()->orderBy('created_at', 'desc')->get();

            return view('webhook.view', [
                'webhooks' => $webhooks,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao exibir webhooks: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function listener(Request $request, string $urlHash)
    {
        try {
            $url = Url::where('hash', $urlHash)->first();
            if (!$url) {
                return response()->json(['status' => 'error', 'message' => 'Hash de URL inválido'], 404);
            }

            $requestData = $this->extractRequestData($request, $url->id);

            $webhook = Webhook::create($requestData);
            if ($webhook) {
                // Dispara o evento para o Pusher
                $this->triggerPusherEvent(['id' => $webhook->id], 'new-webhook');

                $retransmissionUrls = $url->webhook_retransmission_urls()->get();
                if ($retransmissionUrls->count() > 0) {
                    $this->retransmitWebhook($webhook->id);
                }

                return response()->json(['message' => 'Webhook recebido.', 'data' => ['webhook_hash' => $webhook->hash]]);
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

        // Verifica o Content-Type da requisição
        $contentType = $request->header('Content-Type');

        // Inicializa `form_data` como vazio
        $formData = [];
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $formData = $request->except(array_keys($queryParams)); // Exclui query_params do form_data
        }

        // Verifica se o body é JSON válido
        $body = $request->getContent();
        if (strpos($contentType, 'application/json') !== false) {
            $decodedBody = json_decode($body, true);
            $body = is_array($decodedBody) ? $decodedBody : []; // Se o JSON for inválido, retorna array vazio
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            // Para multipart/form-data, o Laravel já popula $request->all()
            $body = $request->all();
        } else {
            // Para outros tipos de conteúdo, mantém o conteúdo bruto
            $body = $body ?: []; // Garante que seja vazio se $body for nulo ou vazio
        }

        // Calcula o tamanho dos cabeçalhos
        $headersString = '';
        foreach ($headers as $key => $value) {
            $headersString .= $key.': '.implode(', ', (array)$value)."\r\n";
        }

        $headersSize = strlen($headersString); // Tamanho dos cabeçalhos

        // Calcula o tamanho dos parâmetros da query string
        $queryString = http_build_query($queryParams); // Converte os parâmetros para uma query string
        $querySize = strlen($queryString); // Tamanho da query string

        // Calcula o tamanho total
        $totalSize = $headersSize + $querySize + strlen($request->getContent());

        // Monta o array final
        return [
            'timestamp' => now(),
            'method' => $request->method(),
            'headers' => $headers, // Cabeçalhos
            'query_params' => $queryParams, // Apenas parâmetros da query string
            'body' => !empty($body) ? json_encode($body, JSON_UNESCAPED_SLASHES) : null, // JSON decodificado ou vazio
            'form_data' => $formData, // Apenas dados do formulário multipart
            'host' => $request->ip(), // IP do cliente
            'size' => $totalSize, // Tamanho total da requisição
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
            Log::error('Erro ao carregar webhooks: '.$e->getMessage());
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
            Log::error('Erro ao deletar webhook: '.$e->getMessage());
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
            Log::error('Erro ao deletar todos os webhooks: '.$e->getMessage());
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
            Log::error('Erro ao marcar webhook como retransmitido: '.$e->getMessage());
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
            Log::error('Erro ao marcar webhook como visualizado: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao marcar como visualizado.'], 500);
        }
    }

    public function loadSingle(int $id)
    {
        try {
            $webhook = Webhook::where('id', $id)->first();
            return response()->json($webhook);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar webhook: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao carregar webhook.'], 500);
        }
    }

    public function createNewUrl()
    {
        try {
            // Gera um novo hash UUID para a URL
            $hash = Str::uuid();
            $ip = request()->ip();

            // Cria e salva a nova URL no banco de dados
            $newUrl = Url::create([
                'hash' => $hash,
                'ip_address' => $ip
            ]);

            // Redireciona para a nova URL com uma mensagem de sucesso
            return redirect()->route('webhook.view', [$newUrl->hash])
                ->with('success', 'Nova URL criada com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao criar nova URL: ".$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    public function retransmitWebhook(int $webhookId)
    {
        try {
            $webhook = Webhook::where('id', $webhookId)->first();

            if (!$webhook) {
                return response()->json(['error' => 'Nenhum webhook encontrado para retransmmissão.'], 404);
            }

            $url = $webhook->url; // Assume que o relacionamento 'url' está configurado no modelo Webhook
            if (!$url) {
                return response()->json(['error' => 'Webhook não está associado a nenhuma URL.'], 404);
            }

            $retransmissionUrls = $url->webhook_retransmission_urls()->get(); // Apenas URLs online
            if (!$retransmissionUrls) {
                return response()->json(['error' => 'Webhook não possui nenhuma URL de retransmissão cadastrada.'], 404);
            }

            foreach ($retransmissionUrls as $retransmissionUrl) {
                if (!$retransmissionUrl->is_online) {
                    $this->triggerPusherEvent(['id' => $webhookId, 'url' => $retransmissionUrl->url],
                        'local-retransmission');
                } else {
                    $queryParams = is_string($webhook->query_params)
                        ? json_decode($webhook->query_params, true)
                        : ($webhook->query_params ?? []);

                    $queryString = http_build_query($queryParams);
                    $fullUrl = $queryString ? "{$retransmissionUrl->url}?{$queryString}" : $retransmissionUrl->url;

                    $headers = is_string($webhook->headers)
                        ? json_decode($webhook->headers, true)
                        : ($webhook->headers ?? []);

                    $response = Http::withHeaders($headers)
                        ->send($webhook->method, $fullUrl, [
                            'body' => $webhook->body,
                        ]);

                    if ($response->failed()) {
                        Log::error("Falha na retransmissão para {$fullUrl}");
                    }
                }
            }

            $this->markRetransmitted($webhookId);

            return response()->json(['success' => 'Retransmissão concluída.']);
        } catch (\Exception $e) {
            Log::error('Erro ao retransmitir webhook: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

}
