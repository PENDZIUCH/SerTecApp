<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 100)->unique();
            $table->string('title');
            $table->longText('html_content');
            $table->longText('css_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('template_key');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_templates');
    }
};
