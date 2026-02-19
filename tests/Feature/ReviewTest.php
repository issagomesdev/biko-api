<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private User $reviewer;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $this->city->id, 'is_private' => false]);
        $this->reviewer = User::factory()->create(['city_id' => $this->city->id, 'is_private' => false]);
    }

    // ── Index ──────────────────────────────────────────────────

    public function test_index_returns_reviews_for_user(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 5,
            'comment' => 'Excelente profissional!',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->getJson("/api/users/{$this->user->id}/reviews");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($review->id));
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson("/api/users/{$this->user->id}/reviews");

        $response->assertUnauthorized();
    }

    // ── Store ─────────────────────────────────────────────────

    public function test_store_creates_review(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/users/{$this->user->id}/reviews", [
                'stars' => 4,
                'comment' => 'Muito bom, recomendo!',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 4,
            'comment' => 'Muito bom, recomendo!',
        ]);
    }

    public function test_store_prevents_self_review(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/users/{$this->user->id}/reviews", [
                'stars' => 5,
                'comment' => 'Auto avaliação',
            ]);

        $response->assertStatus(403);
    }

    public function test_store_prevents_duplicate_review(): void
    {
        Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 5,
            'comment' => 'Primeira avaliação',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/users/{$this->user->id}/reviews", [
                'stars' => 3,
                'comment' => 'Segunda avaliação',
            ]);

        $response->assertStatus(409);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/users/{$this->user->id}/reviews", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stars', 'comment']);
    }

    public function test_store_validates_stars_range(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/users/{$this->user->id}/reviews", [
                'stars' => 6,
                'comment' => 'Nota inválida',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stars']);
    }

    public function test_store_requires_auth(): void
    {
        $response = $this->postJson("/api/users/{$this->user->id}/reviews", [
            'stars' => 5,
            'comment' => 'Teste',
        ]);

        $response->assertUnauthorized();
    }

    // ── Reply ─────────────────────────────────────────────────

    public function test_reply_creates_reply(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 5,
            'comment' => 'Ótimo!',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$review->id}/reply", [
                'comment' => 'Obrigado pela avaliação!',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'parent_id' => $review->id,
            'reviewer_id' => $this->user->id,
            'comment' => 'Obrigado pela avaliação!',
        ]);
    }

    public function test_reply_validates_comment(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 5,
            'comment' => 'Ótimo!',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$review->id}/reply", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    }

    // ── Update ────────────────────────────────────────────────

    public function test_update_modifies_review(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 3,
            'comment' => 'Razoável',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->putJson("/api/reviews/{$review->id}", [
                'stars' => 5,
                'comment' => 'Na verdade, excelente!',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'stars' => 5,
            'comment' => 'Na verdade, excelente!',
        ]);
    }

    public function test_update_forbids_other_user(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 3,
            'comment' => 'Razoável',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/reviews/{$review->id}", [
                'comment' => 'Tentativa de outro',
            ]);

        $response->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────

    public function test_destroy_deletes_review(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 3,
            'comment' => 'Para deletar',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->deleteJson("/api/reviews/{$review->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_destroy_forbids_other_user(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 3,
            'comment' => 'Tentativa',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/reviews/{$review->id}");

        $response->assertForbidden();
    }

    // ── Block checks ──────────────────────────────────────────

    public function test_store_blocked_user_cannot_review(): void
    {
        $this->user->blockedUsers()->attach($this->reviewer->id);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/users/{$this->user->id}/reviews", [
                'stars' => 5,
                'comment' => 'Bloqueado tentando avaliar',
            ]);

        $response->assertForbidden();
    }

    public function test_index_excludes_blocked_reviewer(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'reviewer_id' => $this->reviewer->id,
            'stars' => 5,
            'comment' => 'Avaliação de bloqueado',
        ]);

        $viewer = User::factory()->create(['is_private' => false]);
        $viewer->blockedUsers()->attach($this->reviewer->id);

        $response = $this->actingAs($viewer)
            ->getJson("/api/users/{$this->user->id}/reviews");

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertFalse($ids->contains($review->id));
    }
}
