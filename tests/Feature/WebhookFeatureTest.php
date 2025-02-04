<?php

namespace Tests\Feature;

use App\Models\Url;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $webhook = Webhook::factory()->create(['url_id' => $url->id]);

        $response = $this->postJson(route('webhook.retransmit', ['id' => $webhook->id]));

        $response->assertStatus(200);
    }
}
