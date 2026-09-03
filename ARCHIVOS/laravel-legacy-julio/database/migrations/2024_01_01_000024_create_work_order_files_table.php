<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->string('file_name');
            $table->string('original_file_name');
            $table->string('file_path');
            $table->string('file_type', 100);
            $table->enum('file_category', ['photo', 'report', 'invoice', 'other'])->default('other');
            $table->unsignedBigInteger('file_size');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('work_order_id');
            $table->index('file_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_files');
    }
};
