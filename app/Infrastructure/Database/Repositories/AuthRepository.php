<?php

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use App\Domain\User\Entities\UserEntity;
use App\Models\User;

class AuthRepository implements AuthRepositoryInterface
{
    public function findByEmail(string $email): ?UserEntity
    {
        $user = User::where('email', $email)->first();

        return $user ? UserEntity::fromModel($user) : null;
    }

    public function createToken(int $userId, string $tokenName): string
    {
        $user = User::findOrFail($userId);

        return $user->createToken($tokenName)->plainTextToken;
    }

    public function revokeCurrentToken(int $userId): void
    {
        $user = User::findOrFail($userId);

        $user->currentAccessToken()?->delete();
    }
}
