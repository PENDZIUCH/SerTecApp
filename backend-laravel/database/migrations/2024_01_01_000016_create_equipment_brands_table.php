<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('country', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_brands');
    }
};
