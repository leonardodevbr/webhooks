<?php

namespace App\Http\Controllers;

use App\Models\WebhookRetransmissionUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookUrlController extends Controller
{
    public function listRetransmissionUrls()
    {
        try {
            $urls = WebhookRetransmissionUrl::all(); // Recupera todas as URLs de retransmissão
            return response()->json($urls);
        } catch (\Exception $e) {
            Log::error("Erro ao listar URLs de retransmissão: ".$e->getMessage());
            return response()->json(['error' => 'Erro ao listar URLs de retransmissão.'], 500);
        }
    }

    public function addRetransmissionUrl(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:255',
            'process_immediately' => 'boolean',
            'is_online' => 'boolean'
        ]);

        try {
            $newUrl = WebhookRetransmissionUrl::create([
                'url' => $validated['url'],
                'process_immediately' => $validated['process_immediately'] ?? false,
                'is_online' => $validated['is_online'] ?? false,
            ]);

            return response()->json(['success' => 'URL de retransmissão adicionada com sucesso.', 'url' => $newUrl]);
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar URL de retransmissão: ".$e->getMessage());
            return response()->json(['error' => 'Erro ao adicionar URL de retransmissão.'], 500);
        }
    }


    public function removeRetransmissionUrl($id)
    {
        try {
            $url = WebhookRetransmissionUrl::findOrFail($id); // Busca a URL pelo ID
            $url->delete(); // Exclui a URL
            return response()->json(['success' => 'URL de retransmissão removida com sucesso.']);
        } catch (\Exception $e) {
            Log::error("Erro ao remover URL de retransmissão: ".$e->getMessage());
            return response()->json(['error' => 'Erro ao remover URL de retransmissão.'], 500);
        }
    }

    public function listRetransmissionUrlsForUrl($urlId)
    {
        try {
            $urls = WebhookRetransmissionUrl::where('url_id', $urlId)->get(); // Busca URLs associadas
            return response()->json($urls);
        } catch (\Exception $e) {
            Log::error("Erro ao listar URLs de retransmissão para URL específica: ".$e->getMessage());
            return response()->json(['error' => 'Erro ao listar URLs de retransmissão.'], 500);
        }
    }

    public function retransmitWebhook($webhookData)
    {
        $urls = WebhookRetransmissionUrl::where('is_online', true)->get();

        foreach ($urls as $url) {
            try {
                $queryParams = http_build_query($webhookData['query_params'] ?? []);
                $fullUrl = $queryParams ? "{$url->url}?{$queryParams}" : $url->url;

                $response = Http::withHeaders($webhookData['headers'] ?? [])
                    ->send($webhookData['method'], $fullUrl, [
                        'body' => $webhookData['body'] ?? null,
                    ]);

                if ($response->failed()) {
                    // Log de erro
                    logger()->error("Falha na retransmissão para {$fullUrl}");
                }
            } catch (\Exception $e) {
                logger()->error("Erro ao retransmitir para {$url->url}: {$e->getMessage()}");
            }
        }
    }
}
