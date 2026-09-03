<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('assigned_tech_id')->nullable()->constrained('users')->onDelete('set null');
            // subscription_id comentado - tabla subscriptions se crea DESPUÉS
            // $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->date('visit_date');
            $table->time('scheduled_time')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('work_order_id');
            $table->index('assigned_tech_id');
            $table->index('subscription_id');
            $table->index('visit_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
