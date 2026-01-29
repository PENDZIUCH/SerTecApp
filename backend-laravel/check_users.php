<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = App\Models\User::with('roles')->get();

echo "USUARIOS Y ROLES:\n";
echo "================\n\n";

foreach($users as $u) {
    $roles = $u->roles->pluck('name')->join(', ') ?: 'SIN ROL';
    echo "ID: {$u->id}\n";
    echo "Nombre: {$u->name}\n";
    echo "Email: {$u->email}\n";
    echo "Roles: {$roles}\n";
    echo "---\n";
}
