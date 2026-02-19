<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::inRandomOrder()->first();
    }

    // ── Register ─────────────────────────────────────────────

    public function test_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'João Silva',
            'username' => 'joao.silva',
            'email' => 'joao@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso!',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'data'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@email.com',
            'name' => 'João Silva',
            'city_id' => $this->city->id,
        ]);
    }

    public function test_register_with_categories(): void
    {
        $categories = Category::factory()->count(2)->create();

        $response = $this->postJson('/api/register', [
            'name' => 'Maria Souza',
            'username' => 'maria.souza',
            'email' => 'maria@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
            'categories' => $categories->pluck('id')->toArray(),
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $user = User::where('email', 'maria@email.com')->first();
        $this->assertCount(2, $user->categories);
    }

    public function test_register_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'username', 'email', 'password', 'city_id']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'username' => 'existing.user',
            'email' => 'duplicate@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'username' => 'another.user',
            'email' => 'duplicate@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => '123',
            'city_id' => $this->city->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_with_invalid_city_id(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => '12345678',
            'city_id' => 9999,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    }

    // ── Login ────────────────────────────────────────────────

    public function test_login_with_valid_credentials(): void
    {
        User::create([
            'name' => 'João',
            'username' => 'joao',
            'email' => 'joao@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'joao@email.com',
            'password' => '12345678',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login realizado com sucesso!',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'data'],
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create([
            'name' => 'João',
            'username' => 'joao',
            'email' => 'joao@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'joao@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Credenciais inválidas',
            ]);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@email.com',
            'password' => '12345678',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ── Logout ───────────────────────────────────────────────

    public function test_logout_with_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'João',
            'username' => 'joao',
            'email' => 'joao@email.com',
            'password' => '12345678',
            'city_id' => $this->city->id,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Desconectado',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_fails_without_authentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }
}
