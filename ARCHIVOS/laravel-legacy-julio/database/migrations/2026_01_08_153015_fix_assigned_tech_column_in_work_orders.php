<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Copiar datos de assigned_technician_id a assigned_tech_id
        DB::statement('UPDATE work_orders SET assigned_tech_id = assigned_technician_id WHERE assigned_technician_id IS NOT NULL');
        
        // Eliminar la columna duplicada
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_technician_id']);
            $table->dropColumn('assigned_technician_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};
