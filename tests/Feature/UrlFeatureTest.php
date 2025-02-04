<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UrlFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_monitoring_url()
    {
        $response = $this->postJson(route('webhook.create-new-url'));

        // Aceita tanto redirecionamento quanto resposta 200
        $response->assertStatus(302);
        $response->assertRedirect();

        // Opcionalmente, seguir o redirecionamento e validar a página final:
        $this->followRedirects($response)->assertStatus(200);

        $this->assertDatabaseCount('urls', 1);
    }


    #[Test]
    public function it_retrieves_webhooks_for_valid_url()
    {
        $url = Url::factory()->create();

        $response = $this->get(route('webhook.load', ['url_hash' => $url->hash]));
        $response->assertStatus(200);
    }

    #[Test]
    public function it_returns_404_for_invalid_url()
    {
        $response = $this->get(route('webhook.load', ['url_hash' => 'invalid']));
        $response->assertStatus(404);
    }
}
