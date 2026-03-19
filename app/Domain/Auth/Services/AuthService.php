<?php

namespace App\Domain\Auth\Services;

use App\Application\Auth\DTOs\LoginRequestDto;
use App\Infrastructure\Database\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;

readonly class AuthService
{
    public function __construct(
        private AuthRepository $authRepository
    ) {
    }

    public function login(LoginRequestDto $dto): array
    {
        $user = $this->authRepository->findByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new \RuntimeException('The provided credentials are incorrect.');
        }

        $token = $this->authRepository->createToken($user->id, 'api-token');

        return [
            'user' => $user->toArray(),
            'token' => $token,
        ];
    }

    public function logout(int $userId): void
    {
        $this->authRepository->revokeCurrentToken($userId);
    }
}
