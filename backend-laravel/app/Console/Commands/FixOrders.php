<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOrders extends Command
{
    protected $signature = 'fix:orders';
    protected $description = 'Asignar órdenes a técnico ID 4';

    public function handle()
    {
        $updated = DB::table('work_orders')
            ->whereIn('id', [1, 2])
            ->update(['assigned_tech_id' => 4]);

        $this->info("✅ {$updated} órdenes asignadas a técnico ID 4");
        
        return 0;
    }
}
