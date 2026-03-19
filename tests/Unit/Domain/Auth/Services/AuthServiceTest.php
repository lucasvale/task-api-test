<?php

namespace Tests\Unit\Domain\Auth\Services;

use App\Application\Auth\DTOs\LoginRequestDto;
use App\Domain\Auth\Services\AuthService;
use App\Domain\User\Entities\UserEntity;
use App\Infrastructure\Database\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthRepository|Mockery\MockInterface $authRepository;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authRepository = Mockery::mock(AuthRepository::class);
        $this->authService = new AuthService($this->authRepository);
    }

    public function test_login_returns_user_and_token_with_valid_credentials(): void
    {
        $hashedPassword = Hash::make('secret123');

        $entity = new UserEntity(1, 'Alice', 'alice@example.com', $hashedPassword);

        $this->authRepository
            ->shouldReceive('findByEmail')
            ->with('alice@example.com')
            ->once()
            ->andReturn($entity);

        $this->authRepository
            ->shouldReceive('createToken')
            ->with(1, 'api-token')
            ->once()
            ->andReturn('fake-token-string');

        $dto = new LoginRequestDto(
            email: 'alice@example.com',
            password: 'secret123',
        );

        $result = $this->authService->login($dto);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertSame('fake-token-string', $result['token']);
        $this->assertSame(1, $result['user']['id']);
        $this->assertSame('Alice', $result['user']['name']);
        $this->assertSame('alice@example.com', $result['user']['email']);
    }

    public function test_login_throws_when_user_not_found(): void
    {
        $this->authRepository
            ->shouldReceive('findByEmail')
            ->with('nobody@example.com')
            ->once()
            ->andReturn(null);

        $dto = new LoginRequestDto(
            email: 'nobody@example.com',
            password: 'whatever',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The provided credentials are incorrect.');

        $this->authService->login($dto);
    }

    public function test_login_throws_when_password_is_wrong(): void
    {
        $hashedPassword = Hash::make('correct-password');

        $entity = new UserEntity(1, 'Alice', 'alice@example.com', $hashedPassword);

        $this->authRepository
            ->shouldReceive('findByEmail')
            ->with('alice@example.com')
            ->once()
            ->andReturn($entity);

        $dto = new LoginRequestDto(
            email: 'alice@example.com',
            password: 'wrong-password',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The provided credentials are incorrect.');

        $this->authService->login($dto);
    }

    public function test_login_does_not_create_token_when_credentials_invalid(): void
    {
        $this->authRepository
            ->shouldReceive('findByEmail')
            ->with('alice@example.com')
            ->once()
            ->andReturn(null);

        $this->authRepository
            ->shouldNotReceive('createToken');

        $dto = new LoginRequestDto(
            email: 'alice@example.com',
            password: 'whatever',
        );

        try {
            $this->authService->login($dto);
        } catch (\RuntimeException) {
            // expected
        }
    }

    public function test_logout_revokes_current_token(): void
    {
        $this->authRepository
            ->shouldReceive('revokeCurrentToken')
            ->with(1)
            ->once();

        $this->authService->logout(1);
    }
}
