<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Entities\UserEntity;

interface UserRepositoryInterface
{
    /**
     * @return UserEntity[]
     */
    public function findAll(): array;

    public function findById(int $id): ?UserEntity;

    public function save(UserEntity $data): UserEntity;

    public function update(UserEntity $data): UserEntity;

    public function delete(int $id): void;
}
