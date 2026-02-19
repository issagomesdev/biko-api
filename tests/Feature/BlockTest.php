<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BlockTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $city->id]);
        $this->target = User::factory()->create(['city_id' => $city->id]);
    }

    // ── Block ─────────────────────────────────────────────────

    public function test_block_user(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/users/block/{$this->target->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('blocks', [
            'user_id' => $this->user->id,
            'blocked_user_id' => $this->target->id,
        ]);
    }

    public function test_block_prevents_self_block(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/users/block/{$this->user->id}");

        $response->assertUnprocessable();
    }

    public function test_block_requires_auth(): void
    {
        $response = $this->postJson("/api/users/block/{$this->target->id}");

        $response->assertUnauthorized();
    }

    // ── Unblock ───────────────────────────────────────────────

    public function test_unblock_user(): void
    {
        $this->user->blockedUsers()->attach($this->target->id);

        $response = $this->actingAs($this->user)
            ->postJson("/api/users/unblock/{$this->target->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('blocks', [
            'user_id' => $this->user->id,
            'blocked_user_id' => $this->target->id,
        ]);
    }

    // ── List blocked ──────────────────────────────────────────

    public function test_list_blocked_users(): void
    {
        $this->user->blockedUsers()->attach($this->target->id);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/blocked');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($this->target->id));
    }

    public function test_list_blocked_requires_auth(): void
    {
        $response = $this->getJson('/api/users/blocked');

        $response->assertUnauthorized();
    }

    // ── Block hides from user listing ─────────────────────────

    public function test_blocked_user_hidden_from_listing(): void
    {
        $this->user->blockedUsers()->attach($this->target->id);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users');

        $response->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertFalse($ids->contains($this->target->id));
    }

    // ── Block shows minimal profile ───────────────────────────

    public function test_blocked_user_sees_minimal_profile(): void
    {
        $this->target->blockedUsers()->attach($this->user->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->target->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.is_blocked'));
    }
}
