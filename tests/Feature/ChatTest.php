<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::inRandomOrder()->first();
        $this->user = User::factory()->create(['city_id' => $city->id, 'is_private' => false]);
        $this->otherUser = User::factory()->create(['city_id' => $city->id, 'is_private' => false]);
    }

    // ── Conversations list ────────────────────────────────────

    public function test_index_returns_conversations(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->user)
            ->getJson('/api/conversations');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($conversation->id));
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson('/api/conversations');

        $response->assertUnauthorized();
    }

    public function test_index_excludes_blocked_conversations(): void
    {
        $this->createConversation();

        $this->user->blockedUsers()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user)
            ->getJson('/api/conversations');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));
    }

    // ── Store (create/get conversation) ───────────────────────

    public function test_store_creates_conversation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$this->otherUser->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $minId = min($this->user->id, $this->otherUser->id);
        $maxId = max($this->user->id, $this->otherUser->id);

        $this->assertDatabaseHas('conversations', [
            'user_one_id' => $minId,
            'user_two_id' => $maxId,
        ]);
    }

    public function test_store_returns_existing_conversation(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$this->otherUser->id}");

        $response->assertOk();
        $this->assertEquals($conversation->id, $response->json('data.id'));
    }

    public function test_store_prevents_self_conversation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$this->user->id}");

        $response->assertStatus(403);
    }

    public function test_store_blocked_user_cannot_start_conversation(): void
    {
        $this->otherUser->blockedUsers()->attach($this->user->id);

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$this->otherUser->id}");

        $response->assertForbidden();
    }

    // ── Show (messages) ───────────────────────────────────────

    public function test_show_returns_messages(): void
    {
        $conversation = $this->createConversation();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'Olá!',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/conversations/{$conversation->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $messages = $response->json('data.data');
        $this->assertNotEmpty($messages);
    }

    public function test_show_forbids_non_participant(): void
    {
        $conversation = $this->createConversation();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)
            ->getJson("/api/conversations/{$conversation->id}");

        $response->assertForbidden();
    }

    // ── Send message ──────────────────────────────────────────

    public function test_send_message_creates_message(): void
    {
        Event::fake();

        $conversation = $this->createConversation();

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => 'Oi, tudo bem?',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'Oi, tudo bem?',
        ]);
    }

    public function test_send_message_with_reply(): void
    {
        Event::fake();

        $conversation = $this->createConversation();

        $original = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->otherUser->id,
            'body' => 'Olá!',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => 'Respondendo...',
                'reply_to_id' => $original->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
            'reply_to_id' => $original->id,
        ]);
    }

    public function test_send_message_validates_body(): void
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$conversation->id}/messages", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_send_message_requires_auth(): void
    {
        $conversation = $this->createConversation();

        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'body' => 'Teste',
        ]);

        $response->assertUnauthorized();
    }

    // ── Mark as read ──────────────────────────────────────────

    public function test_mark_as_read(): void
    {
        Event::fake();

        $conversation = $this->createConversation();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->otherUser->id,
            'body' => 'Mensagem não lida',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$conversation->id}/read");

        $response->assertOk();

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->otherUser->id,
            'read_at' => null,
        ]);
    }

    // ── Delete message ────────────────────────────────────────

    public function test_delete_message(): void
    {
        $conversation = $this->createConversation();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'Para deletar',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/messages/{$message->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_delete_message_forbids_other_user(): void
    {
        $conversation = $this->createConversation();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'Mensagem do outro',
        ]);

        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/messages/{$message->id}");

        $response->assertForbidden();
    }

    // ── Helpers ───────────────────────────────────────────────

    private function createConversation(): Conversation
    {
        $minId = min($this->user->id, $this->otherUser->id);
        $maxId = max($this->user->id, $this->otherUser->id);

        return Conversation::create([
            'user_one_id' => $minId,
            'user_two_id' => $maxId,
        ]);
    }
}
