<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowAllOrders extends Command
{
    protected $signature = 'show:all-orders';
    protected $description = 'Mostrar TODAS las órdenes de trabajo';

    public function handle()
    {
        $orders = DB::table('work_orders')
            ->select('id', 'customer_id', 'assigned_tech_id', 'status', 'description')
            ->get();

        if ($orders->isEmpty()) {
            $this->error('No hay órdenes en la base de datos');
            return 0;
        }

        $this->info("Total órdenes: " . $orders->count());
        $this->line('');

        foreach ($orders as $order) {
            $this->info("ID: {$order->id}");
            $this->line("  Customer ID: {$order->customer_id}");
            $this->line("  Assigned Tech ID: " . ($order->assigned_tech_id ?? 'NULL'));
            $this->line("  Status: {$order->status}");
            $this->line("  Description: " . ($order->description ?? 'Sin descripción'));
            $this->line('');
        }

        return 0;
    }
}
