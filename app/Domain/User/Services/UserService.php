<?php

namespace App\Domain\User\Services;

use App\Application\User\DTOs\CreateUserRequestDto;
use App\Application\User\DTOs\UpdateUserRequestDto;
use App\Domain\User\Entities\UserEntity;
use App\Infrastructure\Database\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * @return UserEntity[]
     */
    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function getUser(int $id): UserEntity
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \RuntimeException('User not found.');
        }

        return $user;
    }

    public function createUser(CreateUserRequestDto $dto): UserEntity
    {
        $userEntity = UserEntity::fromCreateUserRequestDto($dto);
        $userEntity->password = Hash::make($userEntity->password);

        return $this->userRepository->save($userEntity);
    }

    public function updateUser(int $id, UpdateUserRequestDto $dto): UserEntity
    {
        $userEntity = $this->userRepository->findById($id);

        if (!$userEntity) {
            throw new \RuntimeException('User not found.');
        }

        $userEntity->applyUpdate($dto);

        if ($dto->password !== null) {
            $userEntity->password = Hash::make($userEntity->password);
        }

        return $this->userRepository->update($userEntity);
    }

    public function deleteUser(int $id): void
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \RuntimeException('User not found.');
        }

        $this->userRepository->delete($id);
    }
}
