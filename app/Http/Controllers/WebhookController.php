<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * Exibe os webhooks associados a uma URL pública.
     */
    public function view(string $urlHash)
    {
        try {
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                return redirect()->route('webhook.create-url')
                    ->with('info', 'A URL solicitada não existe. Criamos uma nova URL para você.');
            }

            $webhooks = $url->webhooks()->orderBy('created_at', 'desc')->get();

            return view('webhook.view', [
                'webhooks' => $webhooks,
                'urlHash' => $urlHash,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao exibir webhooks: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    /**
     * Processa webhooks recebidos para uma URL pública.
     */
    public function listener(string $urlHash)
    {
        try {
            $url = Url::where('hash', $urlHash)->first();

            if (!$url) {
                return response()->json(['error' => 'URL inválida.'], 404);
            }

            // Captura os dados do webhook e os armazena no banco.
            $webhook = Webhook::create([
                'id' => Str::uuid(),
                'url_id' => $url->id,
                'timestamp' => now(),
                'method' => request()->method(),
                'headers' => json_encode(getallheaders()),
                'query_params' => json_encode(request()->query()),
                'body' => request()->getContent(),
                'host' => request()->ip(),
                'size' => strlen(request()->getContent()),
                'retransmitted' => false,
            ]);

            return response()->json(['message' => 'Webhook recebido.', 'data' => $webhook]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
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
}
