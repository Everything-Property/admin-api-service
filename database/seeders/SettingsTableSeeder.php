<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsTableSeeder extends Seeder
{
    public function run()
    {
        // Populate the settings table with the specified values
        Setting::create([
            'user_id' => 16,
            'name' => 'Superadmin',
            'email' => 'superadmin@gmail.com',
            'logo' => null,
            'password' => null,
            'push_notification' => false,
            'email_notification' => false,
            'sms_notification' => false,
            'privacy' => 'public',
            'dark_mode' => false,
            'language' => 'en',
            'two_factor_auth' => false,
        ]);
    }
}
