<?php

namespace Tests\Unit;

use App\Http\Controllers\AccountController;
use App\Models\Account;
use App\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_an_account_successfully()
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(200);
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

        // Criar URL associada à conta
        $url = Url::create([
            'account_id' => $account->id,
            'ip_address' => '127.0.0.1',
            'hash' => Str::uuid(),
        ]);

        $response = $this->postJson(route('login'), [
            'email' => $account->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect']);
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
    public function it_logs_out_successfully()
    {
        $account = Account::factory()->create();
        $this->actingAs($account);

        $response = $this->postJson(route('logout'));
        $response->assertStatus(200);
    }
}
