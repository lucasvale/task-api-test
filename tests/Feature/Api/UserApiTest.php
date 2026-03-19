<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // --- POST /api/users (public) ---

    public function test_create_user_returns_201(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('data.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_create_user_hashes_password(): void
    {
        $this->postJson('/api/users', [
            'name' => 'User',
            'email' => 'hashed@example.com',
            'password' => 'plain-text',
        ]);

        $user = User::where('email', 'hashed@example.com')->first();
        $this->assertTrue(Hash::check('plain-text', $user->password));
    }

    // --- GET /api/users ---

    public function test_list_users_returns_all_users(): void
    {
        User::factory()->count(2)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data'); // 2 + setUp user
    }

    public function test_list_users_without_auth_returns_401(): void
    {
        $this->getJson('/api/users')->assertStatus(401);
    }

    // --- GET /api/users/{id} ---

    public function test_show_user_returns_user(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/users/{$this->user->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $this->user->id);
    }

    public function test_show_user_not_found_returns_error(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/users/999');

        $response->assertStatus(404);
    }

    // --- PUT /api/users/{id} ---

    public function test_update_user_modifies_data(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/users/999', [
                'name' => 'Name',
                'email' => 'e@e.com',
            ]);

        $response->assertStatus(404);
    }

    // --- DELETE /api/users/{id} ---

    public function test_delete_user_removes_from_database(): void
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/users/{$target->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_delete_nonexistent_user_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/users/999');

        $response->assertStatus(404);
    }
}
