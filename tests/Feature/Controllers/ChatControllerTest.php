<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that chat index page loads for authenticated users.
     */
    public function test_chat_index_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/chats');

        $response->assertStatus(200);
    }

    /**
     * Test that chat show page loads for authenticated users.
     */
    public function test_chat_show_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/chats/{$chat->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that gallery page loads for authenticated users.
     */
    public function test_chat_gallery_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/chats/{$chat->id}/gallery");

        $response->assertStatus(200);
    }

    /**
     * Test that unauthenticated users are redirected.
     */
    public function test_unauthenticated_users_redirected(): void
    {
        $response = $this->get('/chats');

        $response->assertRedirect('/login');
    }
}
