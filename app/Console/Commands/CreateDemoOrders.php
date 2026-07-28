<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDemoOrders extends Command
{
    protected $signature = 'demo:orders';
    protected $description = 'Crear 3 órdenes demo con equipos para testing';

    public function handle()
    {
        // 1. Crear técnico si no existe
        $tecnico = User::firstOrCreate(
            ['email' => 'tech@demo.com'],
            [
                'name' => 'Juan Técnico',
                'password' => Hash::make('1234'),
                'is_active' => true,
            ]
        );

        $this->info("✅ Técnico: tech@demo.com (ID: {$tecnico->id})");

        // 2. Tomar primeros 3 clientes
        $clientes = Customer::where('is_active', true)->limit(3)->get();

        if ($clientes->count() < 3) {
            $this->error('❌ No hay suficientes clientes. Necesitás al menos 3.');
            return 1;
        }

        // 3. Crear marca y modelos demo si no existen
        $brand = EquipmentBrand::firstOrCreate(
            ['name' => 'Demo Brand'],
            ['description' => 'Marca demo para testing']
        );

        $models = [];
        foreach (['Cinta PT300', 'Bici IC2', 'Remo GX'] as $modelName) {
            $models[] = EquipmentModel::firstOrCreate(
                ['name' => $modelName, 'brand_id' => $brand->id],
                ['description' => 'Modelo demo']
            );
        }

        // 4. Crear órdenes
        $problemas = [
            ['Cinta no enciende', 4, 'Cliente reporta que no enciende desde ayer.'],
            ['Bici hace ruido en pedal', 2, 'Ruido metálico en el pedal derecho.'],
            ['Remo pierde resistencia', 3, 'La resistencia baja progresivamente.'],
        ];

        foreach ($clientes as $index => $cliente) {
            // Crear equipo
            $equipo = Equipment::create([
                'customer_id' => $cliente->id,
                'brand_id' => $brand->id,
                'model_id' => $models[$index]->id,
                'serial_number' => 'DEMO-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'status' => 'active',
            ]);

            // Crear orden
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

            $this->info("✅ Orden creada: {$problema[0]} - {$cliente->business_name}");
        }

        $this->info('');
        $this->info('🎉 ¡3 órdenes demo creadas!');
        $this->info('📱 Abrí pro.pendziuch.com y refrescá');

        return 0;
    }
}
