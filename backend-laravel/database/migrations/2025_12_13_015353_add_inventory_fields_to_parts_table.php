<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('location')->nullable()->after('stock_quantity');
            $table->decimal('fob_price_usd', 10, 2)->nullable()->after('location');
            $table->decimal('markup_percent', 5, 2)->default(20.00)->after('fob_price_usd');
            $table->decimal('sale_price_usd', 10, 2)->nullable()->after('markup_percent');
            $table->foreignId('equipment_model_id')->nullable()->after('sale_price_usd')->constrained('equipment_models')->onDelete('set null');
            
            $table->index('location');
            $table->index('equipment_model_id');
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign(['equipment_model_id']);
            $table->dropColumn(['location', 'fob_price_usd', 'markup_percent', 'sale_price_usd', 'equipment_model_id']);
        });
    }
};
