<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Standard list of ERP modules, screens and actions.
     */
    public static function getModuleMatrix(): array
    {
        return [
            'masters' => [
                'name' => 'Masters',
                'screens' => [
                    'plants' => 'Company & Plant',
                    'departments' => 'Department',
                    'warehouses' => 'Warehouse',
                    'locations' => 'Storage Location / Bin',
                    'uoms' => 'UOM Master',
                    'categories' => 'Product Category',
                    'products' => 'Product Master',
                    'materials' => 'Material Master',
                    'suppliers' => 'Supplier Master',
                    'customers' => 'Customer Master',
                    'stages' => 'Stage Master',
                    'activities' => 'Activity Master',
                    'parameters' => 'Parameter Master',
                    'equipments' => 'Equipment Master',
                    'qc_tests' => 'QC Test Master',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'export'],
            ],
            'recipe' => [
                'name' => 'Recipe Management',
                'screens' => [
                    'recipes' => 'Recipes',
                    'builder' => 'Recipe Builder',
                    'versions' => 'Recipe Versions',
                    'approval' => 'Recipe Approval',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'approve', 'reject', 'export'],
            ],
            'purchase' => [
                'name' => 'Purchase Management',
                'screens' => [
                    'requisitions' => 'Purchase Requisition',
                    'orders' => 'Purchase Order',
                    'grn' => 'GRN & Incoming QC',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'approve', 'reject', 'cancel', 'print', 'export'],
            ],
            'inventory' => [
                'name' => 'Inventory Management',
                'screens' => [
                    'stock' => 'Stock Ledger & Lot Stock',
                    'transfers' => 'Stock Transfer',
                    'adjustments' => 'Stock Adjustment',
                    'wip' => 'WIP Records',
                    'recovery' => 'Recovered Solvent',
                    'waste' => 'By-Product & Waste',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'export'],
            ],
            'production' => [
                'name' => 'Production Management',
                'screens' => [
                    'plans' => 'Production Plans',
                    'batches' => 'Batch Management',
                    'allocation' => 'Material Allocation',
                    'execution' => 'Production Execution',
                    'rework' => 'Rework Management',
                    'reconciliation' => 'Batch Reconciliation & Closure',
                    'bmr' => 'BMR Records',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'approve', 'hold', 'resume', 'rework', 'print', 'export'],
            ],
            'qc' => [
                'name' => 'Quality Control',
                'screens' => [
                    'dashboard' => 'QC Dashboard',
                    'review' => 'QC Review & Decision',
                    'history' => 'QC History',
                ],
                'actions' => ['view', 'add', 'edit', 'approve', 'reject', 'rework', 'export'],
            ],
            'commercial' => [
                'name' => 'Sales & Invoicing',
                'screens' => [
                    'orders' => 'Sales Order',
                    'dispatch' => 'Dispatch',
                    'sales_invoices' => 'Sales Invoice',
                    'purchase_invoices' => 'Purchase Invoice',
                    'payments' => 'Payments & Receipts',
                    'notes' => 'Credit & Debit Notes',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'print', 'export'],
            ],
            'costing' => [
                'name' => 'Expenses & Costing',
                'screens' => [
                    'expenses' => 'Monthly Expenses',
                    'allocation' => 'Expense Allocation',
                    'batch_cost' => 'Batch Cost Summary',
                ],
                'actions' => ['view', 'add', 'edit', 'approve', 'export'],
            ],
            'reports' => [
                'name' => 'Reports',
                'screens' => [
                    'production' => 'Production Reports',
                    'material' => 'Material Reports',
                    'qc' => 'QC Reports',
                    'recovery' => 'Recovery Reports',
                    'cost' => 'Cost Reports',
                    'commercial' => 'Sales & Purchase Reports',
                ],
                'actions' => ['view', 'print', 'export'],
            ],
            'admin' => [
                'name' => 'Roles & Access',
                'screens' => [
                    'users' => 'User Master',
                    'roles' => 'Role Master',
                    'permissions' => 'Permission Matrix',
                    'audit' => 'Audit Trail',
                    'settings' => 'System Settings',
                ],
                'actions' => ['view', 'add', 'edit', 'delete', 'export'],
            ],
        ];
    }

    /**
     * Check if a user has a specific permission.
     */
    public function userCan(User $user, string $permissionCode): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission($permissionCode);
    }
}
