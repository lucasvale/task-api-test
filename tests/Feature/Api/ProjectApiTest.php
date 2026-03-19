<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // --- POST /api/projects ---

    public function test_create_project_returns_201(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/projects', [
                'name' => 'My Project',
                'description' => 'A description',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'My Project')
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('projects', [
            'name' => 'My Project',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_project_without_auth_returns_401(): void
    {
        $this->postJson('/api/projects', ['name' => 'Test'])->assertStatus(401);
    }

    // --- GET /api/projects ---

    public function test_list_projects_returns_only_own_projects(): void
    {
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Mine']);
        Project::factory()->create(['name' => 'Other']); // other user

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    // --- GET /api/projects/{id} ---

    public function test_show_project_returns_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_show_project_of_other_user_returns_404(): void
    {
        $other = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(404);
    }

    public function test_show_nonexistent_project_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/projects/999');

        $response->assertStatus(404);
    }

    // --- PUT /api/projects/{id} ---

    public function test_update_project_modifies_data(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Updated',
                'description' => 'New desc',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated',
        ]);
    }

    public function test_update_project_of_other_user_returns_404(): void
    {
        $other = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(404);
    }

    // --- DELETE /api/projects/{id} ---

    public function test_delete_project_removes_from_database(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_delete_project_of_other_user_returns_404(): void
    {
        $other = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(404);
    }
}
