<?php

namespace Tests\Unit\Infrastructure\Database\Repositories;

use App\Application\Task\DTOs\TaskFiltersDto;
use App\Domain\Task\Entities\TaskEntity;
use App\Infrastructure\Database\Repositories\TaskRepository;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $repository;
    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TaskRepository();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    // --- findAllByProjectId ---

    public function test_find_all_by_project_id_returns_only_project_tasks(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);

        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Task A']);
        Task::factory()->create(['project_id' => $otherProject->id, 'title' => 'Task B']);

        $result = $this->repository->findAllByProjectId($this->project->id, new TaskFiltersDto());

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(TaskEntity::class, $result);
        $this->assertSame('Task A', $result[0]->title);
    }

    public function test_find_all_by_project_id_returns_empty_when_no_tasks(): void
    {
        $result = $this->repository->findAllByProjectId($this->project->id, new TaskFiltersDto());

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_find_all_filters_by_status(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Todo', 'status' => 'todo']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Done', 'status' => 'done']);

        $filters = new TaskFiltersDto(status: 'todo');
        $result = $this->repository->findAllByProjectId($this->project->id, $filters);

        $this->assertCount(1, $result);
        $this->assertSame('Todo', $result[0]->title);
    }

    public function test_find_all_filters_by_due_date_from(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Early', 'due_date' => '2026-01-01']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Late', 'due_date' => '2026-06-01']);

        $filters = new TaskFiltersDto(due_date_from: '2026-03-01');
        $result = $this->repository->findAllByProjectId($this->project->id, $filters);

        $this->assertCount(1, $result);
        $this->assertSame('Late', $result[0]->title);
    }

    public function test_find_all_filters_by_due_date_to(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Early', 'due_date' => '2026-01-01']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Late', 'due_date' => '2026-06-01']);

        $filters = new TaskFiltersDto(due_date_to: '2026-03-01');
        $result = $this->repository->findAllByProjectId($this->project->id, $filters);

        $this->assertCount(1, $result);
        $this->assertSame('Early', $result[0]->title);
    }

    public function test_find_all_filters_by_due_date_range(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Jan', 'due_date' => '2026-01-15']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Mar', 'due_date' => '2026-03-15']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Jun', 'due_date' => '2026-06-15']);

        $filters = new TaskFiltersDto(due_date_from: '2026-02-01', due_date_to: '2026-04-01');
        $result = $this->repository->findAllByProjectId($this->project->id, $filters);

        $this->assertCount(1, $result);
        $this->assertSame('Mar', $result[0]->title);
    }

    public function test_find_all_combines_multiple_filters(): void
    {
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Match', 'status' => 'todo', 'due_date' => '2026-03-15']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Wrong status', 'status' => 'done', 'due_date' => '2026-03-15']);
        Task::factory()->create(['project_id' => $this->project->id, 'title' => 'Wrong date', 'status' => 'todo', 'due_date' => '2026-01-01']);

        $filters = new TaskFiltersDto(status: 'todo', due_date_from: '2026-03-01');
        $result = $this->repository->findAllByProjectId($this->project->id, $filters);

        $this->assertCount(1, $result);
        $this->assertSame('Match', $result[0]->title);
    }

    // --- findById ---

    public function test_find_by_id_returns_entity_when_exists(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'My Task',
            'status' => 'in-progress',
            'due_date' => '2026-04-01',
        ]);

        $result = $this->repository->findById($task->id);

        $this->assertInstanceOf(TaskEntity::class, $result);
        $this->assertSame($task->id, $result->id);
        $this->assertSame('My Task', $result->title);
        $this->assertSame('in-progress', $result->status);
        $this->assertSame('2026-04-01', $result->dueDate);
    }

    public function test_find_by_id_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    // --- save ---

    public function test_save_creates_task_and_returns_entity_with_id(): void
    {
        $entity = new TaskEntity(null, $this->project->id, $this->user->id, 'New Task', 'Desc', 'todo', '2026-05-01');

        $result = $this->repository->save($entity);

        $this->assertNotNull($result->id);
        $this->assertSame('New Task', $result->title);

        $this->assertDatabaseHas('tasks', [
            'id' => $result->id,
            'project_id' => $this->project->id,
            'title' => 'New Task',
            'status' => 'todo',
        ]);
    }

    public function test_save_creates_task_with_nullable_fields(): void
    {
        $entity = new TaskEntity(null, $this->project->id, null, 'Minimal Task', null, 'todo', null);

        $result = $this->repository->save($entity);

        $this->assertNotNull($result->id);
        $this->assertNull($result->assignedTo);
        $this->assertNull($result->description);
        $this->assertNull($result->dueDate);

        $this->assertDatabaseHas('tasks', [
            'id' => $result->id,
            'assigned_to' => null,
            'description' => null,
            'due_date' => null,
        ]);
    }

    // --- update ---

    public function test_update_modifies_existing_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Old Title',
            'status' => 'todo',
        ]);

        $entity = new TaskEntity($task->id, $this->project->id, $this->user->id, 'Updated Title', 'Updated Desc', 'done', '2026-06-01');

        $result = $this->repository->update($entity);

        $this->assertSame('Updated Title', $result->title);
        $this->assertSame('done', $result->status);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'done',
        ]);
    }

    public function test_update_throws_when_task_not_found(): void
    {
        $entity = new TaskEntity(999, $this->project->id, null, 'Ghost', null, 'todo', null);

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($entity);
    }

    // --- delete ---

    public function test_delete_removes_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $this->repository->delete($task->id);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_delete_throws_when_task_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete(999);
    }
}
