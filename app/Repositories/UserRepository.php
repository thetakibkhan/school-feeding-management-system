<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function all(): Collection
    {
        return User::query()->orderBy('name')->get();
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findById(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function save(User $user): User
    {
        $user->save();

        return $user->refresh();
    }
}
