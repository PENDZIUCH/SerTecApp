<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number', 100)->nullable()->index();
            $table->string('name');
            $table->string('sku', 100)->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->integer('stock_qty')->default(0);
            $table->integer('stock_quantity')->default(0)->index();
            $table->integer('min_stock_level')->default(0);
            $table->string('location')->nullable()->index();
            $table->decimal('fob_price_usd', 10, 2)->nullable();
            $table->decimal('markup_percent', 5, 2)->default(20.00);
            $table->decimal('sale_price_usd', 10, 2)->nullable();
            $table->foreignId('equipment_model_id')->nullable()->constrained('equipment_models')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
