<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabla generica de "listas configurables" para admins/supervisores -
// arrancamos usandola para customer_type (2026-09-17), pero cualquier otra
// lista administrable futura (tipo de equipo, categoria de repuesto, etc.)
// se agrega como una 'category' nueva ACA, sin tabla ni migracion nueva.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100); // ej: 'customer_type'
            $table->string('value', 100);     // slug interno, ej: 'gym'
            $table->string('label', 150);     // texto mostrado, ej: 'Gimnasio'
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category', 'value']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_values');
    }
};
