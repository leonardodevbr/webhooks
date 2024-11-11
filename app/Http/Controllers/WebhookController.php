<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pusher\Pusher;

class WebhookController extends Controller
{
    public function createUrl(): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $ip = request()->ip();

            // Verifica se já existe um registro com o IP atual
            $url = Url::where('ip_address', $ip)->first();

            if ($url) {
                // Se o IP já existe, redireciona usando o hash existente
                return redirect()->route('webhook.view', [$url->hash])->with('info', 'Você já possui uma URL criada.');
            }

            // Caso contrário, cria uma nova URL com um novo hash
            $hash = Str::uuid();
            $newUrl = Url::create([
                'hash' => $hash,
                'ip_address' => $ip
            ]);

            if ($newUrl) {
                return redirect()->route('webhook.view', [$newUrl->hash])->with(
                    'success',
                    'Nova URL criada com sucesso.'
                );
            }

            // Opcionalmente, um retorno para o caso de falha na criação
            return response()->json(['error' => 'Não foi possível criar a URL.'], 500);
        } catch (\Exception $e) {
            Log::error('Erro ao criar URL: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }


    public function view(string $urlHash)
    {
        try {
            // Verifica se o hash existe na tabela URLs
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                // Redireciona para uma nova URL com uma mensagem de erro
                return redirect()->route('webhook.create-url')->with(
                    'error',
                    'A URL solicitada é inválida ou inexistente. Geramos uma nova URL para você.'
                );
            }

            // Obtenção dos webhooks filtrados pelo hash, do mais recente ao mais antigo
            $webhooks = $url->webhooks()->orderBy('created_at', 'desc')->get();

            return view('webhook.view', [
                'webhooks' => $webhooks,
                'urlHash' => $urlHash
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao visualizar webhooks: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }


    public function load(string $urlHash)
    {
        try {
            // Verifica se o hash existe na tabela URLs
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                // Redireciona para uma nova URL com uma mensagem de erro
                return redirect()->route('webhook.create-url')->with(
                    'error',
                    'A URL solicitada é inválida ou inexistente. Geramos uma nova URL para você.'
                );
            }

            // Obtém todos os webhooks associados ao hash, ordenados do mais recente ao mais antigo
            $webhooks = $url->webhooks()->orderBy('created_at', 'desc')->get();

            // Retorna os dados dos webhooks em formato JSON
            return response()->json($webhooks);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar webhooks: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }


    public function listener(string $urlHash)
    {
        try {
            // Configuração do Pusher
            $config = config('services.pusher');
            $pusher = new Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                ['cluster' => $config['cluster']]
            );

            // Verifica se o hash existe na tabela URLs
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                return response()->json(['status' => 'error', 'message' => 'Hash de URL inválido'], 404);
            }

            // Obtenção dos dados da requisição
            $method = request()->method();
            $headers = getallheaders();
            $queryParams = request()->query();
            $body = request()->getContent();
            $host = request()->ip();
            $size = strlen($body);

            // Extração do form-data se o content-type for multipart/form-data
            $formData = [];
            if (strpos($headers['Content-Type'] ?? '', 'multipart/form-data') !== false) {
                $formData = $this->parseFormData($body);
            }

            // Criação dos dados do webhook conforme o esquema da tabela
            $requestData = [
                'id' => Str::uuid(),
                'url_id' => $url->id,
                'timestamp' => now(),
                'method' => $method,
                'headers' => json_encode($headers),         // Salva headers como JSON
                'query_params' => json_encode($queryParams), // Salva parâmetros da URL como JSON
                'body' => $body,                            // Salva o corpo da requisição completo
                'form_data' => json_encode($formData),      // Salva form-data como JSON
                'host' => $host,
                'size' => $size,
                'retransmitted' => false,
            ];

            // Armazena o webhook na base de dados
            $webhook = Webhook::create($requestData);

            if ($webhook) {
                // Envia o evento para o Pusher
                $pusher->trigger(config('services.pusher.channel'), 'new-webhook', $requestData);

                // Retorna uma resposta de sucesso
                return response()->json(['status' => 'Requisição recebida e registrada']);
            }

            Log::error('Erro ao registrar webhook');
            return response()->json(['status' => 'error', 'message' => 'Erro ao registrar webhook'], 500);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar webhook: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erro ao processar a solicitação'], 500);
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


    public function delete(string $uuid)
    {
        try {
            // Verifica se o UUID do webhook foi fornecido
            if (!$uuid) {
                return response()->json(['error' => 'UUID do webhook não especificado'], 400);
            }

            // Busca o webhook pelo UUID e tenta deletá-lo
            $webhook = Webhook::where('id', $uuid)->first();

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

    public function markRetransmitted(string $uuid)
    {
        try {
            // Encontra o webhook pelo ID
            $webhook = Webhook::where('id', $uuid)->first();

            // Atualiza o status de 'retransmitted' para true
            $webhook->update(['retransmitted' => true]);

            return response()->json(['status' => 'success', 'message' => 'Webhook marcado como retransmitido']);
        } catch (\Exception $e) {
            Log::error('Erro ao marcar webhook como retransmitido: '.$e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Erro ao processar a solicitação'], 500);
        }
    }

    public function markAsViewed(string $uuid)
    {
        try {
            $webhook = Webhook::where('id', $uuid)->first();
            $webhook->viewed = true;
            $webhook->save();

            return response()->json(['message' => 'Webhook marcado como visualizado.']);
        } catch (\Exception $e) {
            Log::error('Erro ao marcar webhook como visualizado: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao marcar como visualizado.'], 500);
        }
    }

    public function loadSingle(string $uuid)
    {
        try {
            $webhook = Webhook::where('id', $uuid)->first();
            return response()->json($webhook);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar webhook: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao carregar webhook.'], 500);
        }
    }

    public function createNewUrl(): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
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
}
