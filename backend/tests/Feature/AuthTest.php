<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Rahasia123';

    public function test_user_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Dimas',
            'email' => 'Dimas@Example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'dimas@example.com')
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'dimas@example.com']);
        $this->assertStringStartsWith('cfo_', explode('|', $response->json('token'))[1]);
    }

    public function test_register_validation_returns_422(): void
    {
        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'bukan-email',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dimas@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Dimas',
            'email' => 'DIMAS@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'email']])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'SalahBanget1'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_login_with_unknown_email_returns_same_401(): void
    {
        $this->postJson('/api/login', ['email' => 'tidak-ada@example.com', 'password' => self::PASSWORD])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'SalahBanget1'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertTooManyRequests();
    }

    public function test_protected_endpoint_without_token_returns_401(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_current_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);
        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Lupakan user yang sudah di-resolve guard di request sebelumnya.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_expired_token_is_rejected(): void
    {
        config(['sanctum.expiration' => 60]);
        $user = User::factory()->create(['password' => self::PASSWORD]);
        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->json('token');

        $this->travel(61)->minutes();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_api_responses_include_security_headers(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
