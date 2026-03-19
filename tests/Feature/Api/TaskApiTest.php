<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    private function taskUrl(int $taskId = null): string
    {
        $base = "/api/projects/{$this->project->id}/tasks";
        return $taskId ? "{$base}/{$taskId}" : $base;
    }

    // --- POST ---

    public function test_create_task_returns_201(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'New Task',
                'description' => 'Desc',
                'status' => 'todo',
                'due_date' => '2026-04-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'New Task')
            ->assertJsonPath('data.project_id', $this->project->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_create_task_on_other_users_project_returns_404(): void
    {
        $other = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/projects/{$otherProject->id}/tasks", [
                'title' => 'Hacked Task',
            ]);

        $response->assertStatus(404);
    }

    public function test_create_task_without_auth_returns_401(): void
    {
        $this->postJson($this->taskUrl(), ['title' => 'Test'])->assertStatus(401);
    }

    // --- GET list ---

    public function test_list_tasks_returns_project_tasks(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Task A']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Task B']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_list_tasks_filters_by_status(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'status' => 'todo']);
        Task::factory()->create(['project_id' => $this->project->id, 'status' => 'done']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl() . '?status=todo');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'todo');
    }

    public function test_list_tasks_filters_by_due_date_range(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Early', 'due_date' => '2026-01-01']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Mid', 'due_date' => '2026-03-15']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Late', 'due_date' => '2026-06-01']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl() . '?due_date_from=2026-02-01&due_date_to=2026-04-01');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mid');
    }

    // --- GET show ---

    public function test_show_task_returns_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl($task->id));

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_show_nonexistent_task_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl(999));

        $response->assertStatus(404);
    }

    public function test_show_task_of_other_users_project_returns_404(): void
    {
        $other = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $other->id]);
        $task = Task::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/projects/{$otherProject->id}/tasks/{$task->id}");

        $response->assertStatus(404);
    }

    // --- PUT ---

    public function test_update_task_modifies_data(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Old',
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->taskUrl($task->id), [
                'title' => 'Updated',
                'status' => 'in-progress',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated')
            ->assertJsonPath('data.status', 'in-progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated',
            'status' => 'in-progress',
        ]);
    }

    public function test_update_nonexistent_task_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->taskUrl(999), ['title' => 'X']);

        $response->assertStatus(404);
    }

    // --- DELETE ---

    public function test_delete_task_removes_from_database(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->taskUrl($task->id));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_delete_nonexistent_task_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->taskUrl(999));

        $response->assertStatus(404);
    }
}
