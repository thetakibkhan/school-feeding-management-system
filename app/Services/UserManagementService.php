<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    public function __construct(private readonly UserRepository $users) {}

    public function listUsers(): Collection
    {
        return $this->users->all();
    }

    public function createUser(string $name, string $email, string $password, UserRole $role): User
    {
        return $this->users->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function updateUser(User $user, string $name, string $email, UserRole $role): User
    {
        $user->fill(['name' => $name, 'email' => $email, 'role' => $role]);

        return $this->users->save($user);
    }

    public function deactivate(User $user): User
    {
        $user->is_active = false;

        return $this->users->save($user);
    }
}
