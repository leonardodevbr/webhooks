<?php

namespace Tests\Unit;

use App\Http\Controllers\PublicController;
use App\Models\Webhook;
use App\Models\Url;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\Test;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_url_correctly()
    {
        $response = $this->get(route('webhook.create'));
        $response->assertRedirect();

        $this->assertDatabaseCount('urls', 1);
        $url = Url::first();
        $this->assertNotNull($url->hash);
    }

    #[Test]
    public function it_retrieves_webhooks_for_valid_url()
    {
        $url = Url::factory()->create();
        Webhook::factory()->count(3)->create(['url_id' => $url->id]);

        $response = $this->get(route('webhook.view', ['url_hash' => $url->hash]));
        $response->assertOk();
        $response->assertViewHas('webhooks', function ($webhooks) {
            return count($webhooks) === 3;
        });
    }

    #[Test]
    public function it_retrieves_webhooks_for_authenticated_user()
    {
        $account = Account::factory()->create();
        $this->actingAs($account);

        $url = Url::factory()->create(['account_id' => $account->id]);
        Webhook::factory()->count(2)->create(['url_id' => $url->id]);

        $response = $this->get(route('account.webhook.view', [
            'url_hash' => $url->hash
        ]));

        $response->assertOk();
        $response->assertViewHas('webhooks', function ($webhooks) {
            return count($webhooks) === 2;
        });
    }

    #[Test]
    public function it_handles_valid_webhook_listener_request()
    {
        $url = Url::factory()->create();

        $response = $this->postJson(route('webhook.listener', ['url_hash' => $url->hash]));
        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_valid_webhook_custom_listener_request()
    {
        $url = Url::factory()->create(['slug' => 'custom-slug']);

        $response = $this->postJson(route('webhook.custom-listener', ['url_slug' => 'custom-slug', 'url_hash' => $url->hash]));
        $response->assertStatus(200);
    }

    #[Test]
    public function it_handles_invalid_webhook_listener_request()
    {
        $response = $this->postJson(route('webhook.listener', ['url_hash' => 'invalid']));
        $response->assertStatus(404);
    }

    #[Test]
    public function it_handles_invalid_webhook_custom_listener_request()
    {
        $response = $this->postJson(route('webhook.custom-listener', ['url_slug' => 'invalid', 'url_hash' => 'invalid']));
        $response->assertStatus(404);
    }

    #[Test]
    public function it_marks_webhook_as_viewed()
    {
        $webhook = Webhook::factory()->create(['viewed' => false]);

        $response = $this->patchJson(route('webhook.mark-viewed', ['id' => $webhook->id]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'viewed' => true,
        ]);
    }

    #[Test]
    public function it_extracts_request_data_correctly()
    {
        $url = Url::factory()->create();

        $request = Request::create('/webhook?id=123', 'POST', ['id' => '123'], [], [], [
            'HTTP_Content-Type' => 'application/json'
        ], json_encode(['key' => 'value']));

        $controller = new PublicController();
        $reflection = new ReflectionMethod(PublicController::class, 'extractRequestData');
        $reflection->setAccessible(true);

        $data = $reflection->invoke($controller, $request, $url->id);

        $this->assertEquals('POST', $data['method']);
        $this->assertArrayHasKey('content-type', array_change_key_case($data['headers'], CASE_LOWER));
        $this->assertArrayHasKey('id', $data['query_params']);
        $this->assertEquals('123', $data['query_params']['id']);
        $this->assertEquals($url->id, $data['url_id']);
    }
}
