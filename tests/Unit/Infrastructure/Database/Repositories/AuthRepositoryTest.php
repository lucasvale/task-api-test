<?php

namespace Tests\Unit\Infrastructure\Database\Repositories;

use App\Domain\User\Entities\UserEntity;
use App\Infrastructure\Database\Repositories\AuthRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new AuthRepository();
    }

    public function test_find_by_email_returns_entity_when_exists(): void
    {
        User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $result = $this->repository->findByEmail('alice@example.com');

        $this->assertInstanceOf(UserEntity::class, $result);
        $this->assertSame('Alice', $result->name);
        $this->assertSame('alice@example.com', $result->email);
    }

    public function test_find_by_email_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findByEmail('nobody@example.com');

        $this->assertNull($result);
    }

    public function test_create_token_returns_plain_text_token(): void
    {
        $user = User::factory()->create();

        $token = $this->repository->createToken($user->id, 'test-token');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }

    public function test_create_token_throws_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->createToken(999, 'test-token');
    }

    public function test_revoke_current_token_throws_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->revokeCurrentToken(999);
    }
}
