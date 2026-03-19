<?php

namespace App\Domain\Task\Services;

use App\Application\Task\DTOs\CreateTaskRequestDto;
use App\Application\Task\DTOs\TaskFiltersDto;
use App\Application\Task\DTOs\UpdateTaskRequestDto;
use App\Domain\Task\Entities\TaskEntity;
use App\Infrastructure\Database\Repositories\ProjectRepository;
use App\Infrastructure\Database\Repositories\TaskRepository;
use App\Notifications\TaskCreated;
use App\Notifications\TaskUpdated;
use App\Models\User;
use App\Domain\Project\Entities\ProjectEntity;
use Illuminate\Support\Facades\Cache;

readonly class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
    ) {
    }

    /**
     * @return TaskEntity[]
     */
    public function listTasks(int $projectId, int $userId, TaskFiltersDto $filters): array
    {
        $this->ensureProjectOwnership($projectId, $userId);

        $cacheKey = "tasks:project:{$projectId}:" . md5(serialize($filters));

        return Cache::store('redis')->remember($cacheKey, 300, function () use ($projectId, $filters) {
            return $this->taskRepository->findAllByProjectId($projectId, $filters);
        });
    }

    public function getTask(int $id, int $userId): TaskEntity
    {
        $task = $this->taskRepository->findById($id);

        if (!$task) {
            throw new \RuntimeException('Task not found.');
        }

        $this->ensureProjectOwnership($task->projectId, $userId);

        return $task;
    }

    public function createTask(CreateTaskRequestDto $dto, int $projectId, int $userId): TaskEntity
    {
        $project = $this->ensureProjectOwnership($projectId, $userId);

        $entity = TaskEntity::fromCreateDto($dto, $projectId);
        $savedTask = $this->taskRepository->save($entity);

        $this->clearProjectTasksCache($projectId);

        if (!empty($savedTask->assignedTo)) {
            $this->notifyAssignee($savedTask->assignedTo, new TaskCreated($savedTask, $project->name));
        }

        return $savedTask;
    }

    public function updateTask(int $id, UpdateTaskRequestDto $dto, int $userId): TaskEntity
    {
        $task = $this->taskRepository->findById($id);

        if (!$task) {
            throw new \RuntimeException('Task not found.');
        }

        $project = $this->ensureProjectOwnership($task->projectId, $userId);

        $task->applyUpdate($dto);
        $updatedTask = $this->taskRepository->update($task);

        $this->clearProjectTasksCache($task->projectId);

        if (!empty($updatedTask->assignedTo)) {
            $this->notifyAssignee($updatedTask->assignedTo, new TaskUpdated($updatedTask, $project->name));
        }

        return $updatedTask;
    }

    public function deleteTask(int $id, int $userId): void
    {
        $task = $this->taskRepository->findById($id);

        if (!$task) {
            throw new \RuntimeException('Task not found.');
        }

        $this->ensureProjectOwnership($task->projectId, $userId);

        $this->taskRepository->delete($id);
        $this->clearProjectTasksCache($task->projectId);
    }

    private function ensureProjectOwnership(int $projectId, int $userId): ProjectEntity
    {
        $project = $this->projectRepository->findById($projectId);

        if (!$project || $project->userId !== $userId) {
            throw new \RuntimeException('Project not found.');
        }

        return $project;
    }

    private function clearProjectTasksCache(int $projectId): void
    {
        $redis = Cache::store('redis')->getStore()->connection();
        $prefix = config('cache.prefix') . ':';
        $pattern = $prefix . "tasks:project:{$projectId}:*";

        $keys = $redis->keys($pattern);
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }

    private function notifyAssignee(int $userId, object $notification): void
    {
        $assignee = User::find($userId);

        if ($assignee) {
            $assignee->notify($notification);
        }
    }
}
