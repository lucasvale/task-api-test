<?php

namespace App\Domain\Comments\Repositories;

interface CommentRepositoryInterface
{
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function find(int $id);
    public function findAll();
    public function findByUserId(int $user_id);
    public function findByTaskId(int $task_id);
}
