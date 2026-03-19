<?php

namespace App\Infrastructure\Database\Repositories;

use App\Application\Task\DTOs\TaskFiltersDto;
use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Models\Task;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * @return TaskEntity[]
     */
    public function findAllByProjectId(int $projectId, TaskFiltersDto $filters): array
    {
        $query = Task::where('project_id', $projectId);

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->due_date_from !== null) {
            $query->where('due_date', '>=', $filters->due_date_from);
        }

        if ($filters->due_date_to !== null) {
            $query->where('due_date', '<=', $filters->due_date_to);
        }

        if ($filters->search !== null) {
            $query->whereFullText(['title', 'description'], $filters->search);
        }

        return $query->get()
            ->map(fn (Task $task) => TaskEntity::fromModel($task))
            ->toArray();
    }

    public function findById(int $id): ?TaskEntity
    {
        $task = Task::find($id);

        return $task ? TaskEntity::fromModel($task) : null;
    }

    public function save(TaskEntity $data): TaskEntity
    {
        $task = Task::create([
            'project_id' => $data->projectId,
            'assigned_to' => $data->assignedTo,
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'due_date' => $data->dueDate,
        ]);

        $data->id = $task->id;

        return $data;
    }

    public function update(TaskEntity $data): TaskEntity
    {
        $task = Task::findOrFail($data->id);

        $task->update([
            'assigned_to' => $data->assignedTo,
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'due_date' => $data->dueDate,
        ]);

        return $data;
    }

    public function delete(int $id): void
    {
        Task::findOrFail($id)->delete();
    }
}
