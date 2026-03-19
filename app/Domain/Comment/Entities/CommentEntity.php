<?php

namespace App\Domain\Comment\Entities;

use App\Application\Comment\DTOs\CreateCommentRequestDto;
use App\Application\Comment\DTOs\UpdateCommentRequestDto;
use App\Models\Comment;

class CommentEntity
{
    public function __construct(
        public ?int $id,
        public int $taskId,
        public int $userId,
        public string $body,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    public static function fromCreateDto(CreateCommentRequestDto $dto, int $taskId, int $userId): self
    {
        return new self(
            null,
            $taskId,
            $userId,
            $dto->body,
        );
    }

    public static function fromModel(Comment $comment): self
    {
        return new self(
            $comment->id,
            $comment->task_id,
            $comment->user_id,
            $comment->body,
            $comment->created_at?->toIso8601String(),
            $comment->updated_at?->toIso8601String(),
        );
    }

    public function applyUpdate(UpdateCommentRequestDto $dto): void
    {
        $this->body = $dto->body;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->taskId,
            'user_id' => $this->userId,
            'body' => $this->body,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
