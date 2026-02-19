<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $city->id]);
    }

    // ── Delete account ────────────────────────────────────────

    public function test_delete_account_soft_deletes(): void
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson('/api/users/delete-account');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
    }

    public function test_delete_account_requires_auth(): void
    {
        $response = $this->deleteJson('/api/users/delete-account');

        $response->assertUnauthorized();
    }

    public function test_delete_account_revokes_tokens(): void
    {
        $token = $this->user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/users/delete-account');

        $this->assertEquals(0, $this->user->tokens()->count());
    }

    // ── Restore via login ─────────────────────────────────────

    public function test_login_restores_soft_deleted_account(): void
    {
        $password = 'password123';
        $this->user->update(['password' => $password]);
        $this->user->delete();

        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['restored' => true],
            ]);

        $this->assertNotSoftDeleted('users', ['id' => $this->user->id]);
    }

    public function test_login_rejects_expired_deleted_account(): void
    {
        $password = 'password123';
        $this->user->update(['password' => $password]);
        $this->user->delete();

        // Directly update deleted_at via DB to simulate 61 days ago
        DB::table('users')
            ->where('id', $this->user->id)
            ->update(['deleted_at' => now()->subDays(61)]);

        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => $password,
        ]);

        $response->assertUnauthorized();
    }

    // ── Purge expired ─────────────────────────────────────────

    public function test_purge_deletes_expired_accounts(): void
    {
        $this->user->delete();

        DB::table('users')
            ->where('id', $this->user->id)
            ->update(['deleted_at' => now()->subDays(61)]);

        $service = app(UserService::class);
        $count = $service->permanentlyDeleteExpired();

        $this->assertEquals(1, $count);
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    public function test_purge_keeps_recent_deletions(): void
    {
        $this->user->delete();

        $service = app(UserService::class);
        $count = $service->permanentlyDeleteExpired();

        $this->assertEquals(0, $count);

        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }
}
