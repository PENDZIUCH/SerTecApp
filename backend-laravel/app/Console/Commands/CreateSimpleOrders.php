<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkOrder;
use App\Models\Customer;

class CreateSimpleOrders extends Command
{
    protected $signature = 'create:simple-orders';
    protected $description = 'Crear órdenes simples para testing';

    public function handle()
    {
        // Buscar 3 clientes existentes
        $customers = Customer::take(3)->get();
        
        if ($customers->count() < 3) {
            $this->error('No hay suficientes clientes. Necesitas al menos 3.');
            return 1;
        }

        $problems = [
            'Cinta no enciende - revisar conexión eléctrica',
            'Bici hace ruido en pedal derecho - ajustar rodamiento',
            'Remo pierde resistencia - verificar sistema hidráulico'
        ];

        $priorities = [4, 2, 3]; // urgente, media, alta

        foreach ($customers as $index => $customer) {
            WorkOrder::create([
                'customer_id' => $customer->id,
                'equipment_id' => null, // SIN equipo
                'assigned_technician_id' => 1, // Técnico ID 1
                'description' => $problems[$index],
                'priority' => $priorities[$index],
                'status' => 'pending',
                'notes' => 'Orden creada para testing - sin equipo asignado',
            ]);
        }

        $this->info('3 órdenes creadas exitosamente para el técnico ID 1');
        return 0;
    }
}
