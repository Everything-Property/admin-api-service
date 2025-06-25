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
        Schema::table('user_boost_subscriptions', function (Blueprint $table) {
            $table->decimal('discount_applied', 5, 2)->default(0.00)->after('amount_paid');
            $table->boolean('auto_renew')->default(false)->after('end_date');
            $table->string('flutterwave_transaction_id')->nullable()->after('auto_renew');

            // Update status enum to include 'pending'
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_boost_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['discount_applied', 'auto_renew', 'flutterwave_transaction_id']);
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->change();
        });
    }
};
