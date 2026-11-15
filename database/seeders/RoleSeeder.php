<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['role_code' => 'super_admin', 'role_name' => 'Super Admin', 'description' => 'Full system access across all modules', 'status' => 'Active'],
            ['role_code' => 'plant_admin', 'role_name' => 'Plant Admin', 'description' => 'Plant level administration and monitoring', 'status' => 'Active'],
            ['role_code' => 'master_admin', 'role_name' => 'Master Admin', 'description' => 'Manages all masters and system setup', 'status' => 'Active'],
            ['role_code' => 'recipe_admin', 'role_name' => 'Recipe Admin', 'description' => 'Creates and maintains manufacturing recipes', 'status' => 'Active'],
            ['role_code' => 'recipe_approver', 'role_name' => 'Recipe Approver', 'description' => 'Approves and activates recipe versions', 'status' => 'Active'],
            ['role_code' => 'production_planner', 'role_name' => 'Production Planner', 'description' => 'Creates production plans and plans material allocation', 'status' => 'Active'],
            ['role_code' => 'production_supervisor', 'role_name' => 'Production Supervisor', 'description' => 'Executes batches, logs activities, parameters and stage outputs', 'status' => 'Active'],
            ['role_code' => 'store_manager', 'role_name' => 'Store Manager', 'description' => 'Manages inventory, GRN, material issues, transfers and returns', 'status' => 'Active'],
            ['role_code' => 'purchase_manager', 'role_name' => 'Purchase Manager', 'description' => 'Manages purchase requisitions, POs and supplier relations', 'status' => 'Active'],
            ['role_code' => 'qc_manager', 'role_name' => 'QC Manager', 'description' => 'Inspects incoming materials, in-process stages and finished goods', 'status' => 'Active'],
            ['role_code' => 'sales_manager', 'role_name' => 'Sales Manager', 'description' => 'Manages sales orders, dispatches and customer communications', 'status' => 'Active'],
            ['role_code' => 'accounts_user', 'role_name' => 'Accounts User', 'description' => 'Handles invoices, payments, receipts and costing entries', 'status' => 'Active'],
            ['role_code' => 'management_viewer', 'role_name' => 'Management / Viewer', 'description' => 'Read-only access to dashboards, reports and audit trails', 'status' => 'Active'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['role_code' => $role['role_code']], $role);
        }
    }
}
