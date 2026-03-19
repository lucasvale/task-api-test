<?php

namespace Tests\Unit\Domain\User\Services;

use App\Application\User\DTOs\CreateUserRequestDto;
use App\Application\User\DTOs\UpdateUserRequestDto;
use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Services\UserService;
use App\Infrastructure\Database\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserRepository|Mockery\MockInterface $userRepository;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->userService = new UserService($this->userRepository);
    }

    public function test_list_users_returns_all_users(): void
    {
        $entities = [
            new UserEntity(1, 'Alice', 'alice@example.com', 'hashed'),
            new UserEntity(2, 'Bob', 'bob@example.com', 'hashed'),
        ];

        $this->userRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($entities);

        $result = $this->userService->listUsers();

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]->name);
        $this->assertSame('Bob', $result[1]->name);
    }

    public function test_list_users_returns_empty_array_when_no_users(): void
    {
        $this->userRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn([]);

        $result = $this->userService->listUsers();

        $this->assertCount(0, $result);
    }

    public function test_get_user_returns_entity(): void
    {
        $entity = new UserEntity(1, 'Alice', 'alice@example.com', 'hashed');

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($entity);

        $result = $this->userService->getUser(1);

        $this->assertSame(1, $result->id);
        $this->assertSame('Alice', $result->name);
    }

    public function test_get_user_throws_when_not_found(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found.');

        $this->userService->getUser(999);
    }

    public function test_create_user_hashes_password_and_saves(): void
    {
        $dto = new CreateUserRequestDto(
            name: 'Alice',
            email: 'alice@example.com',
            password: 'plain-password',
        );

        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->withArgs(function (UserEntity $entity) {
                return $entity->name === 'Alice'
                    && $entity->email === 'alice@example.com'
                    && Hash::check('plain-password', $entity->password);
            })
            ->andReturnUsing(function (UserEntity $entity) {
                $entity->id = 1;
                return $entity;
            });

        $result = $this->userService->createUser($dto);

        $this->assertSame(1, $result->id);
        $this->assertSame('Alice', $result->name);
        $this->assertTrue(Hash::check('plain-password', $result->password));
    }

    public function test_update_user_without_password(): void
    {
        $existing = new UserEntity(1, 'Alice', 'alice@example.com', 'old-hashed');

        $dto = new UpdateUserRequestDto(
            name: 'Alice Updated',
            email: 'alice.new@example.com',
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (UserEntity $entity) {
                return $entity->name === 'Alice Updated'
                    && $entity->email === 'alice.new@example.com'
                    && $entity->password === 'old-hashed';
            })
            ->andReturnUsing(fn (UserEntity $entity) => $entity);

        $result = $this->userService->updateUser(1, $dto);

        $this->assertSame('Alice Updated', $result->name);
        $this->assertSame('alice.new@example.com', $result->email);
        $this->assertSame('old-hashed', $result->password);
    }

    public function test_update_user_with_password_hashes_it(): void
    {
        $existing = new UserEntity(1, 'Alice', 'alice@example.com', 'old-hashed');

        $dto = new UpdateUserRequestDto(
            name: 'Alice',
            email: 'alice@example.com',
            password: 'new-password',
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (UserEntity $entity) {
                return Hash::check('new-password', $entity->password);
            })
            ->andReturnUsing(fn (UserEntity $entity) => $entity);

        $result = $this->userService->updateUser(1, $dto);

        $this->assertTrue(Hash::check('new-password', $result->password));
    }

    public function test_update_user_throws_when_not_found(): void
    {
        $dto = new UpdateUserRequestDto(
            name: 'Alice',
            email: 'alice@example.com',
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found.');

        $this->userService->updateUser(999, $dto);
    }

    public function test_delete_user_calls_repository(): void
    {
        $existing = new UserEntity(1, 'Alice', 'alice@example.com', 'hashed');

        $this->userRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($existing);

        $this->userRepository
            ->shouldReceive('delete')
            ->with(1)
            ->once();

        $this->userService->deleteUser(1);
    }

    public function test_delete_user_throws_when_not_found(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found.');

        $this->userService->deleteUser(999);
    }
}
