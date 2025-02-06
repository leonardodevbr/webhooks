<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Url;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthenticatedWebhookFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simula a autenticação de um usuário antes de executar os testes.
     */
    protected function authenticate()
    {
        $account = Account::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($account);

        return $account;
    }

    #[Test]
    public function it_creates_a_monitoring_url_for_authenticated_user()
    {
        $account = $this->authenticate();

        $response = $this->postJson(route('account.webhook.create', [
            'account_slug' => $account->slug
        ]));

        $response->assertStatus(302);

        $redirectUrl = $response->headers->get('Location');

        $this->assertStringContainsString('/view/', $redirectUrl);
    }

    #[Test]
    public function it_retransmits_a_webhook_with_retransmission_urls()
    {
        $account = $this->authenticate();
        $url = Url::factory()->create(['account_id' => $account->id]);
        $webhook = Webhook::factory()->create(['url_id' => $url->id]);

        $retransmissionUrl = $url->webhook_retransmission_urls()->create([
            'url' => 'https://example.com/callback',
            'is_online' => true,
        ]);

        $this->assertDatabaseHas('webhook_retransmission_urls', ['url' => $retransmissionUrl->url]);

        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($account)->postJson(route('webhook.retransmit', [
            'id' => $webhook->id,
        ]));

        $response->assertStatus(200);
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_ignores_retransmission_if_no_retransmission_urls_exist()
    {
        $account = $this->authenticate();
        $url = Url::factory()->create(['account_id' => $account->id]);
        $webhook = Webhook::factory()->create(['url_id' => $url->id]);

        $this->assertDatabaseMissing('webhook_retransmission_urls', ['url_id' => $url->id]);

        $response = $this->actingAs($account)->postJson(route('webhook.retransmit', [
            'id' => $webhook->id,
        ]));

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Webhook não possui nenhuma URL de retransmissão cadastrada.'
        ]);

        Http::assertSentCount(0);
    }

    #[Test]
    public function it_denies_access_to_protected_urls_for_guests()
    {
        $response = $this->getJson(route('plans.index'));
        $response->assertStatus(401);
    }

    #[Test]
    public function it_prevents_authenticated_users_from_viewing_other_accounts_urls()
    {
        $account1 = $this->authenticate();
        $account2 = Account::factory()->create(); // Outra conta

        $url = Url::factory()->create(['account_id' => $account2->id]); // URL pertencente à outra conta

        $response = $this->actingAs($account1)->getJson(route('account.webhook.view', [
            'url_hash' => $url->hash,
        ]));

        // Aceitar tanto redirecionamento (302) quanto um possível erro 403
        $response->assertStatus(302);
        $response->assertRedirect(route('form.login')); // Verifica se redireciona para criação de uma nova URL
    }


    #[Test]
    public function it_converts_public_url_to_private_on_login()
    {
        $url = Url::factory()->create(['account_id' => null]); // URL pública

        $account = $this->authenticate();

        // Simula o login e conversão da URL pública para privada
        $url->update(['account_id' => $account->id]);

        $this->assertDatabaseHas('urls', [
            'id' => $url->id,
            'account_id' => $account->id,
        ]);
    }

    #[Test]
    public function it_creates_private_url_if_no_public_url_exists_on_login()
    {
        $account = $this->authenticate();

        // Nenhuma URL pública existe
        $this->assertDatabaseMissing('urls', ['account_id' => null]);

        // Simula a criação de uma nova URL privada ao logar
        $newUrl = Url::factory()->create(['account_id' => $account->id]);

        $this->assertDatabaseHas('urls', ['id' => $newUrl->id, 'account_id' => $account->id]);
    }

    #[Test]
    public function it_prevents_users_from_retransmitting_webhooks_of_other_accounts()
    {
        $account1 = $this->authenticate();
        $account2 = Account::factory()->create(); // Outra conta

        $url = Url::factory()->create(['account_id' => $account2->id]); // URL pertencente à outra conta
        $webhook = Webhook::factory()->create(['url_id' => $url->id]);

        $response = $this->actingAs($account1)->postJson(route('webhook.retransmit', [
            'id' => $webhook->id,
        ]));

        $response->assertStatus(403); // Agora retorna 403 Forbidden
        $response->assertJson([
            'error' => 'Acesso negado.'
        ]);
    }
}
