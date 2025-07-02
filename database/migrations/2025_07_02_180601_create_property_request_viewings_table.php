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
        Schema::create('property_request_viewings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('property_request_id');
            $table->enum('viewing_type', ['free', 'paid'])->default('free');
            $table->decimal('amount_charged', 10, 2)->default(0);
            $table->integer('year');
            $table->integer('month');
            $table->enum('status', ['completed', 'pending', 'failed'])->default('completed');
            $table->timestamp('viewed_at');
            $table->string('flutterwave_transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_request_id')->references('id')->on('property_request')->onDelete('cascade');

            // Index for efficient monthly quota queries
            $table->index(['user_id', 'year', 'month', 'viewing_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_request_viewings');
    }
};
