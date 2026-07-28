<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@sertecapp.local'],
            [
                'name' => 'Administrador',
                'email' => 'admin@sertecapp.local',
                'password' => Hash::make('12345678'),
                'phone' => '+54 9 11 1234-5678',
                'job_title' => 'Administrador del Sistema',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        $admin->assignRole('admin');

        $this->command->info('✅ Admin user created successfully');
        $this->command->info('📧 Email: admin@sertecapp.local');
        $this->command->info('🔐 Password: 12345678');

        // Create additional test users
        $this->createTestUsers();
    }

    /**
     * Create additional test users
     */
    private function createTestUsers(): void
    {
        // Technician user
        $technician = User::firstOrCreate(
            ['email' => 'tecnico@sertecapp.local'],
            [
                'name' => 'Juan Técnico',
                'email' => 'tecnico@sertecapp.local',
                'password' => Hash::make('12345678'),
                'phone' => '+54 9 11 2345-6789',
                'job_title' => 'Técnico Senior',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $technician->assignRole('technician');

        // Supervisor user
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@sertecapp.local'],
            [
                'name' => 'María Supervisora',
                'email' => 'supervisor@sertecapp.local',
                'password' => Hash::make('12345678'),
                'phone' => '+54 9 11 3456-7890',
                'job_title' => 'Supervisora de Operaciones',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $supervisor->assignRole('supervisor');

        $this->command->info('✅ Test users created successfully');
    }
}
