<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained('equipment_brands')->onDelete('set null');
            $table->foreignId('model_id')->nullable()->constrained('equipment_models')->onDelete('set null');
            $table->string('serial_number', 100)->nullable();
            $table->string('equipment_code', 50)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiration')->nullable();
            $table->date('next_service_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'inactive', 'in_workshop', 'decommissioned'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('brand_id');
            $table->index('model_id');
            $table->index('status');
            $table->index('next_service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
