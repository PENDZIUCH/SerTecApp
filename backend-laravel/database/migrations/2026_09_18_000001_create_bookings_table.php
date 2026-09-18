<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de agenda/reservas GENERICO y reusable (no acoplado a
 * work_order/tecnico). "resource" = lo que se reserva (un tecnico, una
 * mesa, un repartidor); "subject" = para que es la reserva (una orden de
 * trabajo, un cliente, un pedido). La integracion especifica de SerTecApp
 * (tecnico=resource, orden de trabajo=subject) vive en WorkOrderService,
 * no en este schema. El sistema Visit existente sigue en paralelo, intacto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');

            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();

            $table->string('status')->default('scheduled');

            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['resource_type', 'resource_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('starts_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
