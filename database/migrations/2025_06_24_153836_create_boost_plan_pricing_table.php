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
        Schema::create('boost_plan_pricing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boost_plan_id');
            $table->enum('account_type', ['ROLE_USER', 'ROLE_BROKER', 'ROLE_DEVELOPER', 'ROLE_COMPANY']);
            $table->decimal('monthly_price', 15, 2);
            $table->decimal('quarterly_price', 15, 2);
            $table->decimal('yearly_price', 15, 2);
            $table->timestamps();
            
            $table->unique(['boost_plan_id', 'account_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_plan_pricing');
    }
};
