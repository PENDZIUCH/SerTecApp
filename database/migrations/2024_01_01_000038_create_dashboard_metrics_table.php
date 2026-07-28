<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 100);
            $table->decimal('metric_value', 15, 2)->nullable();
            $table->bigInteger('metric_int')->nullable();
            $table->json('metric_json')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('created_at')->nullable();

            $table->index('metric_key');
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_metrics');
    }
};
