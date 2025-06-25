<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Seed Permissions
        $permissions = [
            'can_view_listing', 'can_view_project', 'can_schedule_viewing_of_listing',
            'can_schedule_viewing_of_project', 'can_fund_wallet', 'can_review_listing',
            'can_review_project', 'can_search_for_brokers', 'can_view_articles',
            'can_request_kyc_verification', 'can_view_terms_and_conditions',
            'can_search_for_listings_and_projects', 'can_add_listing', 'can_edit_delete_listing',
            'can_withdraw_from_wallet', 'can_add_project', 'can_edit_delete_project',
            'can_create_sub_accounts', 'can_edit_delete_sub_accounts', 'can_request_rc_verification',
            'can_upload_article', 'can_edit_article', 'can_delete_article', 'can_view_dashboard_reports',
            'can_export_reports', 'can_create_user', 'can_reset_user_password', 'can_trigger_password_reset',
            'can_view_user_transactions', 'can_verify_kyc', 'can_view_activity_log',
            'can_create_role_permission', 'can_edit_role_permission', 'can_create_staff', 'can_edit_delete_staff'
        ];

        // Insert permissions into the 'permissions' table
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['description' => str_replace('_', ' ', ucfirst($permission))]
            );
        }

        // Seed Roles
        $roles = [
            'ROLE_SUPERADMIN' => 'Superadmin',
            'ROLE_ADMIN' => 'Admin',
            'ROLE_AGENT' => 'Agent',
            'ROLE_USER' => 'Seeker',
            'ROLE_BROKER' => 'Broker',
            'ROLE_DEVELOPER' => 'Developer',
            'ROLE_COMPANY' => 'Company',
            'ROLE_STAFF_MEDIA' => 'Media Staff',
            'ROLE_STAFF_IT' => 'IT Staff',
        ];

        // Insert roles into the 'roles' table
        foreach ($roles as $roleName => $roleDescription) {
            Role::updateOrCreate(
                ['name' => $roleName],
                ['description' => $roleDescription]
            );
        }

        // Assign Permissions to Roles (role_permission table)
        $rolesPermissions = [
            'ROLE_USER' => [
                'can_view_listing', 'can_view_project', 'can_schedule_viewing_of_listing',
                'can_schedule_viewing_of_project', 'can_fund_wallet', 'can_review_listing',
                'can_review_project', 'can_search_for_brokers', 'can_view_articles',
                'can_request_kyc_verification', 'can_view_terms_and_conditions',
                'can_search_for_listings_and_projects'
            ],
            'ROLE_BROKER' => [
                'can_view_listing', 'can_view_project', 'can_add_listing', 'can_edit_delete_listing',
                'can_schedule_viewing_of_listing', 'can_fund_wallet', 'can_withdraw_from_wallet',
                'can_review_listing', 'can_search_for_brokers', 'can_view_articles',
                'can_request_kyc_verification', 'can_view_terms_and_conditions',
                'can_search_for_listings_and_projects'
            ],
            'ROLE_DEVELOPER' => [
                'can_view_listing', 'can_view_project', 'can_add_project', 'can_edit_delete_project',
                'can_request_rc_verification', 'can_create_sub_accounts', 'can_edit_delete_sub_accounts',
                'can_review_listing', 'can_review_project', 'can_search_for_brokers',
                'can_view_articles', 'can_view_terms_and_conditions', 'can_search_for_listings_and_projects'
            ],
            'ROLE_COMPANY' => [
                'can_view_listing', 'can_view_project', 'can_add_listing', 'can_edit_delete_listing',
                'can_request_rc_verification', 'can_create_sub_accounts', 'can_edit_delete_sub_accounts',
                'can_withdraw_from_wallet', 'can_review_listing', 'can_review_project', 'can_search_for_brokers',
                'can_view_articles', 'can_view_terms_and_conditions', 'can_search_for_listings_and_projects'
            ],
            'ROLE_STAFF_MEDIA' => [
                'can_upload_article', 'can_edit_article', 'can_delete_article',
                'can_add_listing', 'can_add_project', 'can_edit_delete_listing',
                'can_edit_delete_project', 'can_view_dashboard_reports', 'can_export_reports'
            ],
            'ROLE_STAFF_IT' => [
                'can_upload_article', 'can_edit_article', 'can_delete_article',
                'can_add_listing', 'can_add_project', 'can_edit_delete_listing',
                'can_edit_delete_project', 'can_view_dashboard_reports', 'can_export_reports',
                'can_create_user', 'can_reset_user_password', 'can_trigger_password_reset',
                'can_view_user_transactions', 'can_verify_kyc', 'can_view_activity_log',
                'can_create_role_permission', 'can_edit_role_permission', 'can_create_staff',
                'can_edit_delete_staff'
            ],
            'ROLE_SUPERADMIN' => $permissions,
            'ROLE_ADMIN' => $permissions,
            'ROLE_AGENT' => ['can_create_user']
        ];

        // Insert role_permission relationships into 'role_permission' table
        foreach ($rolesPermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();

                if ($role && $permission) {
                    // Insert into role_permission table
                    DB::table('role_permission')->updateOrInsert(
                        ['role_id' => $role->id, 'permission_id' => $permission->id]
                    );
                }
            }
        }
    }
}
