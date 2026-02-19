<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private User $sender;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $city->id]);
        $this->sender = User::factory()->create(['city_id' => $city->id]);
    }

    // ── Index ──────────────────────────────────────────────────

    public function test_index_returns_notifications(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_FOLLOW,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotEmpty($response->json('data.data'));
    }

    public function test_index_filters_by_type(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_FOLLOW,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_LIKE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?type=follow');

        $response->assertOk();

        $types = collect($response->json('data.data'))->pluck('type')->unique();
        $this->assertEquals(['follow'], $types->values()->all());
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    // ── Unread count ──────────────────────────────────────────

    public function test_unread_count_returns_counts(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_FOLLOW,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_LIKE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['total', 'like', 'comment', 'follow'],
            ]);

        $this->assertEquals(2, $response->json('data.total'));
        $this->assertEquals(1, $response->json('data.like'));
        $this->assertEquals(1, $response->json('data.follow'));
    }

    // ── Read single ───────────────────────────────────────────

    public function test_read_marks_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_FOLLOW,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    // ── Read all ──────────────────────────────────────────────

    public function test_read_all_marks_all_as_read(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_FOLLOW,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_LIKE,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        $response->assertOk();

        $unread = Notification::where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unread);
    }

    public function test_read_all_filters_by_type(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_FOLLOW,
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'sender_id' => $this->sender->id,
            'type' => Notification::TYPE_LIKE,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all?type=follow');

        $response->assertOk();

        $unreadFollow = Notification::where('user_id', $this->user->id)
            ->where('type', 'follow')
            ->whereNull('read_at')
            ->count();

        $unreadLike = Notification::where('user_id', $this->user->id)
            ->where('type', 'like')
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadFollow);
        $this->assertEquals(1, $unreadLike);
    }
}
