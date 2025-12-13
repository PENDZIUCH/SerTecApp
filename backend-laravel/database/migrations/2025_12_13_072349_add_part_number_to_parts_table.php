<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('part_number', 100)->nullable()->after('name')->index();
        });
        
        // Generar part_number automático para registros existentes
        DB::statement("UPDATE parts SET part_number = 'AUTO-' || id WHERE part_number IS NULL");
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('part_number');
        });
    }
};
