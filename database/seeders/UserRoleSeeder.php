<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => UserRole::USER->value,
                'name' => UserRole::USER->name,
                'description' => UserRole::USER->description(),
                'system' => UserRole::USER->isSystem(),
            ],
            [
                'id' => UserRole::MODERATOR->value,
                'name' => UserRole::MODERATOR->name,
                'description' => UserRole::MODERATOR->description(),
                'system' => UserRole::MODERATOR->isSystem(),
            ],
            [
                'id' => UserRole::ADMIN->value,
                'name' => UserRole::ADMIN->name,
                'description' => UserRole::ADMIN->description(),
                'system' => UserRole::ADMIN->isSystem(),
            ],
            [
                'id' => UserRole::DEVELOPER->value,
                'name' => UserRole::DEVELOPER->name,
                'description' => UserRole::DEVELOPER->description(),
                'system' => UserRole::DEVELOPER->isSystem(),
            ],
        ];

        foreach ($roles as $role) {
            DB::table('user_roles')->updateOrInsert(
                ['id' => $role['id']],
                $role
            );
        }

        $this->command->info('User roles seeded successfully!');
    }
}

