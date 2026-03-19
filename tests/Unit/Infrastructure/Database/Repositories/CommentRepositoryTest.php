<?php

namespace Tests\Unit\Infrastructure\Database\Repositories;

use App\Domain\Comment\Entities\CommentEntity;
use App\Infrastructure\Database\Repositories\CommentRepository;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CommentRepository $repository;
    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CommentRepository();
        $this->user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->task = Task::factory()->create(['project_id' => $project->id]);
    }

    // --- findAllByTaskId ---

    public function test_find_all_by_task_id_returns_only_task_comments(): void
    {
        $otherTask = Task::factory()->create(['project_id' => $this->task->project_id]);

        Comment::factory()->create(['task_id' => $this->task->id, 'user_id' => $this->user->id, 'body' => 'Comment A']);
        Comment::factory()->create(['task_id' => $otherTask->id, 'user_id' => $this->user->id, 'body' => 'Comment B']);

        $result = $this->repository->findAllByTaskId($this->task->id);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(CommentEntity::class, $result);
        $this->assertSame('Comment A', $result[0]->body);
    }

    public function test_find_all_by_task_id_returns_empty_when_no_comments(): void
    {
        $result = $this->repository->findAllByTaskId($this->task->id);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_find_all_by_task_id_returns_ordered_by_created_at_asc(): void
    {
        Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'Second',
            'created_at' => '2026-03-18 12:00:00',
        ]);
        Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'First',
            'created_at' => '2026-03-18 10:00:00',
        ]);

        $result = $this->repository->findAllByTaskId($this->task->id);

        $this->assertCount(2, $result);
        $this->assertSame('First', $result[0]->body);
        $this->assertSame('Second', $result[1]->body);
    }

    // --- findById ---

    public function test_find_by_id_returns_entity_when_exists(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'My comment',
        ]);

        $result = $this->repository->findById($comment->id);

        $this->assertInstanceOf(CommentEntity::class, $result);
        $this->assertSame($comment->id, $result->id);
        $this->assertSame($this->task->id, $result->taskId);
        $this->assertSame($this->user->id, $result->userId);
        $this->assertSame('My comment', $result->body);
        $this->assertNotNull($result->createdAt);
        $this->assertNotNull($result->updatedAt);
    }

    public function test_find_by_id_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    // --- save ---

    public function test_save_creates_comment_and_returns_entity_with_id(): void
    {
        $entity = new CommentEntity(null, $this->task->id, $this->user->id, 'New comment');

        $result = $this->repository->save($entity);

        $this->assertNotNull($result->id);
        $this->assertSame('New comment', $result->body);
        $this->assertNotNull($result->createdAt);
        $this->assertNotNull($result->updatedAt);

        $this->assertDatabaseHas('comments', [
            'id' => $result->id,
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'New comment',
        ]);
    }

    // --- update ---

    public function test_update_modifies_existing_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'Old body',
        ]);

        $entity = new CommentEntity(
            $comment->id,
            $this->task->id,
            $this->user->id,
            'Updated body',
            $comment->created_at->toIso8601String(),
            $comment->updated_at->toIso8601String(),
        );

        $result = $this->repository->update($entity);

        $this->assertSame('Updated body', $result->body);
        $this->assertNotNull($result->updatedAt);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Updated body',
        ]);
    }

    public function test_update_throws_when_comment_not_found(): void
    {
        $entity = new CommentEntity(999, $this->task->id, $this->user->id, 'Ghost');

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($entity);
    }

    // --- delete ---

    public function test_delete_removes_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $this->repository->delete($comment->id);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_delete_throws_when_comment_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete(999);
    }
}
