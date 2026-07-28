<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
            $table->enum('item_type', ['service', 'part']);
            $table->text('description');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->foreignId('part_id')->nullable()->constrained('parts')->onDelete('set null');
            $table->timestamps();

            $table->index('budget_id');
            $table->index('item_type');
            $table->index('part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
