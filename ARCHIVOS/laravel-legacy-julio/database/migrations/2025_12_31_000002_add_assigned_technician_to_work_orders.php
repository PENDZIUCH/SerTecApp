<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('assigned_technician_id')->nullable()->after('equipment_id')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_technician_id']);
            $table->dropColumn('assigned_technician_id');
        });
    }
};
