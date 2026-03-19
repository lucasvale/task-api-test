<?php

namespace App\Domain\Project\Repositories;

use App\Domain\Project\Entities\ProjectEntity;

interface ProjectRepositoryInterface
{
    /**
     * @return ProjectEntity[]
     */
    public function findAllByUserId(int $userId): array;

    public function findById(int $id): ?ProjectEntity;

    public function save(ProjectEntity $data): ProjectEntity;

    public function update(ProjectEntity $data): ProjectEntity;

    public function delete(int $id): void;
}
