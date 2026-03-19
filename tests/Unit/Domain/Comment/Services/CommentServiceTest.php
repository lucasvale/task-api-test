<?php

namespace Tests\Unit\Domain\Comment\Services;

use App\Application\Comment\DTOs\CreateCommentRequestDto;
use App\Application\Comment\DTOs\UpdateCommentRequestDto;
use App\Domain\Comment\Entities\CommentEntity;
use App\Domain\Comment\Services\CommentService;
use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Task\Entities\TaskEntity;
use App\Infrastructure\Database\Repositories\CommentRepository;
use App\Infrastructure\Database\Repositories\ProjectRepository;
use App\Infrastructure\Database\Repositories\TaskRepository;
use Mockery;
use Tests\TestCase;

class CommentServiceTest extends TestCase
{
    private CommentRepository|Mockery\MockInterface $commentRepository;
    private TaskRepository|Mockery\MockInterface $taskRepository;
    private ProjectRepository|Mockery\MockInterface $projectRepository;
    private CommentService $commentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commentRepository = Mockery::mock(CommentRepository::class);
        $this->taskRepository = Mockery::mock(TaskRepository::class);
        $this->projectRepository = Mockery::mock(ProjectRepository::class);
        $this->commentService = new CommentService(
            $this->commentRepository,
            $this->taskRepository,
            $this->projectRepository,
        );
    }

    private function mockTaskAccess(int $taskId, int $projectId, int $userId): void
    {
        $this->taskRepository
            ->shouldReceive('findById')
            ->with($taskId)
            ->andReturn(new TaskEntity($taskId, $projectId, null, 'Task', null, 'todo', null));

        $this->projectRepository
            ->shouldReceive('findById')
            ->with($projectId)
            ->andReturn(new ProjectEntity($projectId, $userId, 'Project', null));
    }

    private function mockTaskNotFound(int $taskId): void
    {
        $this->taskRepository
            ->shouldReceive('findById')
            ->with($taskId)
            ->andReturn(null);
    }

    private function mockTaskOwnedByOther(int $taskId, int $projectId, int $otherUserId): void
    {
        $this->taskRepository
            ->shouldReceive('findById')
            ->with($taskId)
            ->andReturn(new TaskEntity($taskId, $projectId, null, 'Task', null, 'todo', null));

        $this->projectRepository
            ->shouldReceive('findById')
            ->with($projectId)
            ->andReturn(new ProjectEntity($projectId, $otherUserId, 'Project', null));
    }

    // --- listComments ---

    public function test_list_comments_returns_comments_for_task(): void
    {
        $this->mockTaskAccess(1, 5, 10);

        $entities = [
            new CommentEntity(1, 1, 10, 'Comment A', '2026-03-18T00:00:00+00:00', '2026-03-18T00:00:00+00:00'),
            new CommentEntity(2, 1, 10, 'Comment B', '2026-03-18T00:00:00+00:00', '2026-03-18T00:00:00+00:00'),
        ];

        $this->commentRepository
            ->shouldReceive('findAllByTaskId')
            ->with(1)
            ->once()
            ->andReturn($entities);

        $result = $this->commentService->listComments(1, 10);

        $this->assertCount(2, $result);
        $this->assertSame('Comment A', $result[0]->body);
        $this->assertSame('Comment B', $result[1]->body);
    }

    public function test_list_comments_returns_empty_array(): void
    {
        $this->mockTaskAccess(1, 5, 10);

        $this->commentRepository
            ->shouldReceive('findAllByTaskId')
            ->with(1)
            ->once()
            ->andReturn([]);

        $result = $this->commentService->listComments(1, 10);

        $this->assertCount(0, $result);
    }

    public function test_list_comments_throws_when_task_not_found(): void
    {
        $this->mockTaskNotFound(999);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->commentService->listComments(999, 10);
    }

    public function test_list_comments_throws_when_user_does_not_own_project(): void
    {
        $this->mockTaskOwnedByOther(1, 5, 20);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->commentService->listComments(1, 10);
    }

    // --- getComment ---

    public function test_get_comment_returns_entity(): void
    {
        $comment = new CommentEntity(1, 3, 10, 'A comment', '2026-03-18T00:00:00+00:00', '2026-03-18T00:00:00+00:00');

        $this->commentRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($comment);

        $this->mockTaskAccess(3, 5, 10);

        $result = $this->commentService->getComment(1, 10);

        $this->assertSame(1, $result->id);
        $this->assertSame('A comment', $result->body);
    }

    public function test_get_comment_throws_when_not_found(): void
    {
        $this->commentRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->commentService->getComment(999, 10);
    }

    public function test_get_comment_throws_when_user_does_not_own_project(): void
    {
        $comment = new CommentEntity(1, 3, 10, 'A comment');

        $this->commentRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($comment);

        $this->mockTaskOwnedByOther(3, 5, 20);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->commentService->getComment(1, 10);
    }

    // --- createComment ---

    public function test_create_comment_saves_and_returns_entity(): void
    {
        $this->mockTaskAccess(3, 5, 10);

        $dto = new CreateCommentRequestDto(body: 'New comment');

        $this->commentRepository
            ->shouldReceive('save')
            ->once()
            ->withArgs(function (CommentEntity $entity) {
                return $entity->body === 'New comment'
                    && $entity->taskId === 3
                    && $entity->userId === 10
                    && $entity->id === null;
            })
            ->andReturnUsing(function (CommentEntity $entity) {
                $entity->id = 1;
                $entity->createdAt = '2026-03-18T00:00:00+00:00';
                $entity->updatedAt = '2026-03-18T00:00:00+00:00';
                return $entity;
            });

        $result = $this->commentService->createComment($dto, 3, 10);

        $this->assertSame(1, $result->id);
        $this->assertSame('New comment', $result->body);
        $this->assertSame(10, $result->userId);
    }

    public function test_create_comment_throws_when_task_not_found(): void
    {
        $this->mockTaskNotFound(999);

        $dto = new CreateCommentRequestDto(body: 'Comment');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->commentService->createComment($dto, 999, 10);
    }

    // --- updateComment ---

    public function test_update_comment_applies_changes_and_returns(): void
    {
        $existing = new CommentEntity(1, 3, 10, 'Old body', '2026-03-18T00:00:00+00:00', '2026-03-18T00:00:00+00:00');

        $this->commentRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $dto = new UpdateCommentRequestDto(body: 'Updated body');

        $this->commentRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(fn (CommentEntity $entity) => $entity->body === 'Updated body')
            ->andReturnUsing(fn (CommentEntity $entity) => $entity);

        $result = $this->commentService->updateComment(1, $dto, 10);

        $this->assertSame('Updated body', $result->body);
    }

    public function test_update_comment_throws_when_not_found(): void
    {
        $this->commentRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $dto = new UpdateCommentRequestDto(body: 'Body');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->commentService->updateComment(999, $dto, 10);
    }

    public function test_update_comment_throws_when_user_is_not_author(): void
    {
        $existing = new CommentEntity(1, 3, 20, 'Body');

        $this->commentRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $dto = new UpdateCommentRequestDto(body: 'Updated');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->commentService->updateComment(1, $dto, 10);
    }

    // --- deleteComment ---

    public function test_delete_comment_calls_repository(): void
    {
        $existing = new CommentEntity(1, 3, 10, 'Body');

        $this->commentRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->commentRepository
            ->shouldReceive('delete')
            ->with(1)
            ->once();

        $this->commentService->deleteComment(1, 10);
    }

    public function test_delete_comment_throws_when_not_found(): void
    {
        $this->commentRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->commentService->deleteComment(999, 10);
    }

    public function test_delete_comment_throws_when_user_is_not_author(): void
    {
        $existing = new CommentEntity(1, 3, 20, 'Body');

        $this->commentRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->commentService->deleteComment(1, 10);
    }
}
