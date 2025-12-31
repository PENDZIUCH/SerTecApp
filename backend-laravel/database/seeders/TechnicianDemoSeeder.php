<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TechnicianDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario técnico demo
        $tecnico = User::firstOrCreate(
            ['email' => 'tech@demo.com'],
            [
                'name' => 'Juan Técnico',
                'password' => Hash::make('1234'),
            ]
        );

        // Crear clientes demo si no existen
        $gymCentro = Customer::firstOrCreate(
            ['tax_id' => '20-12345678-9'],
            [
                'business_name' => 'Gym Centro',
                'contact_name' => 'Carlos Pérez',
                'phone' => '+54 11 1234-5678',
                'email' => 'carlos@gymcentro.com',
                'address' => 'Av. Libertador 1234, CABA',
            ]
        );

        $fitnessSur = Customer::firstOrCreate(
            ['tax_id' => '20-98765432-1'],
            [
                'business_name' => 'Club Fitness Sur',
                'contact_name' => 'Ana García',
                'phone' => '+54 11 8765-4321',
                'email' => 'ana@fitnesssur.com',
                'address' => 'Mitre 567, Avellaneda',
            ]
        );

        $fitnessCompany = Customer::firstOrCreate(
            ['tax_id' => '20-55555555-5'],
            [
                'business_name' => 'Fitness Company',
                'contact_name' => 'Luis Martínez',
                'phone' => '+54 11 5555-6666',
                'email' => 'luis@fitnesscompany.com',
                'address' => 'San Martín 890, San Isidro',
            ]
        );

        // Crear equipos demo
        $cinta = Equipment::firstOrCreate(
            ['serial_number' => 'BF-PT300-2023-001'],
            [
                'customer_id' => $gymCentro->id,
                'brand' => 'Body Fitness',
                'model' => 'PT300',
                'type' => 'treadmill',
                'status' => 'active',
            ]
        );

        $bici = Equipment::firstOrCreate(
            ['serial_number' => 'SW-IC2-2022-045'],
            [
                'customer_id' => $fitnessSur->id,
                'brand' => 'Schwinn',
                'model' => 'IC2',
                'type' => 'bike',
                'status' => 'active',
            ]
        );

        $remo = Equipment::firstOrCreate(
            ['serial_number' => 'LF-GX-2021-089'],
            [
                'customer_id' => $fitnessCompany->id,
                'brand' => 'Life Fitness',
                'model' => 'GX',
                'type' => 'rower',
                'status' => 'active',
            ]
        );

        // Crear órdenes de trabajo demo
        WorkOrder::firstOrCreate(
            [
                'equipment_id' => $cinta->id,
                'description' => 'Cinta no enciende',
            ],
            [
                'customer_id' => $gymCentro->id,
                'assigned_technician_id' => $tecnico->id,
                'priority' => 4, // urgente
                'status' => 'pending',
                'notes' => 'Cliente reporta que la cinta no enciende desde ayer. Revisaron el enchufe y funciona.',
            ]
        );

        WorkOrder::firstOrCreate(
            [
                'equipment_id' => $bici->id,
                'description' => 'Bici hace ruido en pedal derecho',
            ],
            [
                'customer_id' => $fitnessSur->id,
                'assigned_technician_id' => $tecnico->id,
                'priority' => 2, // media
                'status' => 'pending',
                'notes' => 'Ruido metálico en el pedal derecho al pedalear.',
            ]
        );

        WorkOrder::firstOrCreate(
            [
                'equipment_id' => $remo->id,
                'description' => 'Remo pierde resistencia',
            ],
            [
                'customer_id' => $fitnessCompany->id,
                'assigned_technician_id' => $tecnico->id,
                'priority' => 3, // alta
                'status' => 'pending',
                'notes' => 'La resistencia del remo baja progresivamente durante el uso.',
            ]
        );

        $this->command->info('✅ Datos demo creados correctamente');
        $this->command->info('📧 Email: tech@demo.com');
        $this->command->info('🔑 Password: 1234');
    }
}
