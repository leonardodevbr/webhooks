<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\Webhook;
use App\Models\WebhookRetransmissionUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        try {
            // Validação dos dados
            $validated = $request->validate([
                'url_id' => 'required|integer|exists:urls,id', // Garante que o ID existe na tabela 'urls'
                'url' => 'required|url|max:255', // URL válida com limite de 255 caracteres
                'is_online' => 'boolean' // Garante que seja booleano
            ]);

            // Criação da URL de retransmissão
            $newUrl = WebhookRetransmissionUrl::create([
                'url_id' => $validated['url_id'],
                'url' => $validated['url'],
                'is_online' => $validated['is_online'] ?? false,
            ]);

            return response()->json(['success' => 'URL de retransmissão adicionada com sucesso.', 'url' => $newUrl]);
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar URL de retransmissão: " . $e->getMessage());
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
}
