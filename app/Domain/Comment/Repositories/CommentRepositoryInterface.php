<?php

namespace App\Domain\Comment\Repositories;

use App\Domain\Comment\Entities\CommentEntity;

interface CommentRepositoryInterface
{
    /**
     * @return CommentEntity[]
     */
    public function findAllByTaskId(int $taskId): array;

    public function findById(int $id): ?CommentEntity;

    public function save(CommentEntity $data): CommentEntity;

    public function update(CommentEntity $data): CommentEntity;

    public function delete(int $id): void;
}
