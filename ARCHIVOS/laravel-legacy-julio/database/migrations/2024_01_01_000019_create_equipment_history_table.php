<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade');
            $table->string('event_type', 50);
            $table->text('description');
            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('equipment_id');
            $table->index('event_type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_history');
    }
};
