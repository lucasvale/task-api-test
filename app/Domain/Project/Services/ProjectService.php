<?php

namespace App\Domain\Project\Services;

use App\Application\Project\DTOs\CreateProjectRequestDto;
use App\Application\Project\DTOs\UpdateProjectRequestDto;
use App\Domain\Project\Entities\ProjectEntity;
use App\Infrastructure\Database\Repositories\ProjectRepository;

readonly class ProjectService
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    /**
     * @return ProjectEntity[]
     */
    public function listProjects(int $userId): array
    {
        return $this->projectRepository->findAllByUserId($userId);
    }

    public function getProject(int $id, int $userId): ProjectEntity
    {
        $project = $this->projectRepository->findById($id);

        if (!$project) {
            throw new \RuntimeException('Project not found.');
        }

        if ($project->userId !== $userId) {
            throw new \RuntimeException('Project not found.');
        }

        return $project;
    }

    public function createProject(CreateProjectRequestDto $dto, int $userId): ProjectEntity
    {
        $entity = ProjectEntity::fromCreateDto($dto, $userId);

        return $this->projectRepository->save($entity);
    }

    public function updateProject(int $id, UpdateProjectRequestDto $dto, int $userId): ProjectEntity
    {
        $project = $this->projectRepository->findById($id);

        if (!$project) {
            throw new \RuntimeException('Project not found.');
        }

        if ($project->userId !== $userId) {
            throw new \RuntimeException('Project not found.');
        }

        $project->applyUpdate($dto);

        return $this->projectRepository->update($project);
    }

    public function deleteProject(int $id, int $userId): void
    {
        $project = $this->projectRepository->findById($id);

        if (!$project) {
            throw new \RuntimeException('Project not found.');
        }

        if ($project->userId !== $userId) {
            throw new \RuntimeException('Project not found.');
        }

        $this->projectRepository->delete($id);
    }
}
