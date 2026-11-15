<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = PermissionService::getModuleMatrix();

        foreach ($matrix as $moduleKey => $module) {
            foreach ($module['screens'] as $screenKey => $screenName) {
                foreach ($module['actions'] as $action) {
                    $code = "{$moduleKey}.{$screenKey}.{$action}";
                    $name = ucfirst($action) . ' ' . $screenName;

                    Permission::updateOrCreate(
                        ['code' => $code],
                        [
                            'module' => $moduleKey,
                            'screen' => $screenKey,
                            'action' => $action,
                            'name' => $name,
                        ]
                    );
                }
            }
        }
    }
}
