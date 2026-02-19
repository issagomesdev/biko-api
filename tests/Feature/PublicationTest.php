<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Comment;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Publication $publication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_private' => false]);
        $this->publication = Publication::factory()->create(['user_id' => $this->user->id]);
    }

    // ── Index (listar / filtrar) ──────────────────────────────

    public function test_index_returns_paginated_publications(): void
    {
        $response = $this->getJson('/api/publications');

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => 'Publicações listadas com sucesso.'])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'text', 'type', 'author', 'created_at'],
                    ],
                ],
            ]);
    }

    public function test_index_filters_by_search(): void
    {
        $pub = Publication::factory()->create([
            'user_id' => $this->user->id,
            'text' => 'Preciso de um eletricista urgente para minha casa',
        ]);

        $response = $this->getJson('/api/publications?search=eletricista');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($pub->id));
    }

    public function test_index_filters_by_type(): void
    {
        $client = Publication::factory()->client()->create(['user_id' => $this->user->id]);
        $provider = Publication::factory()->provider()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/publications?type=0');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($client->id));
        $this->assertFalse($ids->contains($provider->id));
    }

    public function test_index_filters_by_category(): void
    {
        $category = Category::first();

        $pub = Publication::factory()->create(['user_id' => $this->user->id]);
        $pub->categories()->attach($category->id);

        $response = $this->getJson("/api/publications?categories[]={$category->id}");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($pub->id));
    }

    public function test_index_filters_by_city(): void
    {
        $city = City::first();

        $pub = Publication::factory()->create([
            'user_id' => $this->user->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson("/api/publications?city_id={$city->id}");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($pub->id));
    }

    public function test_index_respects_per_page(): void
    {
        Publication::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/publications?per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_index_validates_invalid_type(): void
    {
        $response = $this->getJson('/api/publications?type=99');

        $response->assertUnprocessable();
    }

    // ── Index (filtro por data) ───────────────────────────────

    public function test_index_filters_by_date_today(): void
    {
        $today = Publication::factory()->create(['user_id' => $this->user->id]);
        $old = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->getJson('/api/publications?date=today');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($today->id));
        $this->assertFalse($ids->contains($old->id));
    }

    public function test_index_filters_by_date_last_7d(): void
    {
        $recent = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3),
        ]);
        $old = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/publications?date=last_7d&per_page=100');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($recent->id));
        $this->assertFalse($ids->contains($old->id));
    }

    public function test_index_filters_by_date_last_30d(): void
    {
        $recent = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(15),
        ]);
        $old = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(60),
        ]);

        $response = $this->getJson('/api/publications?date=last_30d&per_page=100');
        $response->assertOk();
        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($recent->id));
        $this->assertFalse($ids->contains($old->id));
    }

    public function test_index_filters_by_specific_date(): void
    {
        $date = now()->subDays(5);

        $target = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => $date,
        ]);

        $response = $this->getJson('/api/publications?date='.$date->format('Y-m-d'));

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($target->id));
    }

    public function test_index_filters_by_date_range(): void
    {
        $inside = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);
        $outside = Publication::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(20),
        ]);

        $from = now()->subDays(10)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->getJson("/api/publications?date_from={$from}&date_to={$to}&per_page=100");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($inside->id));
        $this->assertFalse($ids->contains($outside->id));
    }

    // ── Show ──────────────────────────────────────────────────

    public function test_show_returns_publication(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/publications/{$this->publication->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Publicação encontrada.',
                'data' => ['id' => $this->publication->id],
            ]);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/publications/999999');

        $response->assertNotFound();
    }

    public function test_show_requires_auth(): void
    {
        $response = $this->getJson("/api/publications/{$this->publication->id}");

        $response->assertUnauthorized();
    }

    // ── Store ─────────────────────────────────────────────────

    public function test_store_creates_publication(): void
    {
        $city = City::first();
        $category = Category::first();

        $payload = [
            'text' => 'Preciso de um encanador para consertar uma torneira',
            'type' => Publication::TYPE_CLIENT,
            'city_id' => $city->id,
            'categories' => [$category->id],
            'tags' => ['urgente', 'encanamento'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/publications', $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Publicação criada com sucesso.',
            ]);

        $this->assertDatabaseHas('publications', [
            'text' => $payload['text'],
            'type' => $payload['type'],
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_requires_auth(): void
    {
        $response = $this->postJson('/api/publications', [
            'text' => 'Preciso de um encanador para consertar uma torneira',
            'type' => Publication::TYPE_CLIENT,
            'city_id' => 1,
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/publications', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'type', 'city_id']);
    }

    public function test_store_validates_text_min_length(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/publications', [
                'text' => 'curto',
                'type' => Publication::TYPE_CLIENT,
                'city_id' => City::first()->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    // ── Update ────────────────────────────────────────────────

    public function test_update_modifies_publication(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson("/api/publications/{$this->publication->id}", [
                'text' => 'Texto atualizado da publicação com mais detalhes',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Publicação atualizada com sucesso.',
            ]);

        $this->assertDatabaseHas('publications', [
            'id' => $this->publication->id,
            'text' => 'Texto atualizado da publicação com mais detalhes',
        ]);
    }

    public function test_update_requires_auth(): void
    {
        $response = $this->putJson("/api/publications/{$this->publication->id}", [
            'text' => 'Texto atualizado da publicação com mais detalhes',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_forbids_other_user(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/publications/{$this->publication->id}", [
                'text' => 'Tentativa de atualização por outro usuário',
            ]);

        $response->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────

    public function test_destroy_deletes_publication(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/publications/{$this->publication->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Publicação deletada com sucesso.',
            ]);

        $this->assertDatabaseMissing('publications', ['id' => $this->publication->id]);
    }

    public function test_destroy_requires_auth(): void
    {
        $response = $this->deleteJson("/api/publications/{$this->publication->id}");

        $response->assertUnauthorized();
    }

    // ── Like ──────────────────────────────────────────────────

    public function test_like_toggles_on(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/publications/like/{$this->publication->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['liked' => true],
                'message' => 'Like adicionado.',
            ]);

        $this->assertDatabaseHas('likes', [
            'publication_id' => $this->publication->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_like_toggles_off(): void
    {
        // Like primeiro
        $this->actingAs($this->user)
            ->postJson("/api/publications/like/{$this->publication->id}");

        // Unlike
        $response = $this->actingAs($this->user)
            ->postJson("/api/publications/like/{$this->publication->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['liked' => false],
                'message' => 'Like removido.',
            ]);

        $this->assertDatabaseMissing('likes', [
            'publication_id' => $this->publication->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_like_requires_auth(): void
    {
        $response = $this->postJson("/api/publications/like/{$this->publication->id}");

        $response->assertUnauthorized();
    }

    // ── Comment ───────────────────────────────────────────────

    public function test_comment_adds_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/publications/comment/{$this->publication->id}", [
                'comment' => 'Ótimo serviço, recomendo!',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Comentário adicionado com sucesso.',
            ]);

        $this->assertDatabaseHas('comments', [
            'publication_id' => $this->publication->id,
            'user_id' => $this->user->id,
            'comment' => 'Ótimo serviço, recomendo!',
        ]);
    }

    public function test_comment_requires_auth(): void
    {
        $response = $this->postJson("/api/publications/comment/{$this->publication->id}", [
            'comment' => 'Teste',
        ]);

        $response->assertUnauthorized();
    }

    public function test_comment_validates_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/publications/comment/{$this->publication->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    }

    // ── Delete Comment ───────────────────────────────────────

    public function test_delete_comment_removes_comment(): void
    {
        $comment = Comment::factory()->create([
            'publication_id' => $this->publication->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/publications/comment/{$comment->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Comentário deletado com sucesso.',
            ]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_delete_comment_requires_auth(): void
    {
        $comment = Comment::factory()->create([
            'publication_id' => $this->publication->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/publications/comment/{$comment->id}");

        $response->assertUnauthorized();
    }

    public function test_delete_comment_forbids_other_user(): void
    {
        $comment = Comment::factory()->create([
            'publication_id' => $this->publication->id,
            'user_id' => $this->user->id,
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/publications/comment/{$comment->id}");

        $response->assertForbidden();
    }
}
