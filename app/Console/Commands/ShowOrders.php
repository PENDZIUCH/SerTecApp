<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowOrders extends Command
{
    protected $signature = 'show:orders';
    protected $description = 'Mostrar todas las columnas de las órdenes';

    public function handle()
    {
        $orders = DB::table('work_orders')
            ->whereIn('id', [5, 6, 7])
            ->get();

        foreach ($orders as $order) {
            $this->info("=== Orden {$order->id} ===");
            foreach ((array)$order as $key => $value) {
                $this->line("$key: " . ($value ?? 'NULL'));
            }
            $this->line('');
        }

        return 0;
    }
}
