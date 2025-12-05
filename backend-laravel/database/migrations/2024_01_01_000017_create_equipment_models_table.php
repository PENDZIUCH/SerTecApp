<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('equipment_brands')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('model_code', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('brand_id');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_models');
    }
};
