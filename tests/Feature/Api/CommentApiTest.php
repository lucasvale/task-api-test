<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->task = Task::factory()->create(['project_id' => $this->project->id]);
    }

    private function commentUrl(int $commentId = null): string
    {
        $base = "/api/tasks/{$this->task->id}/comments";
        return $commentId ? "{$base}/{$commentId}" : $base;
    }

    // --- POST ---

    public function test_create_comment_returns_201(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->commentUrl(), [
                'body' => 'A comment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.body', 'A comment')
            ->assertJsonPath('data.task_id', $this->task->id)
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('comments', [
            'body' => 'A comment',
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_comment_on_task_of_other_user_returns_404(): void
    {
        $other = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $other->id]);
        $otherTask = Task::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/tasks/{$otherTask->id}/comments", [
                'body' => 'Hacked',
            ]);

        $response->assertStatus(404);
    }

    public function test_create_comment_without_auth_returns_401(): void
    {
        $this->postJson($this->commentUrl(), ['body' => 'Test'])->assertStatus(401);
    }

    // --- GET list ---

    public function test_list_comments_returns_task_comments(): void
    {
        Comment::factory()->create(['task_id' => $this->task->id, 'user_id' => $this->user->id, 'body' => 'First']);
        Comment::factory()->create(['task_id' => $this->task->id, 'user_id' => $this->user->id, 'body' => 'Second']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->commentUrl());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_list_comments_on_other_users_task_returns_404(): void
    {
        $other = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $other->id]);
        $otherTask = Task::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks/{$otherTask->id}/comments");

        $response->assertStatus(404);
    }

    // --- GET show ---

    public function test_show_comment_returns_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->commentUrl($comment->id));

        $response->assertOk()
            ->assertJsonPath('data.id', $comment->id)
            ->assertJsonPath('data.body', $comment->body);
    }

    public function test_show_nonexistent_comment_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->commentUrl(999));

        $response->assertStatus(404);
    }

    // --- PUT ---

    public function test_update_own_comment_modifies_body(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'Old body',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->commentUrl($comment->id), [
                'body' => 'Updated body',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.body', 'Updated body');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Updated body',
        ]);
    }

    public function test_update_comment_of_other_user_returns_404(): void
    {
        $other = User::factory()->create();
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $other->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->commentUrl($comment->id), [
                'body' => 'Hijacked',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_nonexistent_comment_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->commentUrl(999), ['body' => 'X']);

        $response->assertStatus(404);
    }

    // --- DELETE ---

    public function test_delete_own_comment_removes_from_database(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->commentUrl($comment->id));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_delete_comment_of_other_user_returns_404(): void
    {
        $other = User::factory()->create();
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $other->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->commentUrl($comment->id));

        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_comment_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->commentUrl(999));

        $response->assertStatus(404);
    }
}
