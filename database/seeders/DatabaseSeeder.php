<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use LogicException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = config('assessment.accounts.admin');
        $fieldStaff = config('assessment.accounts.field_staff');

        if (app()->environment('production')) {
            $passwords = [$admin['password'] ?? null, $fieldStaff['password'] ?? null];
            if (
                blank($admin['email'] ?? null)
                || blank($fieldStaff['email'] ?? null)
                || in_array(null, $passwords, true)
                || in_array('password', $passwords, true)
                || strlen($passwords[0]) < 16
                || strlen($passwords[1]) < 16
                || $admin['email'] === $fieldStaff['email']
                || $passwords[0] === $passwords[1]
            ) {
                throw new LogicException('Set distinct, non-default assessment account credentials before seeding production.');
            }
        }

        User::query()->firstOrCreate(
            ['email' => $admin['email'] ?: 'admin@example.com'],
            [
                'name' => 'Assessment Admin',
                'password' => $admin['password'] ?: 'password',
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => $fieldStaff['email'] ?: 'field@example.com'],
            [
                'name' => 'Assessment Field Staff',
                'password' => $fieldStaff['password'] ?: 'password',
                'role' => UserRole::FieldStaff,
                'is_active' => true,
            ],
        );

        $this->call(AnwaraSchoolSeeder::class);
        $this->call(SchoolPrincipalContactSeeder::class);
        $this->call(DemandSetupSeeder::class);
        $this->call(OfficialReportPeriodSeeder::class);
    }
}
