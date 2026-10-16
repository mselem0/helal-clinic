<?php

namespace Modules\AccessControl\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AccessControl\Filament\Resources\RoleResource;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn ($record) => in_array($record->name, ['super_admin', 'panel_user'])),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $role = $this->record;
        $permissions = $role->permissions->pluck('name')->toArray();
        $mapping = $this->getPermissionMapping();

        $data['modules'] = [];
        foreach ($mapping as $module => $perms) {
            // If the role has ANY of the permissions in this module, we consider it enabled
            // Or we could require ALL permissions. Let's go with ANY for better UX when transitioning from granular to simple.
            $hasAny = false;
            foreach ($perms as $p) {
                if (in_array($p, $permissions)) {
                    $hasAny = true;
                    break;
                }
            }
            $data['modules'][$module] = $hasAny;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->modules = $data['modules'] ?? [];
        unset($data['modules']);

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->record;

        // Skip for super_admin if we want to keep it pristine
        if ($role->name === 'super_admin') {
            return;
        }

        $permissions = [];
        $mapping = $this->getPermissionMapping();

        foreach ($this->modules as $module => $enabled) {
            if ($enabled && isset($mapping[$module])) {
                foreach ($mapping[$module] as $permName) {
                    Permission::findOrCreate($permName, 'web');
                    $permissions[] = $permName;
                }
            }
        }

        $role->syncPermissions($permissions);
    }

    protected function getPermissionMapping(): array
    {
        return [
            'bookings' => ['view_Booking', 'view_any_Booking', 'create_Booking', 'update_Booking', 'delete_Booking', 'delete_any_Booking'],
            'services' => [
                'view_Service', 'view_any_Service', 'create_Service', 'update_Service', 'delete_Service', 'delete_any_Service',
                'view_Video', 'view_any_Video', 'create_Video', 'update_Video', 'delete_Video', 'delete_any_Video',
            ],
            'content' => [
                'page_AboutDoctor', 'page_HeroSettings', 'page_ThemeEditor',
                'view_Testimonial', 'view_any_Testimonial', 'create_Testimonial', 'update_Testimonial', 'delete_Testimonial', 'delete_any_Testimonial',
                'view_ClinicBranch', 'view_any_ClinicBranch', 'create_ClinicBranch', 'update_ClinicBranch', 'delete_ClinicBranch', 'delete_any_ClinicBranch',
                'view_PageSection', 'view_any_PageSection', 'create_PageSection', 'update_PageSection',
            ],
            'whatsapp' => [
                'view_WhatsappTemplate', 'view_any_WhatsappTemplate', 'create_WhatsappTemplate', 'update_WhatsappTemplate', 'delete_WhatsappTemplate',
                'view_WhatsappLog', 'view_any_WhatsappLog', 'page_WhatsappConversations',
            ],
            'seo' => [
                'page_SeoManagement', 'page_SeoOverview', 'page_GlobalSeoSettings', 'page_RobotsTxtEditor', 'page_SitemapManager', 'page_Seo404Monitor',
                'view_SeoPage', 'view_any_SeoPage', 'create_SeoPage', 'update_SeoPage', 'delete_SeoPage',
                'view_SeoRedirect', 'view_any_SeoRedirect', 'create_SeoRedirect', 'update_SeoRedirect', 'delete_SeoRedirect',
                'view_SeoSchema', 'view_any_SeoSchema', 'create_SeoSchema', 'update_SeoSchema', 'delete_SeoSchema',
            ],
            'settings' => [
                'page_SiteSettings', 'view_Activity', 'view_any_Activity',
                'view_User', 'view_any_User', 'create_User', 'update_User', 'delete_User',
                'view_role', 'view_any_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            ],
        ];
    }
}
