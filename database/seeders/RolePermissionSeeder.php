<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        PermissionService::ensurePermissionsExist();

        $permissions = Permission::all();
        $roles = Role::all()->keyBy('role_code');

        // Super Admin gets all permissions
        if (isset($roles['super_admin'])) {
            $superAdmin = $roles['super_admin'];
            $syncData = [];
            foreach ($permissions as $p) {
                $syncData[$p->id] = ['created_at' => now(), 'updated_at' => now()];
            }
            $superAdmin->permissions()->sync($syncData);
        }

        // Plant Admin gets all operational permissions including Authorize Stage
        if (isset($roles['plant_admin'])) {
            $plantAdmin = $roles['plant_admin'];
            $paIds = $permissions->filter(function ($p) {
                return in_array($p->module, ['masters', 'recipe', 'purchase', 'inventory', 'production', 'qc', 'commercial', 'costing', 'reports']);
            })->pluck('id');
            $plantAdmin->permissions()->syncWithoutDetaching($paIds);
        }

        // Production Supervisor gets production (including Authorize Stage), inventory, qc view
        if (isset($roles['production_supervisor'])) {
            $pRole = $roles['production_supervisor'];
            $pIds = $permissions->filter(function ($p) {
                return in_array($p->module, ['production', 'masters']) ||
                    ($p->module === 'inventory' && in_array($p->action, ['view', 'add'])) ||
                    ($p->module === 'qc' && $p->action === 'view') ||
                    ($p->module === 'recipe' && $p->action === 'view');
            })->pluck('id');
            $pRole->permissions()->syncWithoutDetaching($pIds);
        }

        // Store Manager gets inventory, purchase grn, masters view
        if (isset($roles['store_manager'])) {
            $sRole = $roles['store_manager'];
            $sIds = $permissions->filter(function ($p) {
                return $p->module === 'inventory' ||
                    ($p->module === 'purchase' && in_array($p->screen, ['grn', 'requisitions'])) ||
                    ($p->module === 'masters' && $p->action === 'view');
            })->pluck('id');
            $sRole->permissions()->syncWithoutDetaching($sIds);
        }

        // QC Manager gets qc module, masters view
        if (isset($roles['qc_manager'])) {
            $qcRole = $roles['qc_manager'];
            $qcIds = $permissions->filter(function ($p) {
                return $p->module === 'qc' ||
                    ($p->module === 'production' && in_array($p->action, ['view', 'rework'])) ||
                    ($p->module === 'masters' && $p->screen === 'qc_tests');
            })->pluck('id');
            $qcRole->permissions()->syncWithoutDetaching($qcIds);
        }

        // Recipe Admin gets recipe and process masters
        if (isset($roles['recipe_admin'])) {
            $rRole = $roles['recipe_admin'];
            $rIds = $permissions->filter(function ($p) {
                return $p->module === 'recipe' ||
                    ($p->module === 'masters' && in_array($p->screen, ['stages', 'activities', 'parameters', 'equipments', 'qc_tests', 'products', 'materials']));
            })->pluck('id');
            $rRole->permissions()->syncWithoutDetaching($rIds);
        }
    }
}
