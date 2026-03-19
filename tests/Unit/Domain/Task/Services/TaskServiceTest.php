<?php

namespace Tests\Unit\Domain\Task\Services;

use App\Application\Task\DTOs\CreateTaskRequestDto;
use App\Application\Task\DTOs\TaskFiltersDto;
use App\Application\Task\DTOs\UpdateTaskRequestDto;
use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Services\TaskService;
use App\Infrastructure\Database\Repositories\ProjectRepository;
use App\Infrastructure\Database\Repositories\TaskRepository;
use Mockery;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskRepository|Mockery\MockInterface $taskRepository;
    private ProjectRepository|Mockery\MockInterface $projectRepository;
    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = Mockery::mock(TaskRepository::class);
        $this->projectRepository = Mockery::mock(ProjectRepository::class);
        $this->taskService = new TaskService($this->taskRepository, $this->projectRepository);
    }

    private function mockProjectOwnership(int $projectId, int $userId): void
    {
        $this->projectRepository
            ->shouldReceive('findById')
            ->with($projectId)
            ->andReturn(new ProjectEntity($projectId, $userId, 'Project', null));
    }

    private function mockProjectNotFound(int $projectId): void
    {
        $this->projectRepository
            ->shouldReceive('findById')
            ->with($projectId)
            ->andReturn(null);
    }

    private function mockProjectOwnedByOther(int $projectId, int $otherUserId): void
    {
        $this->projectRepository
            ->shouldReceive('findById')
            ->with($projectId)
            ->andReturn(new ProjectEntity($projectId, $otherUserId, 'Project', null));
    }

    // --- listTasks ---

    public function test_list_tasks_returns_tasks_for_project(): void
    {
        $this->mockProjectOwnership(1, 10);

        $entities = [
            new TaskEntity(1, 1, null, 'Task A', null, 'todo', null),
            new TaskEntity(2, 1, null, 'Task B', null, 'done', null),
        ];

        $filters = new TaskFiltersDto();

        $this->taskRepository
            ->shouldReceive('findAllByProjectId')
            ->with(1, $filters)
            ->once()
            ->andReturn($entities);

        $result = $this->taskService->listTasks(1, 10, $filters);

        $this->assertCount(2, $result);
        $this->assertSame('Task A', $result[0]->title);
        $this->assertSame('Task B', $result[1]->title);
    }

    public function test_list_tasks_returns_empty_array(): void
    {
        $this->mockProjectOwnership(1, 10);

        $filters = new TaskFiltersDto();

        $this->taskRepository
            ->shouldReceive('findAllByProjectId')
            ->with(1, $filters)
            ->once()
            ->andReturn([]);

        $result = $this->taskService->listTasks(1, 10, $filters);

        $this->assertCount(0, $result);
    }

    public function test_list_tasks_passes_filters_to_repository(): void
    {
        $this->mockProjectOwnership(1, 10);

        $filters = new TaskFiltersDto(status: 'todo', due_date_from: '2026-01-01');

        $this->taskRepository
            ->shouldReceive('findAllByProjectId')
            ->with(1, $filters)
            ->once()
            ->andReturn([]);

        $this->taskService->listTasks(1, 10, $filters);
    }

    public function test_list_tasks_throws_when_project_not_found(): void
    {
        $this->mockProjectNotFound(999);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->taskService->listTasks(999, 10, new TaskFiltersDto());
    }

    public function test_list_tasks_throws_when_user_does_not_own_project(): void
    {
        $this->mockProjectOwnedByOther(1, 20);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->taskService->listTasks(1, 10, new TaskFiltersDto());
    }

    // --- getTask ---

    public function test_get_task_returns_entity(): void
    {
        $entity = new TaskEntity(1, 5, null, 'Task A', null, 'todo', null);

        $this->taskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($entity);

        $this->mockProjectOwnership(5, 10);

        $result = $this->taskService->getTask(1, 10);

        $this->assertSame(1, $result->id);
        $this->assertSame('Task A', $result->title);
    }

    public function test_get_task_throws_when_not_found(): void
    {
        $this->taskRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->taskService->getTask(999, 10);
    }

    public function test_get_task_throws_when_user_does_not_own_project(): void
    {
        $entity = new TaskEntity(1, 5, null, 'Task A', null, 'todo', null);

        $this->taskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($entity);

        $this->mockProjectOwnedByOther(5, 20);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->taskService->getTask(1, 10);
    }

    // --- createTask ---

    public function test_create_task_saves_and_returns_entity(): void
    {
        $this->mockProjectOwnership(5, 10);

        $dto = new CreateTaskRequestDto(
            title: 'New Task',
            description: 'Desc',
            status: 'todo',
            assigned_to: 3,
            due_date: '2026-04-01',
        );

        $this->taskRepository
            ->shouldReceive('save')
            ->once()
            ->withArgs(function (TaskEntity $entity) {
                return $entity->title === 'New Task'
                    && $entity->projectId === 5
                    && $entity->assignedTo === 3
                    && $entity->status === 'todo'
                    && $entity->dueDate === '2026-04-01'
                    && $entity->id === null;
            })
            ->andReturnUsing(function (TaskEntity $entity) {
                $entity->id = 1;
                return $entity;
            });

        $result = $this->taskService->createTask($dto, 5, 10);

        $this->assertSame(1, $result->id);
        $this->assertSame('New Task', $result->title);
    }

    public function test_create_task_throws_when_project_not_found(): void
    {
        $this->mockProjectNotFound(999);

        $dto = new CreateTaskRequestDto(title: 'Task');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->taskService->createTask($dto, 999, 10);
    }

    // --- updateTask ---

    public function test_update_task_applies_changes_and_returns(): void
    {
        $existing = new TaskEntity(1, 5, null, 'Old Title', null, 'todo', null);

        $this->taskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->mockProjectOwnership(5, 10);

        $dto = new UpdateTaskRequestDto(
            title: 'Updated Title',
            description: 'Updated Desc',
            status: 'in-progress',
            assigned_to: 3,
            due_date: '2026-05-01',
        );

        $this->taskRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (TaskEntity $entity) {
                return $entity->title === 'Updated Title'
                    && $entity->status === 'in-progress'
                    && $entity->assignedTo === 3;
            })
            ->andReturnUsing(fn (TaskEntity $entity) => $entity);

        $result = $this->taskService->updateTask(1, $dto, 10);

        $this->assertSame('Updated Title', $result->title);
        $this->assertSame('in-progress', $result->status);
    }

    public function test_update_task_throws_when_not_found(): void
    {
        $this->taskRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $dto = new UpdateTaskRequestDto(title: 'Title');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->taskService->updateTask(999, $dto, 10);
    }

    public function test_update_task_throws_when_user_does_not_own_project(): void
    {
        $existing = new TaskEntity(1, 5, null, 'Title', null, 'todo', null);

        $this->taskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->mockProjectOwnedByOther(5, 20);

        $dto = new UpdateTaskRequestDto(title: 'Title');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->taskService->updateTask(1, $dto, 10);
    }

    // --- deleteTask ---

    public function test_delete_task_calls_repository(): void
    {
        $existing = new TaskEntity(1, 5, null, 'Title', null, 'todo', null);

        $this->taskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->mockProjectOwnership(5, 10);

        $this->taskRepository
            ->shouldReceive('delete')
            ->with(1)
            ->once();

        $this->taskService->deleteTask(1, 10);
    }

    public function test_delete_task_throws_when_not_found(): void
    {
        $this->taskRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task not found.');

        $this->taskService->deleteTask(999, 10);
    }

    public function test_delete_task_throws_when_user_does_not_own_project(): void
    {
        $existing = new TaskEntity(1, 5, null, 'Title', null, 'todo', null);

        $this->taskRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->mockProjectOwnedByOther(5, 20);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->taskService->deleteTask(1, 10);
    }
}
