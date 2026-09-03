<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QuickDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Crear técnico demo si no existe
        $tecnico = User::firstOrCreate(
            ['email' => 'tech@demo.com'],
            [
                'name' => 'Juan Técnico',
                'password' => Hash::make('1234'),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Técnico: tech@demo.com (password: 1234)");

        // Tomar 3 clientes reales existentes
        $clientes = Customer::where('is_active', true)->limit(3)->get();

        if ($clientes->count() < 3) {
            $this->command->error('No hay suficientes clientes en la DB');
            return;
        }

        $problemas = [
            ['Cinta no enciende', 4, 'Cliente reporta que la cinta no enciende. Verificar fuente de alimentación.'],
            ['Bici hace ruido en pedal', 2, 'Ruido metálico en el pedal derecho al pedalear.'],
            ['Remo pierde resistencia', 3, 'La resistencia del remo baja progresivamente durante el uso.'],
        ];

        foreach ($clientes as $index => $cliente) {
            // Crear equipo dummy para este cliente
            $equipo = Equipment::create([
                'customer_id' => $cliente->id,
                'brand' => 'Demo Brand',
                'model' => 'Model ' . ($index + 1),
                'serial_number' => 'DEMO-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'type' => ['treadmill', 'bike', 'rower'][$index],
                'status' => 'active',
            ]);

            // Crear orden de trabajo
            $problema = $problemas[$index];
            WorkOrder::create([
                'customer_id' => $cliente->id,
                'equipment_id' => $equipo->id,
                'assigned_technician_id' => $tecnico->id,
                'description' => $problema[0],
                'priority' => $problema[1],
                'status' => 'pending',
                'notes' => $problema[2],
            ]);

            $this->command->info("✅ Orden creada para: {$cliente->business_name}");
        }

        $this->command->info('');
        $this->command->info('🎉 ¡3 órdenes demo creadas!');
        $this->command->info('📱 Abrí pro.pendziuch.com y logueate como tech@demo.com');
    }
}
