<?php

namespace App\Domain\Auth\Repositories;

use App\Domain\User\Entities\UserEntity;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email): ?UserEntity;

    public function createToken(int $userId, string $tokenName): string;

    public function revokeCurrentToken(int $userId): void;
}
