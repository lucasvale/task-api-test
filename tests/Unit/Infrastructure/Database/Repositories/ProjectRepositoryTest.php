<?php

namespace Tests\Unit\Infrastructure\Database\Repositories;

use App\Domain\Project\Entities\ProjectEntity;
use App\Infrastructure\Database\Repositories\ProjectRepository;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ProjectRepository();
    }

    public function test_find_all_by_user_id_returns_only_user_projects(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Project::factory()->create(['user_id' => $user1->id, 'name' => 'User1 Project']);
        Project::factory()->create(['user_id' => $user2->id, 'name' => 'User2 Project']);

        $result = $this->repository->findAllByUserId($user1->id);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(ProjectEntity::class, $result);
        $this->assertSame('User1 Project', $result[0]->name);
    }

    public function test_find_all_by_user_id_returns_empty_when_no_projects(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->findAllByUserId($user->id);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_find_by_id_returns_entity_when_exists(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Project',
            'description' => 'A description',
        ]);

        $result = $this->repository->findById($project->id);

        $this->assertInstanceOf(ProjectEntity::class, $result);
        $this->assertSame($project->id, $result->id);
        $this->assertSame($user->id, $result->userId);
        $this->assertSame('My Project', $result->name);
        $this->assertSame('A description', $result->description);
    }

    public function test_find_by_id_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_save_creates_project_and_returns_entity_with_id(): void
    {
        $user = User::factory()->create();

        $entity = new ProjectEntity(null, $user->id, 'New Project', 'Some desc');

        $result = $this->repository->save($entity);

        $this->assertNotNull($result->id);
        $this->assertSame('New Project', $result->name);
        $this->assertSame($user->id, $result->userId);

        $this->assertDatabaseHas('projects', [
            'id' => $result->id,
            'user_id' => $user->id,
            'name' => 'New Project',
            'description' => 'Some desc',
        ]);
    }

    public function test_save_creates_project_with_null_description(): void
    {
        $user = User::factory()->create();

        $entity = new ProjectEntity(null, $user->id, 'No Desc Project', null);

        $result = $this->repository->save($entity);

        $this->assertNotNull($result->id);
        $this->assertNull($result->description);

        $this->assertDatabaseHas('projects', [
            'id' => $result->id,
            'description' => null,
        ]);
    }

    public function test_update_modifies_existing_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'description' => 'Old Desc',
        ]);

        $entity = new ProjectEntity($project->id, $user->id, 'Updated Name', 'Updated Desc');

        $result = $this->repository->update($entity);

        $this->assertSame('Updated Name', $result->name);
        $this->assertSame('Updated Desc', $result->description);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'description' => 'Updated Desc',
        ]);
    }

    public function test_update_throws_when_project_not_found(): void
    {
        $entity = new ProjectEntity(999, 1, 'Ghost', null);

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($entity);
    }

    public function test_delete_removes_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->repository->delete($project->id);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_delete_throws_when_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete(999);
    }
}
