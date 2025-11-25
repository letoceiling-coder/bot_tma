<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserRoleSeeder::class,
        ]);

        // Создание администратора по умолчанию
        if (!\App\Models\User::where('email', 'admin@crm.loc')->exists()) {
            \App\Models\User::factory()->admin()->create([
                'name' => 'Администратор',
                'email' => 'admin@crm.loc',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('✅ Создан администратор: admin@crm.loc / password');
        }

        // Раскомментируйте для тестовых данных
        // \App\Models\User::factory(10)->create();
        // \App\Models\User::factory(5)->moderator()->create();
    }
}
