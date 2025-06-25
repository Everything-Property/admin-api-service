<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $accountTypes = [
            [
                'name' => 'ROLE_SUPERADMIN',
                'description' => 'Super Administrator with full access',
            ],
            [
                'name' => 'ROLE_ADMIN',
                'description' => 'Administrator with management access',
            ],
            [
                'name' => 'ROLE_AGENT',
                'description' => 'Real Estate Agent with property management access',
            ],
            [
                'name' => 'ROLE_USER',
                'description' => 'Regular User with limited access',
            ],
            [
                'name' => 'ROLE_BROKER',
                'description' => 'Regular User with limited access',
            ],
            [
                'name' => 'ROLE_DEVELOPER',
                'description' => 'Regular User with limited access',
            ],
            [
                'name' => 'ROLE_COMPANY',
                'description' => 'Company Administrator with organization management access',
            ],
            [
                'name' => 'ROLE_STAFF_MEDIA',
                'description' => 'Media staff access',
            ],
            [
                'name' => 'ROLE_STAFF_IT',
                'description' => 'IT staff access',
            ],
        ];

        DB::table('account_types')->insert($accountTypes);
    }
}
