<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class UpdateRolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Add missing 'ROLE_SEEKER' to roles
        $roleName = 'ROLE_SEEKER';
        $roleDescription = 'Seeker';

        $role = Role::updateOrCreate(
            ['name' => $roleName],
            ['description' => $roleDescription]
        );

        // Permissions for ROLE_SEEKER
        $seekerPermissions = [
            'can_view_listing', 'can_view_project', 'can_schedule_viewing_of_listing',
            'can_schedule_viewing_of_project', 'can_fund_wallet', 'can_review_listing',
            'can_review_project', 'can_search_for_brokers', 'can_view_articles',
            'can_request_kyc_verification', 'can_view_terms_and_conditions',
            'can_search_for_listings_and_projects'
        ];

        // Assign permissions to ROLE_SEEKER
        foreach ($seekerPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permission->id]
                );
            }
        }
    }
}
