<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->string('token', 100)->unique();
            $table->timestamp('expires_at');
            $table->string('signed_by_name')->nullable();
            $table->string('signed_by_email')->nullable();
            $table->string('signed_by_phone', 20)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_image_path')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('work_order_id');
            $table->index('token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_tokens');
    }
};
