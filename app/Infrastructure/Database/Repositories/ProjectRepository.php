<?php

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;
use App\Models\Project;

class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @return ProjectEntity[]
     */
    public function findAllByUserId(int $userId): array
    {
        return Project::where('user_id', $userId)
            ->get()
            ->map(fn (Project $project) => ProjectEntity::fromModel($project))
            ->toArray();
    }

    public function findById(int $id): ?ProjectEntity
    {
        $project = Project::find($id);

        return $project ? ProjectEntity::fromModel($project) : null;
    }

    public function save(ProjectEntity $data): ProjectEntity
    {
        $project = Project::create([
            'user_id' => $data->userId,
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $data->id = $project->id;

        return $data;
    }

    public function update(ProjectEntity $data): ProjectEntity
    {
        $project = Project::findOrFail($data->id);

        $project->update([
            'name' => $data->name,
            'description' => $data->description,
        ]);

        return $data;
    }

    public function delete(int $id): void
    {
        Project::findOrFail($id)->delete();
    }
}
