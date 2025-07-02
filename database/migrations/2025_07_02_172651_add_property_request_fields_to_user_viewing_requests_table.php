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
        Schema::table('user_viewing_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('property_request_id')->nullable()->after('property_id');
            $table->string('flutterwave_transaction_id')->nullable()->after('notes');

            $table->foreign('property_request_id')->references('id')->on('property_request')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_viewing_requests', function (Blueprint $table) {
            $table->dropForeign(['property_request_id']);
            $table->dropColumn(['property_request_id', 'flutterwave_transaction_id']);
        });
    }
};
