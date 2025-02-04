<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccountFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_a_user_and_logs_in()
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('accounts', ['email' => 'test@example.com']);
    }

    #[Test]
    public function it_fails_to_register_duplicate_email()
    {
        $account = Account::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_logs_in_successfully()
    {
        $account = Account::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson(route('login'), [
            'email' => $account->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect']);
        $this->assertAuthenticatedAs($account);
    }

    #[Test]
    public function it_fails_to_login_with_invalid_credentials()
    {
        $response = $this->postJson(route('login'), [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_redirects_guest_users_to_login()
    {
        $response = $this->get(route('account.webhook.view', ['account_slug' => 'test', 'url_hash' => 'test']));
        $response->assertStatus(302);
        $response->assertRedirect(route('form.login'));
    }

    #[Test]
    public function it_logs_out_successfully()
    {
        $account = Account::factory()->create();
        $this->actingAs($account);

        $response = $this->postJson(route('logout'));
        $response->assertStatus(200);
        $this->assertGuest();
    }
}
