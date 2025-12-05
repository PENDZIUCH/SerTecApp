<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'delivered'])->default('pending');
            $table->date('entry_date');
            $table->date('estimated_completion_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->foreignId('assigned_tech_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('equipment_id');
            $table->index('customer_id');
            $table->index('work_order_id');
            $table->index('status');
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_items');
    }
};
