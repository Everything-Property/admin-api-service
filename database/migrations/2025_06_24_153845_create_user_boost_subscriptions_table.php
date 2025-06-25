<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_boost_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('boost_plan_id')->constrained('boost_plans')->onDelete('cascade');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('discount_applied', 5, 2)->default(0.00);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->string('flutterwave_transaction_id')->nullable();
            $table->json('payment_details')->nullable(); // Store payment gateway response
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_boost_subscriptions');
    }
};
