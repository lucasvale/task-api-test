<?php

namespace Tests\Unit\Infrastructure\Database\Repositories;

use App\Domain\User\Entities\UserEntity;
use App\Infrastructure\Database\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository();
    }

    public function test_find_all_returns_empty_when_no_users(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_find_all_returns_all_users_as_entities(): void
    {
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $result = $this->repository->findAll();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(UserEntity::class, $result);

        $names = array_map(fn (UserEntity $e) => $e->name, $result);
        $this->assertContains('Alice', $names);
        $this->assertContains('Bob', $names);
    }

    public function test_find_by_id_returns_entity_when_exists(): void
    {
        $user = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);

        $result = $this->repository->findById($user->id);

        $this->assertInstanceOf(UserEntity::class, $result);
        $this->assertSame($user->id, $result->id);
        $this->assertSame('Alice', $result->name);
        $this->assertSame('alice@example.com', $result->email);
    }

    public function test_find_by_id_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_save_creates_user_and_returns_entity_with_id(): void
    {
        $entity = new UserEntity(null, 'Alice', 'alice@example.com', 'hashed-password');

        $result = $this->repository->save($entity);

        $this->assertNotNull($result->id);
        $this->assertSame('Alice', $result->name);
        $this->assertSame('alice@example.com', $result->email);

        $this->assertDatabaseHas('users', [
            'id' => $result->id,
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
    }

    public function test_update_modifies_existing_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $entity = new UserEntity($user->id, 'Alice Updated', 'alice.new@example.com', $user->password);

        $result = $this->repository->update($entity);

        $this->assertSame('Alice Updated', $result->name);
        $this->assertSame('alice.new@example.com', $result->email);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Alice Updated',
            'email' => 'alice.new@example.com',
        ]);
    }

    public function test_update_throws_when_user_not_found(): void
    {
        $entity = new UserEntity(999, 'Ghost', 'ghost@example.com', 'password');

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($entity);
    }

    public function test_delete_removes_user(): void
    {
        $user = User::factory()->create();

        $this->repository->delete($user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_throws_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete(999);
    }
}
