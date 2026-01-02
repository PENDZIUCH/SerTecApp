<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowPartes extends Command
{
    protected $signature = 'show:partes';
    protected $description = 'Mostrar todos los partes guardados';

    public function handle()
    {
        $partes = DB::table('work_parts')
            ->select('id', 'work_order_id', 'technician_id', 'status', 'diagnosis', 'work_done', 'signature', 'created_at')
            ->get();

        if ($partes->isEmpty()) {
            $this->error('No hay partes guardados');
            return 0;
        }

        $this->info("Total partes: " . $partes->count());
        $this->line('');

        foreach ($partes as $parte) {
            $this->info("Parte ID: {$parte->id}");
            $this->line("  Work Order: {$parte->work_order_id}");
            $this->line("  Technician: {$parte->technician_id}");
            $this->line("  Status: {$parte->status}");
            $this->line("  Diagnosis: " . substr($parte->diagnosis ?? 'Sin diagnóstico', 0, 50));
            $this->line("  Work Done: " . substr($parte->work_done ?? 'Sin trabajo', 0, 50));
            $this->line("  Signature: " . ($parte->signature ? 'SÍ' : 'NO'));
            $this->line("  Created: {$parte->created_at}");
            $this->line('');
        }

        return 0;
    }
}
