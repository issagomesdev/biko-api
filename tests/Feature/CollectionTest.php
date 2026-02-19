<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Collection;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $city->id]);
    }

    // ── Index ──────────────────────────────────────────────────

    public function test_index_returns_user_collections(): void
    {
        Collection::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/collections');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/collections');

        $response->assertUnauthorized();
    }

    // ── Store ─────────────────────────────────────────────────

    public function test_store_creates_collection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/collections', [
                'name' => 'Meus favoritos',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('collections', [
            'user_id' => $this->user->id,
            'name' => 'Meus favoritos',
        ]);
    }

    public function test_store_validates_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/collections', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // ── Show ──────────────────────────────────────────────────

    public function test_show_returns_collection_with_publications(): void
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/collections/{$collection->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['id' => $collection->id],
            ]);
    }

    // ── Update ────────────────────────────────────────────────

    public function test_update_renames_collection(): void
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/collections/{$collection->id}", [
                'name' => 'Nome atualizado',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'name' => 'Nome atualizado',
        ]);
    }

    public function test_update_prevents_renaming_default(): void
    {
        $collection = Collection::factory()->default()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/collections/{$collection->id}", [
                'name' => 'Tentativa',
            ]);

        $response->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────

    public function test_destroy_deletes_collection(): void
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/collections/{$collection->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    public function test_destroy_prevents_deleting_default(): void
    {
        $collection = Collection::factory()->default()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/collections/{$collection->id}");

        $response->assertForbidden();
    }

    // ── Toggle publication ────────────────────────────────────

    public function test_toggle_adds_publication_to_collection(): void
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $publication = Publication::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/collections/{$collection->id}/publications/{$publication->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('collection_publication', [
            'collection_id' => $collection->id,
            'publication_id' => $publication->id,
        ]);
    }

    public function test_toggle_removes_publication_from_collection(): void
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $publication = Publication::factory()->create(['user_id' => $this->user->id]);

        $collection->publications()->attach($publication->id);

        $response = $this->actingAs($this->user)
            ->postJson("/api/collections/{$collection->id}/publications/{$publication->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('collection_publication', [
            'collection_id' => $collection->id,
            'publication_id' => $publication->id,
        ]);
    }
}
