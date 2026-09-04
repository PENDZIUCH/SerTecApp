<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PARTES EN DB ===\n";
$partes = DB::table('work_parts')
    ->select('id', 'work_order_id', 'status', 'created_at')
    ->orderBy('id')
    ->get();

foreach ($partes as $parte) {
    echo sprintf(
        "ID: %d | Orden: %d | Status: %s | Created: %s\n",
        $parte->id,
        $parte->work_order_id,
        $parte->status,
        $parte->created_at
    );
}

echo "\nTotal: " . $partes->count() . " partes\n";
