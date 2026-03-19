<?php

namespace App\Domain\User\Entities;

use App\Application\User\DTOs\CreateUserRequestDto;
use App\Application\User\DTOs\UpdateUserRequestDto;
use App\Models\User;

class UserEntity
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public string $password
    ) {
    }

    public static function fromCreateUserRequestDto(CreateUserRequestDto $dto): self
    {
        return new self(
            null,
            $dto->name,
            $dto->email,
            $dto->password,
        );
    }

    public static function fromModel(User $user): self
    {
        return new self(
            $user->id,
            $user->name,
            $user->email,
            $user->password,
        );
    }

    public function applyUpdate(UpdateUserRequestDto $dto): void
    {
        $this->name = $dto->name;
        $this->email = $dto->email;

        if ($dto->password !== null) {
            $this->password = $dto->password;
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
