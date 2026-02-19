<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $city->id, 'is_private' => false]);
        $this->target = User::factory()->create(['city_id' => $city->id, 'is_private' => false]);
    }

    // ── Follow / Unfollow ─────────────────────────────────────

    public function test_follow_public_user(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/users/follow/{$this->target->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('followers', [
            'followed_id' => $this->target->id,
            'follower_id' => $this->user->id,
            'status' => 'accepted',
        ]);
    }

    public function test_follow_private_user_creates_request(): void
    {
        $this->target->update(['is_private' => true]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/users/follow/{$this->target->id}");

        $response->assertOk();

        $this->assertDatabaseHas('followers', [
            'followed_id' => $this->target->id,
            'follower_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_unfollow_user(): void
    {
        $this->target->followers()->attach($this->user->id, ['status' => 'accepted']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/users/follow/{$this->target->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('followers', [
            'followed_id' => $this->target->id,
            'follower_id' => $this->user->id,
        ]);
    }

    public function test_follow_requires_auth(): void
    {
        $response = $this->postJson("/api/users/follow/{$this->target->id}");

        $response->assertUnauthorized();
    }

    public function test_follow_blocked_user_fails(): void
    {
        $this->target->blockedUsers()->attach($this->user->id);

        $response = $this->actingAs($this->user)
            ->postJson("/api/users/follow/{$this->target->id}");

        $response->assertForbidden();
    }

    // ── Pending followers ─────────────────────────────────────

    public function test_pending_followers_list(): void
    {
        $this->user->update(['is_private' => true]);
        $this->user->pendingFollowers()->attach($this->target->id, ['status' => 'pending']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/pending-followers');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($this->target->id));
    }

    // ── Accept follower ───────────────────────────────────────

    public function test_accept_follower(): void
    {
        $this->user->update(['is_private' => true]);
        $this->user->pendingFollowers()->attach($this->target->id, ['status' => 'pending']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/users/accept-follower/{$this->target->id}");

        $response->assertOk();

        $this->assertDatabaseHas('followers', [
            'followed_id' => $this->user->id,
            'follower_id' => $this->target->id,
            'status' => 'accepted',
        ]);
    }

    // ── Reject follower ───────────────────────────────────────

    public function test_reject_follower(): void
    {
        $this->user->update(['is_private' => true]);
        $this->user->pendingFollowers()->attach($this->target->id, ['status' => 'pending']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/users/reject-follower/{$this->target->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('followers', [
            'followed_id' => $this->user->id,
            'follower_id' => $this->target->id,
        ]);
    }
}
