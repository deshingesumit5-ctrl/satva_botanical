<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PermissionService
{
    /**
     * Return all supported action columns from config/modules.php.
     */
    public static function getActions(): array
    {
        return config('modules.actions', [
            'view'     => 'View',
            'add'      => 'Add',
            'edit'     => 'Edit',
            'delete'   => 'Delete',
            'approve'  => 'Approve',
            'export'   => 'Export',
            'reject'   => 'Reject',
            'hold'     => 'Hold',
            'resume'   => 'Resume',
            'rework'   => 'Rework',
            'print'    => 'Print',
            'cancel'   => 'Cancel',
            'override' => 'Override',
        ]);
    }

    /**
     * Return all module items from config/modules.php.
     */
    public static function getItems(): array
    {
        return config('modules.items', []);
    }

    /**
     * Return module items grouped by section in the configured order.
     */
    public static function getItemsBySection(): array
    {
        $sections = config('modules.sections', []);
        $items = self::getItems();
        $grouped = [];

        foreach ($sections as $section) {
            $grouped[$section] = [];
        }

        foreach ($items as $item) {
            $sec = $item['section'] ?? 'Other';
            if (!isset($grouped[$sec])) {
                $grouped[$sec] = [];
            }
            $grouped[$sec][] = $item;
        }

        return array_filter($grouped, fn($list) => !empty($list));
    }

    /**
     * Legacy helper for PermissionSeeder and backward compatibility.
     */
    public static function getModuleMatrix(): array
    {
        $matrix = [];
        foreach (self::getItems() as $item) {
            $modKey = $item['module'];
            $scrKey = $item['screen'];
            if (!isset($matrix[$modKey])) {
                $matrix[$modKey] = [
                    'name'    => $item['section'],
                    'screens' => [],
                    'actions' => [],
                ];
            }
            $matrix[$modKey]['screens'][$scrKey] = $item['label'];
            $matrix[$modKey]['actions'] = array_values(array_unique(array_merge($matrix[$modKey]['actions'], $item['actions'])));
            $matrix[$modKey]['screen_actions'][$scrKey] = $item['actions'];
        }

        return $matrix;
    }

    /**
     * Ensure all permissions defined in config/modules.php exist in the database.
     */
    public static function ensurePermissionsExist(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (self::getItems() as $item) {
            foreach ($item['actions'] as $action) {
                $code = "{$item['key']}.{$action}";
                $name = ucfirst($action) . ' ' . $item['label'];

                Permission::firstOrCreate(
                    ['code' => $code],
                    [
                        'module' => $item['module'],
                        'screen' => $item['screen'],
                        'action' => $action,
                        'name'   => $name,
                    ]
                );
            }
        }
    }

    /**
     * Normalize submitted permission codes:
     * If any action (add, edit, delete, approve, etc.) is checked for a module/screen,
     * automatically include its '.view' permission.
     */
    public static function normalizePermissionCodes(array $codes): array
    {
        $normalized = [];

        foreach ($codes as $code) {
            if (!is_string($code) || trim($code) === '') {
                continue;
            }
            $code = trim($code);
            $normalized[$code] = true;

            $parts = explode('.', $code);
            if (count($parts) === 3 && $parts[2] !== 'view') {
                $viewCode = "{$parts[0]}.{$parts[1]}.view";
                $normalized[$viewCode] = true;
            }
        }

        return array_keys($normalized);
    }

    /**
     * Sync permissions for a role by permission codes, ensuring DB rows exist and clearing cache.
     */
    public static function syncRolePermissions(Role $role, array $permissionCodes): void
    {
        self::ensurePermissionsExist();

        $normalizedCodes = self::normalizePermissionCodes($permissionCodes);
        $permissionIds = Permission::whereIn('code', $normalizedCodes)->pluck('id')->toArray();

        $role->permissions()->sync($permissionIds);
        $role->unsetRelation('permissions');

        Cache::flush();
    }

    /**
     * Map a route name (and HTTP method) to required permission code(s).
     * Returns null if route does not require a specific module permission (e.g. dashboard, profile, logout).
     * Returns string or array of strings (any match grants access).
     */
    public static function resolveRoutePermission(?string $routeName, string $method = 'GET', ?string $uri = null): string|array|null
    {
        $method = strtoupper($method);

        // Handle unnamed routes by URI
        if (!$routeName && $uri) {
            $cleanUri = trim($uri, '/');
            if ($cleanUri === 'admin/permissions/update') {
                return ['admin.permissions.edit', 'admin.roles.edit'];
            }
            if ($cleanUri === 'change-password') {
                return null;
            }
        }

        if (!$routeName) {
            return null;
        }

        // Always-allowed authenticated routes
        $publicAuthRoutes = [
            'login',
            'login.post',
            'logout',
            'dashboard',
            'profile',
            'profile.update',
            'password',
            'password.update',
            'change-password',
            'notifications.index',
            'notifications.read',
            'notifications.count',
        ];

        if (in_array($routeName, $publicAuthRoutes, true)) {
            return null;
        }

        // 1. MASTERS: masters.{resource}.{action}
        if (str_starts_with($routeName, 'masters.')) {
            $parts = explode('.', $routeName);
            $resource = str_replace('-', '_', $parts[1] ?? '');
            $routeAction = $parts[2] ?? 'index';
            $permAction = self::mapResourceAction($routeAction, $method);
            return "masters.{$resource}.{$permAction}";
        }

        // 2. ADMIN: admin.*
        if (str_starts_with($routeName, 'admin.')) {
            if (str_starts_with($routeName, 'admin.users.')) {
                $routeAction = substr($routeName, strlen('admin.users.'));
                $permAction = self::mapResourceAction($routeAction, $method);
                return "admin.users.{$permAction}";
            }
            if (str_starts_with($routeName, 'admin.roles.')) {
                $routeAction = substr($routeName, strlen('admin.roles.'));
                $permAction = self::mapResourceAction($routeAction, $method);
                return "admin.roles.{$permAction}";
            }
            if (str_starts_with($routeName, 'admin.permissions.')) {
                $routeAction = substr($routeName, strlen('admin.permissions.'));
                if ($routeAction === 'index' && $method === 'GET') {
                    return 'admin.permissions.view';
                }
                return ['admin.permissions.edit', 'admin.permissions.add'];
            }
            if (str_starts_with($routeName, 'admin.audit-trail.')) {
                return 'admin.audit.view';
            }
        }

        // 3. SETTINGS: settings.*
        if (str_starts_with($routeName, 'settings.')) {
            if (str_ends_with($routeName, '.update') || in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return 'admin.settings.edit';
            }
            return 'admin.settings.view';
        }

        // 4. RECIPE: recipe.*
        if (str_starts_with($routeName, 'recipe.')) {
            return match ($routeName) {
                'recipe.index', 'recipe.recipes.index', 'recipe.show' => ['recipe.recipes.view', 'recipe.builder.view', 'recipe.versions.view', 'recipe.approval.view'],
                'recipe.create', 'recipe.store', 'recipe.copy' => ['recipe.recipes.add', 'recipe.builder.add'],
                'recipe.edit', 'recipe.submit', 'recipe.activate', 'recipe.deactivate', 'recipe.toggle-status' => ['recipe.recipes.edit', 'recipe.builder.edit'],
                'recipe.destroy' => 'recipe.recipes.delete',
                'recipe.approve' => ['recipe.approval.approve', 'recipe.recipes.approve'],
                'recipe.reject' => ['recipe.approval.reject', 'recipe.recipes.reject', 'recipe.approval.approve'],
                'recipe.versions' => ['recipe.versions.view', 'recipe.recipes.view'],
                'recipe.new-version', 'recipe.clone-version' => ['recipe.versions.add', 'recipe.recipes.add', 'recipe.recipes.edit'],
                'recipe.builder.step' => ['recipe.builder.view', 'recipe.recipes.view'],
                'recipe.builder.save-step' => ['recipe.builder.edit', 'recipe.builder.add', 'recipe.recipes.edit', 'recipe.recipes.add'],
                default => 'recipe.recipes.view',
            };
        }

        // 5. PURCHASE: purchase.*
        if (str_starts_with($routeName, 'purchase.')) {
            if (str_starts_with($routeName, 'purchase.requisitions.')) {
                $act = substr($routeName, strlen('purchase.requisitions.'));
                return match ($act) {
                    'index', 'show' => 'purchase.requisitions.view',
                    'create', 'store', 'convert-to-po' => 'purchase.requisitions.add',
                    'edit', 'update', 'submit' => 'purchase.requisitions.edit',
                    'destroy' => 'purchase.requisitions.delete',
                    'approve' => 'purchase.requisitions.approve',
                    'reject' => ['purchase.requisitions.reject', 'purchase.requisitions.approve'],
                    default => 'purchase.requisitions.view',
                };
            }
            if (str_starts_with($routeName, 'purchase.orders.')) {
                $act = substr($routeName, strlen('purchase.orders.'));
                return match ($act) {
                    'index', 'show' => 'purchase.orders.view',
                    'create', 'store' => 'purchase.orders.add',
                    'edit', 'update' => 'purchase.orders.edit',
                    'destroy' => 'purchase.orders.delete',
                    'approve' => 'purchase.orders.approve',
                    'cancel' => ['purchase.orders.cancel', 'purchase.orders.delete', 'purchase.orders.edit'],
                    'print' => ['purchase.orders.print', 'purchase.orders.view'],
                    default => 'purchase.orders.view',
                };
            }
            if (str_starts_with($routeName, 'purchase.grn.')) {
                $act = substr($routeName, strlen('purchase.grn.'));
                return match ($act) {
                    'index', 'show' => 'purchase.grn.view',
                    'create', 'store', 'from-po', 'store-from-po' => 'purchase.grn.add',
                    'edit', 'update' => 'purchase.grn.edit',
                    'destroy' => 'purchase.grn.delete',
                    default => 'purchase.grn.view',
                };
            }
        }

        // 6. INVENTORY: inventory.*
        if (str_starts_with($routeName, 'inventory.')) {
            if ($routeName === 'inventory.dashboard') {
                return [
                    'inventory.stock.view',
                    'inventory.transfers.view',
                    'inventory.adjustments.view',
                    'inventory.wip.view',
                    'inventory.recovery.view',
                    'inventory.waste.view',
                ];
            }
            if (str_starts_with($routeName, 'inventory.stock.')) {
                $act = substr($routeName, strlen('inventory.stock.'));
                return match ($act) {
                    'index', 'ledger', 'lots' => 'inventory.stock.view',
                    'opening-entry-form' => ['inventory.stock.add', 'inventory.stock.view'],
                    'opening-entry', 'store-opening-entry' => 'inventory.stock.add',
                    default => 'inventory.stock.view',
                };
            }
            if (str_starts_with($routeName, 'inventory.transfers.')) {
                $act = substr($routeName, strlen('inventory.transfers.'));
                return 'inventory.transfers.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'inventory.adjustments.')) {
                $act = substr($routeName, strlen('inventory.adjustments.'));
                return 'inventory.adjustments.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'inventory.wip.')) {
                $act = substr($routeName, strlen('inventory.wip.'));
                return $act === 'index' ? 'inventory.wip.view' : ['inventory.wip.edit', 'inventory.wip.add'];
            }
            if (str_starts_with($routeName, 'inventory.recovery.')) {
                $act = substr($routeName, strlen('inventory.recovery.'));
                return $act === 'index' ? 'inventory.recovery.view' : ['inventory.recovery.edit', 'inventory.recovery.add'];
            }
            if (str_starts_with($routeName, 'inventory.waste.')) {
                $act = substr($routeName, strlen('inventory.waste.'));
                return $act === 'index' ? 'inventory.waste.view' : ['inventory.waste.edit', 'inventory.waste.add'];
            }
        }

        // 7. PRODUCTION: production.*
        if (str_starts_with($routeName, 'production.')) {
            if ($routeName === 'production.dashboard') {
                return [
                    'production.plans.view',
                    'production.batches.view',
                    'production.allocation.view',
                    'production.execution.view',
                    'production.rework.view',
                    'production.reconciliation.view',
                    'production.bmr.view',
                ];
            }
            if (str_starts_with($routeName, 'production.plans.')) {
                $act = substr($routeName, strlen('production.plans.'));
                return match ($act) {
                    'index', 'show', 'requirements' => 'production.plans.view',
                    'create', 'store' => 'production.plans.add',
                    'edit', 'update' => 'production.plans.edit',
                    'destroy' => 'production.plans.delete',
                    'approve' => 'production.plans.approve',
                    'reject' => ['production.plans.reject', 'production.plans.approve'],
                    default => 'production.plans.view',
                };
            }
            if (in_array($routeName, ['production.batches.allocate'])) {
                return ['production.allocation.view', 'production.batches.view'];
            }
            if (in_array($routeName, ['production.batches.allocate.save', 'production.batches.save-allocation', 'production.batches.consume', 'production.batches.return-material'])) {
                return ['production.allocation.add', 'production.allocation.edit', 'production.batches.edit'];
            }
            if (in_array($routeName, ['production.batches.execute', 'production.execution.show', 'production.execution.stage'])) {
                return ['production.execution.view', 'production.batches.view'];
            }
            if ($routeName === 'production.execution.authorize-stage') {
                return ['production.authorize_stage.approve', 'production.authorize_stage.view', 'production.authorize_stage.override'];
            }
            if (in_array($routeName, ['production.batches.execute.stage', 'production.execution.save-stage', 'production.execution.start-stage', 'production.batches.execute.stage.complete', 'production.execution.complete-stage', 'production.execution.add-repetition', 'production.execution.skip-stage'])) {
                return ['production.execution.edit', 'production.execution.add'];
            }
            if (in_array($routeName, ['production.batches.reconcile', 'production.reconciliation.show'])) {
                return ['production.reconciliation.view', 'production.batches.view'];
            }
            if (in_array($routeName, ['production.batches.reconcile.save', 'production.reconciliation.close'])) {
                return ['production.reconciliation.edit', 'production.reconciliation.approve', 'production.batches.edit'];
            }
            if (in_array($routeName, ['production.batches.bmr', 'production.bmr.show'])) {
                return ['production.bmr.view', 'production.batches.view'];
            }
            if (in_array($routeName, ['production.batches.bmr.pdf', 'production.bmr.pdf'])) {
                return ['production.bmr.print', 'production.bmr.export', 'production.bmr.view'];
            }
            if (str_starts_with($routeName, 'production.rework.')) {
                $act = substr($routeName, strlen('production.rework.'));
                return 'production.rework.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'production.batches.')) {
                $act = substr($routeName, strlen('production.batches.'));
                return match ($act) {
                    'index', 'show' => 'production.batches.view',
                    'create', 'store' => 'production.batches.add',
                    'edit', 'update', 'start' => ['production.batches.edit', 'production.batches.add'],
                    'destroy' => 'production.batches.delete',
                    'hold' => ['production.batches.hold', 'production.batches.edit'],
                    'resume' => ['production.batches.resume', 'production.batches.edit'],
                    'close' => ['production.batches.approve', 'production.reconciliation.approve', 'production.batches.edit'],
                    default => 'production.batches.view',
                };
            }
        }

        // 8. QC: qc.*
        if (str_starts_with($routeName, 'qc.')) {
            return match ($routeName) {
                'qc.dashboard', 'qc.pending' => ['qc.dashboard.view', 'qc.review.view'],
                'qc.history' => ['qc.history.view', 'qc.dashboard.view'],
                'qc.show', 'qc.review' => ['qc.review.view', 'qc.dashboard.view'],
                'qc.save-results' => ['qc.review.edit', 'qc.review.add'],
                'qc.approve' => 'qc.review.approve',
                'qc.reject' => ['qc.review.reject', 'qc.review.approve'],
                'qc.rework' => ['qc.review.rework', 'qc.review.approve'],
                default => 'qc.dashboard.view',
            };
        }

        // 9. SALES: sales.* -> commercial.*
        if (str_starts_with($routeName, 'sales.')) {
            if (str_starts_with($routeName, 'sales.orders.')) {
                $act = substr($routeName, strlen('sales.orders.'));
                return match ($act) {
                    'approve' => ['commercial.orders.approve', 'commercial.orders.edit'],
                    'cancel'  => ['commercial.orders.cancel', 'commercial.orders.delete', 'commercial.orders.edit'],
                    default   => 'commercial.orders.' . self::mapResourceAction($act, $method),
                };
            }
            if (str_starts_with($routeName, 'sales.dispatch.')) {
                $act = substr($routeName, strlen('sales.dispatch.'));
                return 'commercial.dispatch.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'sales.invoices.')) {
                $act = substr($routeName, strlen('sales.invoices.'));
                if ($act === 'pdf') {
                    return ['commercial.sales_invoices.print', 'commercial.sales_invoices.export', 'commercial.sales_invoices.view'];
                }
                return 'commercial.sales_invoices.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'sales.purchase-invoices.')) {
                $act = substr($routeName, strlen('sales.purchase-invoices.'));
                return 'commercial.purchase_invoices.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'sales.payments.')) {
                $act = substr($routeName, strlen('sales.payments.'));
                return 'commercial.payments.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'sales.receipts.')) {
                $act = substr($routeName, strlen('sales.receipts.'));
                return 'commercial.payments.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'sales.notes.')) {
                $act = substr($routeName, strlen('sales.notes.'));
                return 'commercial.notes.' . self::mapResourceAction($act, $method);
            }
        }

        // 10. COSTING: costing.*
        if (str_starts_with($routeName, 'costing.')) {
            if (str_starts_with($routeName, 'costing.expenses.')) {
                $act = substr($routeName, strlen('costing.expenses.'));
                if (in_array($act, ['approve', 'post'])) {
                    return ['costing.expenses.approve', 'costing.expenses.edit'];
                }
                return 'costing.expenses.' . self::mapResourceAction($act, $method);
            }
            if (str_starts_with($routeName, 'costing.allocation.')) {
                $act = substr($routeName, strlen('costing.allocation.'));
                return $act === 'index' ? 'costing.allocation.view' : ['costing.allocation.add', 'costing.allocation.edit'];
            }
            if (str_starts_with($routeName, 'costing.batch-cost.')) {
                return 'costing.batch_cost.view';
            }
        }

        // 11. REPORTS: reports.*
        if (str_starts_with($routeName, 'reports.')) {
            $parts = explode('.', $routeName);
            $reportType = $parts[1] ?? 'production';
            $subAction = $parts[2] ?? 'view';
            if ($subAction === 'export') {
                return "reports.{$reportType}.export";
            }
            if ($subAction === 'pdf') {
                return ["reports.{$reportType}.print", "reports.{$reportType}.export", "reports.{$reportType}.view"];
            }
            return "reports.{$reportType}.view";
        }

        return null;
    }

    /**
     * Resolve the base module.screen key for the current route so UI can check add/edit/delete/approve/export permissions.
     */
    public static function resolveScreenKeyForRoute(?string $routeName): ?string
    {
        $resolved = self::resolveRoutePermission($routeName, 'GET');
        if (!$resolved) {
            return null;
        }
        $first = is_array($resolved) ? ($resolved[0] ?? null) : $resolved;
        if (!$first) {
            return null;
        }
        $parts = explode('.', $first);
        if (count($parts) >= 2) {
            return "{$parts[0]}.{$parts[1]}";
        }
        return null;
    }

    protected static function mapResourceAction(string $routeAction, string $method): string
    {
        return match ($routeAction) {
            'index', 'show' => 'view',
            'create', 'store' => 'add',
            'edit', 'update', 'toggle-status', 'reset-password' => 'edit',
            'destroy' => 'delete',
            'approve' => 'approve',
            'reject' => 'reject',
            'export' => 'export',
            'print' => 'print',
            'cancel' => 'cancel',
            default => in_array($method, ['POST']) ? 'add' : (in_array($method, ['PUT', 'PATCH']) ? 'edit' : ($method === 'DELETE' ? 'delete' : 'view')),
        };
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
