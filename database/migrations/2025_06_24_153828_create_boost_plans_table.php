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
        Schema::create('boost_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('listing_limit'); // Number of listings allowed
            $table->decimal('base_price', 15, 2); // Base monthly price
            $table->decimal('quarterly_discount', 5, 2)->default(10.00); // Quarterly discount percentage
            $table->decimal('yearly_discount', 5, 2)->default(15.00); // Yearly discount percentage
            $table->integer('free_viewing_requests_per_month')->default(0); // Free viewing requests per month
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recommended')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_plans');
    }
};
