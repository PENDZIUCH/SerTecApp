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
        $this->command->info('🌱 Starting database seeding...');
        $this->command->newLine();

        // Run seeders in order
        $this->call([
            RolesSeeder::class,
            PermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📝 Default credentials:');
        $this->command->info('   Admin: admin@sertecapp.local / 12345678');
        $this->command->info('   Technician: tecnico@sertecapp.local / 12345678');
        $this->command->info('   Supervisor: supervisor@sertecapp.local / 12345678');
        $this->command->newLine();
    }
}
