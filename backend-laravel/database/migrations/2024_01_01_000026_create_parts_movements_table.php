<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'return']);
            $table->integer('quantity');
            $table->foreignId('related_work_order_id')->nullable()->constrained('work_orders')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('part_id');
            $table->index('movement_type');
            $table->index('related_work_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts_movements');
    }
};
