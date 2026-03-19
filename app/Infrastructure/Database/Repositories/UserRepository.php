<?php

namespace App\Infrastructure\Database\Repositories;

use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    /**
     * @return UserEntity[]
     */
    public function findAll(): array
    {
        return User::all()
            ->map(fn (User $user) => UserEntity::fromModel($user))
            ->toArray();
    }

    public function findById(int $id): ?UserEntity
    {
        $user = User::find($id);

        return $user ? UserEntity::fromModel($user) : null;
    }

    public function save(UserEntity $data): UserEntity
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);

        $data->id = $user->id;

        return $data;
    }

    public function update(UserEntity $data): UserEntity
    {
        $user = User::findOrFail($data->id);

        $user->update([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);

        return $data;
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
    }
}
