<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'pendziuch@gmail.com')->first();

if ($user) {
    // Remover rol técnico
    $user->removeRole('técnico');
    
    // Agregar rol admin
    $adminRole = Spatie\Permission\Models\Role::where('name', 'administrador')->first();
    if ($adminRole) {
        $user->assignRole($adminRole);
        echo "✅ Usuario '{$user->name}' ahora tiene rol ADMINISTRADOR\n";
    } else {
        echo "❌ Rol 'administrador' no existe\n";
    }
} else {
    echo "❌ Usuario no encontrado\n";
}
