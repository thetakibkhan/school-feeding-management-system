<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Assessment Admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'field@example.com'],
            [
                'name' => 'Assessment Field Staff',
                'password' => 'password',
                'role' => UserRole::FieldStaff,
                'is_active' => true,
            ],
        );
    }
}
