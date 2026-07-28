<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== USUARIOS ===\n";
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->whereIn('email', ['juan@test.com', 'pendziuch@gmail.com'])
    ->get();

foreach ($users as $user) {
    echo sprintf("ID: %d | Name: %s | Email: %s\n", $user->id, $user->name, $user->email);
}

echo "\n=== ORDENES ===\n";
$ordenes = DB::table('work_orders')
    ->select('id', 'wo_number', 'assigned_tech_id', 'status')
    ->orderBy('id')
    ->get();

foreach ($ordenes as $orden) {
    $techId = $orden->assigned_tech_id ?? 'NULL';
    echo sprintf("Orden #%d (%s) -> Tech ID: %s | Status: %s\n", 
        $orden->id, 
        $orden->wo_number, 
        $techId,
        $orden->status
    );
}

echo "\n=== VERIFICACIÓN API ===\n";
foreach ($users as $user) {
    $count = DB::table('work_orders')
        ->where('assigned_tech_id', $user->id)
        ->count();
    echo sprintf("%s (ID %d): %d órdenes asignadas\n", $user->name, $user->id, $count);
}
