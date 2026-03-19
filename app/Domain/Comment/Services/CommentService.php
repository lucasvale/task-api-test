<?php

namespace App\Domain\Comment\Services;

use App\Application\Comment\DTOs\CreateCommentRequestDto;
use App\Application\Comment\DTOs\UpdateCommentRequestDto;
use App\Domain\Comment\Entities\CommentEntity;
use App\Infrastructure\Database\Repositories\CommentRepository;
use App\Infrastructure\Database\Repositories\TaskRepository;
use App\Infrastructure\Database\Repositories\ProjectRepository;

readonly class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
    ) {
    }

    /**
     * @return CommentEntity[]
     */
    public function listComments(int $taskId, int $userId): array
    {
        $this->ensureTaskAccess($taskId, $userId);

        return $this->commentRepository->findAllByTaskId($taskId);
    }

    public function getComment(int $id, int $userId): CommentEntity
    {
        $comment = $this->commentRepository->findById($id);

        if (!$comment) {
            throw new \RuntimeException('Comment not found.');
        }

        $this->ensureTaskAccess($comment->taskId, $userId);

        return $comment;
    }

    public function createComment(CreateCommentRequestDto $dto, int $taskId, int $userId): CommentEntity
    {
        $this->ensureTaskAccess($taskId, $userId);

        $entity = CommentEntity::fromCreateDto($dto, $taskId, $userId);

        return $this->commentRepository->save($entity);
    }

    public function updateComment(int $id, UpdateCommentRequestDto $dto, int $userId): CommentEntity
    {
        $comment = $this->commentRepository->findById($id);

        if (!$comment) {
            throw new \RuntimeException('Comment not found.');
        }

        if ($comment->userId !== $userId) {
            throw new \RuntimeException('Comment not found.');
        }

        $comment->applyUpdate($dto);

        return $this->commentRepository->update($comment);
    }

    public function deleteComment(int $id, int $userId): void
    {
        $comment = $this->commentRepository->findById($id);

        if (!$comment) {
            throw new \RuntimeException('Comment not found.');
        }

        if ($comment->userId !== $userId) {
            throw new \RuntimeException('Comment not found.');
        }

        $this->commentRepository->delete($id);
    }

    private function ensureTaskAccess(int $taskId, int $userId): void
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new \RuntimeException('Task not found.');
        }

        $project = $this->projectRepository->findById($task->projectId);

        if (!$project || $project->userId !== $userId) {
            throw new \RuntimeException('Task not found.');
        }
    }
}
