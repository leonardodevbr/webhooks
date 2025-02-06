<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_register_a_user()
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounts', ['email' => 'test@example.com']);
    }

    #[Test]
    public function it_can_login_a_user()
    {
        Account::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'hash' => Str::uuid(),
            'slug' => 'test-user',
        ]);

        $this->assertTrue(Auth::attempt([
            'email' => 'test@example.com',
            'password' => 'password',
        ]));
    }

    #[Test]
    public function it_blocks_invalid_login()
    {
        $response = $this->postJson(route('login'), [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_denies_access_to_protected_route_without_authentication()
    {
        $response = $this->getJson(route('plans.index'));
        $response->assertStatus(401);
    }

    #[Test]
    public function it_allows_access_to_protected_route_with_authentication()
    {
        $user = Account::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'hash' => Str::uuid(),
            'slug' => 'test-user',
        ]);

        Auth::attempt([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user);

        $response = $this->getJson(route('webhook.retransmission.list'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_logout_a_user()
    {
        $account = Account::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'hash' => Str::uuid(),
            'slug' => 'test-user',
            'is_admin' => true
        ]);

        $this->actingAs($account);

        $this->postJson(route('logout'))->assertStatus(200);

        $response = $this->getJson(route('plans.index'));
        $response->assertStatus(401);
    }
}
