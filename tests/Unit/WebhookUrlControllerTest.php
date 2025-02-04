<?php

namespace Tests\Unit;

use App\Models\Url;
use App\Models\WebhookRetransmissionUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WebhookUrlControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_all_retransmission_urls()
    {
        WebhookRetransmissionUrl::factory()->count(3)->create();

        $response = $this->getJson(route('webhook.retransmission.list'));

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    #[Test]
    public function it_adds_a_retransmission_url()
    {
        $url = Url::factory()->create();
        $data = [
            'url_id' => $url->id,
            'url' => 'https://example.com/webhook',
        ];

        $response = $this->postJson(route('webhook.retransmission.add'), $data);

        $response->assertStatus(200); // Verifique se sua API retorna 200 e não 201
        $this->assertDatabaseHas('webhook_retransmission_urls', $data);
    }

    #[Test]
    public function it_removes_a_retransmission_url()
    {
        $retransmissionUrl = WebhookRetransmissionUrl::factory()->create();

        $response = $this->deleteJson(route('webhook.retransmission.remove', $retransmissionUrl->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('webhook_retransmission_urls', ['id' => $retransmissionUrl->id]);
    }

    #[Test]
    public function it_lists_retransmission_urls_for_a_specific_url()
    {
        $url = Url::factory()->create();
        WebhookRetransmissionUrl::factory()->count(2)->create(['url_id' => $url->id]);

        $response = $this->getJson(route('webhook.retransmission.list-for-url', $url->id));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }
}
