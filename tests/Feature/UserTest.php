<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $this->city->id]);
    }

    // ── Index (listar / filtrar) ──────────────────────────────

    public function test_index_returns_paginated_users(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/users');

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => 'Usuários listados com sucesso.'])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'city', 'created_at'],
                    ],
                ],
            ]);
    }

    public function test_index_filters_by_search(): void
    {
        $target = User::factory()->create(['name' => 'Zeferino Especial']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users?search=Zeferino');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
    }

    public function test_index_filters_by_category(): void
    {
        $category = Category::first();

        $target = User::factory()->create();
        $target->categories()->attach($category->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users?categories[]={$category->id}");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
    }

    public function test_index_filters_by_city_id(): void
    {
        $target = User::factory()->create(['city_id' => $this->city->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users?city_id={$this->city->id}");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
    }

    public function test_index_respects_per_page(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/users?per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    }

    // ── Show ──────────────────────────────────────────────────

    public function test_show_returns_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->user->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Usuário encontrado.',
                'data' => ['id' => $this->user->id],
            ]);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/users/999999');

        $response->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $response = $this->getJson("/api/users/{$this->user->id}");

        $response->assertUnauthorized();
    }

    // ── Auth ──────────────────────────────────────────────────

    public function test_user_auth_returns_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/users/auth');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Usuário autenticado.',
                'data' => ['id' => $this->user->id],
            ]);
    }

    public function test_user_auth_requires_auth(): void
    {
        $response = $this->getJson('/api/users/auth');

        $response->assertUnauthorized();
    }

    // ── Update ────────────────────────────────────────────────

    public function test_update_modifies_user(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/users/{$this->user->id}", [
                'name' => 'Nome Atualizado',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Nome Atualizado',
        ]);
    }

    public function test_update_syncs_categories(): void
    {
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/users/{$this->user->id}", [
                'categories' => $categories->pluck('id')->toArray(),
            ]);

        $response->assertOk();

        $this->user->refresh();
        $this->assertCount(2, $this->user->categories);
    }

    public function test_update_requires_auth(): void
    {
        $response = $this->putJson("/api/users/{$this->user->id}", [
            'name' => 'Nome Atualizado',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_forbids_other_user(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/users/{$this->user->id}", [
                'name' => 'Tentativa de outro usuário',
            ]);

        $response->assertForbidden();
    }

    public function test_update_validates_email_unique(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/users/{$this->user->id}", [
                'email' => $otherUser->email,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
