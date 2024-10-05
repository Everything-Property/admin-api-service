<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminActivityLog;

class AdminActivityLogSeeder extends Seeder
{
    public function run()
    {
        $logs = [
            [
                'timestamp' => now(),
                'name' => 'Superadmin',
                'role' => 'ROLE_SUPERADMIN',
                'activity' => 'User Login',
                'details' => 'User logged in from IP 192.168.1.1',
                'device' => 'Chrome on Windows 10',
            ],
            [
                'timestamp' => now(),
                'name' => 'AdminUser1',
                'role' => 'ROLE_ADMIN',
                'activity' => 'Created New User',
                'details' => 'Created a new user with username: johndoe',
                'device' => 'Firefox on macOS',
            ],
            [
                'timestamp' => now(),
                'name' => 'AdminUser2',
                'role' => 'ROLE_ADMIN',
                'activity' => 'Updated Settings',
                'details' => 'Updated privacy settings for user johndoe',
                'device' => 'Safari on iOS',
            ],
        ];

        foreach ($logs as $log) {
            AdminActivityLog::create($log);
        }
    }
}
