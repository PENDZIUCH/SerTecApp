<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_renewal_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->date('renewal_date');
            $table->date('previous_renewal_date')->nullable();
            $table->integer('visits_reset')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->nullable();

            $table->index('subscription_id');
            $table->index('renewal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_renewal_history');
    }
};
