<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();

            // Account
            $table->string('password')->nullable();

            // Notifications
            $table->boolean('push_notification')->default(false);
            $table->boolean('email_notification')->default(false);
            $table->boolean('sms_notification')->default(false);

            // Privacy
            $table->enum('privacy', ['private', 'public'])->default('public');

            // App Preference
            $table->boolean('dark_mode')->default(false);
            $table->string('language')->nullable();

            // Security
            $table->boolean('two_factor_auth')->default(false);

            $table->timestamps();
        });
    }






    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
