<?php

namespace App\Http\Controllers;

use App\Models\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UrlController extends Controller
{
    /**
     * Cria uma URL para usuários anônimos (baseada no IP).
     */
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
            Log::error('Erro ao criar URL: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    /**
     * Cria uma nova URL vinculada a uma conta autenticada.
     */
    public function createNewUrl()
    {
        try {
            $user = Auth::user();

            $url = Url::create([
                'hash' => Str::uuid(),
                'account_id' => $user->id,
                'slug' => request('slug'),
            ]);

            return redirect()->route('url.view', [$user->slug, $url->slug])
                ->with('success', 'Nova URL criada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao criar nova URL: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    /**
     * Exibe os webhooks associados a uma URL vinculada a uma conta.
     */
    public function view(string $accountSlug, string $urlSlug)
    {
        try {
            $url = Url::where('slug', $urlSlug)
                ->whereHas('account', fn($query) => $query->where('slug', $accountSlug))
                ->first();

            if (!$url) {
                return redirect()->route('url.create-anonymous')
                    ->with('info', 'A URL solicitada não existe.');
            }

            $webhooks = $url->webhooks()->orderBy('created_at', 'desc')->get();

            return view('url.view', [
                'webhooks' => $webhooks,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao exibir webhooks: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }

    /**
     * Escuta webhooks enviados para uma URL vinculada a uma conta.
     */
    public function listener(string $accountSlug, string $urlSlug)
    {
        try {
            $url = Url::where('slug', $urlSlug)
                ->whereHas('account', fn($query) => $query->where('slug', $accountSlug))
                ->first();

            if (!$url) {
                return response()->json(['error' => 'URL inválida.'], 404);
            }

            // Aqui seria a lógica para processar webhooks enviados para a URL.
            return response()->json(['message' => 'Webhook recebido com sucesso.']);
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar a solicitação.'], 500);
        }
    }
}
