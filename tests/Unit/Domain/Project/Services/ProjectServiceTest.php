<?php

namespace Tests\Unit\Domain\Project\Services;

use App\Application\Project\DTOs\CreateProjectRequestDto;
use App\Application\Project\DTOs\UpdateProjectRequestDto;
use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Services\ProjectService;
use App\Infrastructure\Database\Repositories\ProjectRepository;
use Mockery;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    private ProjectRepository|Mockery\MockInterface $projectRepository;
    private ProjectService $projectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRepository = Mockery::mock(ProjectRepository::class);
        $this->projectService = new ProjectService($this->projectRepository);
    }

    public function test_list_projects_returns_projects_for_user(): void
    {
        $entities = [
            new ProjectEntity(1, 10, 'Project A', 'Desc A'),
            new ProjectEntity(2, 10, 'Project B', null),
        ];

        $this->projectRepository
            ->shouldReceive('findAllByUserId')
            ->with(10)
            ->once()
            ->andReturn($entities);

        $result = $this->projectService->listProjects(10);

        $this->assertCount(2, $result);
        $this->assertSame('Project A', $result[0]->name);
        $this->assertSame('Project B', $result[1]->name);
    }

    public function test_list_projects_returns_empty_array_when_no_projects(): void
    {
        $this->projectRepository
            ->shouldReceive('findAllByUserId')
            ->with(10)
            ->once()
            ->andReturn([]);

        $result = $this->projectService->listProjects(10);

        $this->assertCount(0, $result);
    }

    public function test_get_project_returns_entity(): void
    {
        $entity = new ProjectEntity(1, 10, 'Project A', 'Desc A');

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($entity);

        $result = $this->projectService->getProject(1, 10);

        $this->assertSame(1, $result->id);
        $this->assertSame('Project A', $result->name);
    }

    public function test_get_project_throws_when_not_found(): void
    {
        $this->projectRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->projectService->getProject(999, 10);
    }

    public function test_get_project_throws_when_user_does_not_own_project(): void
    {
        $entity = new ProjectEntity(1, 20, 'Project A', 'Desc A');

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($entity);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->projectService->getProject(1, 10);
    }

    public function test_create_project_saves_and_returns_entity(): void
    {
        $dto = new CreateProjectRequestDto(
            name: 'New Project',
            description: 'A description',
        );

        $this->projectRepository
            ->shouldReceive('save')
            ->once()
            ->withArgs(function (ProjectEntity $entity) {
                return $entity->name === 'New Project'
                    && $entity->description === 'A description'
                    && $entity->userId === 10
                    && $entity->id === null;
            })
            ->andReturnUsing(function (ProjectEntity $entity) {
                $entity->id = 1;
                return $entity;
            });

        $result = $this->projectService->createProject($dto, 10);

        $this->assertSame(1, $result->id);
        $this->assertSame('New Project', $result->name);
        $this->assertSame(10, $result->userId);
    }

    public function test_update_project_applies_changes_and_returns(): void
    {
        $existing = new ProjectEntity(1, 10, 'Old Name', 'Old Desc');

        $dto = new UpdateProjectRequestDto(
            name: 'Updated Name',
            description: 'Updated Desc',
        );

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->projectRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (ProjectEntity $entity) {
                return $entity->name === 'Updated Name'
                    && $entity->description === 'Updated Desc';
            })
            ->andReturnUsing(fn (ProjectEntity $entity) => $entity);

        $result = $this->projectService->updateProject(1, $dto, 10);

        $this->assertSame('Updated Name', $result->name);
        $this->assertSame('Updated Desc', $result->description);
    }

    public function test_update_project_throws_when_not_found(): void
    {
        $dto = new UpdateProjectRequestDto(name: 'Name', description: null);

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->projectService->updateProject(999, $dto, 10);
    }

    public function test_update_project_throws_when_user_does_not_own_project(): void
    {
        $existing = new ProjectEntity(1, 20, 'Name', 'Desc');

        $dto = new UpdateProjectRequestDto(name: 'Name', description: null);

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->projectService->updateProject(1, $dto, 10);
    }

    public function test_delete_project_calls_repository(): void
    {
        $existing = new ProjectEntity(1, 10, 'Name', 'Desc');

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->projectRepository
            ->shouldReceive('delete')
            ->with(1)
            ->once();

        $this->projectService->deleteProject(1, 10);
    }

    public function test_delete_project_throws_when_not_found(): void
    {
        $this->projectRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->projectService->deleteProject(999, 10);
    }

    public function test_delete_project_throws_when_user_does_not_own_project(): void
    {
        $existing = new ProjectEntity(1, 20, 'Name', 'Desc');

        $this->projectRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Project not found.');

        $this->projectService->deleteProject(1, 10);
    }
}
