<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('title');
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'cancelled', 'expired'])->default('draft');
            $table->date('valid_until')->nullable();
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->enum('discount_type', ['none', 'amount', 'percent'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('subtotal_services', 10, 2)->default(0);
            $table->decimal('subtotal_parts', 10, 2)->default(0);
            $table->decimal('subtotal_before_discount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('subtotal_after_discount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('status');
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
