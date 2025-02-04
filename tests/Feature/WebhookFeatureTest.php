<?php

namespace Tests\Feature;

use App\Models\Url;
use App\Models\Webhook;
use App\Models\WebhookRetransmissionUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WebhookFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_webhook()
    {
        $url = Url::factory()->create();

        $response = $this->postJson(route('webhook.listener', ['url_hash' => $url->hash]), [
            'data' => ['test' => 'value']
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('webhooks', 1);
    }

    #[Test]
    public function it_handles_invalid_webhook_requests()
    {
        $response = $this->postJson(route('webhook.listener', ['url_hash' => 'invalid']), []);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_retransmits_a_webhook()
    {
        $url = Url::factory()->create();
        $webhook = Webhook::factory()->create([
            'url_id' => $url->id,
            'method' => 'PUT', // Correspondendo ao método usado na retransmissão
            'query_params' => json_encode(['id' => 0]), // Query params sendo usados
        ]);

        $onlineUrl = WebhookRetransmissionUrl::factory()->create([
            'url_id' => $url->id,
            'url' => 'https://example.com/callback',
            'is_online' => true
        ]);

        $localUrl = WebhookRetransmissionUrl::factory()->create([
            'url_id' => $url->id,
            'url' => 'http://localhost:8000/webhook-receive',
            'is_online' => false
        ]);

        // Fakeando todas as chamadas HTTP e garantindo que a URL completa seja capturada
        Http::fake([
            'https://example.com/callback?id=0' => Http::response(['success' => true], 200),
        ]);

        $response = $this->postJson(route('webhook.retransmit', ['id' => $webhook->id]));

        // Verifica se pelo menos uma requisição foi enviada
        Http::assertSentCount(1);

        // Valida se a requisição foi enviada corretamente para a URL online com os parâmetros esperados
        Http::assertSent(function ($request) use ($onlineUrl, $webhook) {
            return $request->url() === "{$onlineUrl->url}?id=0" // Adicionando a query string na comparação
                && $request->method() === 'PUT' // Garantindo que o método HTTP seja o esperado
                && $request->body() === $webhook->body;
        });

        $response->assertStatus(200);
    }

}
