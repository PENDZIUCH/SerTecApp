<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar el rol del usuario admin
        $adminUser = DB::table('users')->where('email', 'admin@demo.com')->first();
        if ($adminUser) {
            // Obtener el ID del nuevo rol 'administrador'
            $adminRole = DB::table('roles')->where('name', 'administrador')->first();
            if ($adminRole) {
                // Actualizar la tabla pivot model_has_roles
                DB::table('model_has_roles')
                    ->where('model_id', $adminUser->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->update(['role_id' => $adminRole->id]);
            }
        }
    }

    public function down(): void
    {
        // No revertir
    }
};
