<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El enum(['individual','company','gym']) no puede aceptar valores nuevos
// (country, hotel, consorcio, etc.) sin otra migracion cada vez - lo
// convertimos a string libre. La validacion real de "que valores son
// validos hoy" pasa a vivir en lookup_values (activo/inactivo), no en la
// columna. Los valores existentes (individual/company/gym) no se tocan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', ['individual', 'company', 'gym'])->change();
        });
    }
};
