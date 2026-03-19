<?php

namespace App\Domain\Task\Repositories;

use App\Application\Task\DTOs\TaskFiltersDto;
use App\Domain\Task\Entities\TaskEntity;

interface TaskRepositoryInterface
{
    /**
     * @return TaskEntity[]
     */
    public function findAllByProjectId(int $projectId, TaskFiltersDto $filters): array;

    public function findById(int $id): ?TaskEntity;

    public function save(TaskEntity $data): TaskEntity;

    public function update(TaskEntity $data): TaskEntity;

    public function delete(int $id): void;
}
