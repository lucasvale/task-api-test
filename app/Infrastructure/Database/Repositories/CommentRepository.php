<?php

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Comment\Entities\CommentEntity;
use App\Domain\Comment\Repositories\CommentRepositoryInterface;
use App\Models\Comment;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @return CommentEntity[]
     */
    public function findAllByTaskId(int $taskId): array
    {
        return Comment::where('task_id', $taskId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (Comment $comment) => CommentEntity::fromModel($comment))
            ->toArray();
    }

    public function findById(int $id): ?CommentEntity
    {
        $comment = Comment::find($id);

        return $comment ? CommentEntity::fromModel($comment) : null;
    }

    public function save(CommentEntity $data): CommentEntity
    {
        $comment = Comment::create([
            'task_id' => $data->taskId,
            'user_id' => $data->userId,
            'body' => $data->body,
        ]);

        $data->id = $comment->id;
        $data->createdAt = $comment->created_at->toIso8601String();
        $data->updatedAt = $comment->updated_at->toIso8601String();

        return $data;
    }

    public function update(CommentEntity $data): CommentEntity
    {
        $comment = Comment::findOrFail($data->id);

        $comment->update([
            'body' => $data->body,
        ]);

        $data->updatedAt = $comment->updated_at->toIso8601String();

        return $data;
    }

    public function delete(int $id): void
    {
        Comment::findOrFail($id)->delete();
    }
}
