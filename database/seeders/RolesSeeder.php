<?php

// database/seeders/RolesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin',
            'booking_manager',
            'marketing',
            'content_manager',
            'receptionist',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'booking_manager' => [
                'view_any_booking',
                'view_booking',
                'create_booking',
                'update_booking',
                'delete_booking',
                'view_any_whatsapp_log',
                'view_whatsapp_log',
                'create_whatsapp_log',
                'update_whatsapp_log',
            ],
            'marketing' => [
                'page_SeoManagement',
                'page_RobotsTxtEditor',
                'view_any_seo_page',
                'view_seo_page',
                'update_seo_page',
                'view_any_seo_redirect',
                'view_seo_redirect',
                'create_seo_redirect',
                'update_seo_redirect',
                'view_any_seo404_monitor',
                'view_seo404_monitor',
                'update_seo404_monitor',
            ],
            'content_manager' => [
                'page_ThemeEditor',
                'view_any_article', 'view_article', 'create_article', 'update_article', 'delete_article',
                'view_any_service',
                'view_service',
                'create_service',
                'update_service',
                'delete_service',
                'view_any_video',
                'view_video',
                'create_video',
                'update_video',
                'delete_video',
                'view_any_testimonial',
                'view_testimonial',
                'create_testimonial',
                'update_testimonial',
                'delete_testimonial',
                'view_any_clinic_branch',
                'view_clinic_branch',
                'create_clinic_branch',
                'update_clinic_branch',
                'delete_clinic_branch',
            ],
            'receptionist' => [
                'view_any_booking',
                'view_booking',
                'update_booking',
                'view_any_clinic_branch',
                'view_clinic_branch',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            // Ensure permissions exist
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            Role::where('name', $roleName)->first()?->givePermissionTo($permissions);
        }
    }
}
