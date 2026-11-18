<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationService
{
    public static function notifyRole(string $roleCode, string $title, string $message, ?string $link = null, string $type = 'general'): Notification
    {
        return Notification::create([
            'role_code' => $roleCode,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    public static function notifyUser(int $userId, string $title, string $message, ?string $link = null, string $type = 'general'): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    /**
     * Build a query of all notifications accessible to the given user.
     */
    public static function accessibleQueryForUser(User $user): Builder
    {
        $query = Notification::query();

        if ($user->isSuperAdmin()) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            });
        }

        $roleCode = $user->role?->role_code;

        $query->where(function ($q) use ($user, $roleCode) {
            $q->where('user_id', $user->id)
                ->orWhere(function ($sub) use ($roleCode) {
                    $sub->whereNull('user_id')
                        ->where(function ($r) use ($roleCode) {
                            $r->whereNull('role_code');
                            if ($roleCode) {
                                $r->orWhere('role_code', $roleCode);
                            }
                        });
                });
        });

        // Filter broadcast notifications by module/link permissions
        $allowedPrefixes = ['/dashboard', '/notifications', '/profile'];
        $modulePrefixMap = [
            '/masters'    => ['masters.plants.view', 'masters.products.view', 'masters.materials.view', 'masters.warehouses.view'],
            '/recipe'     => ['recipe.recipes.view', 'recipe.builder.view', 'recipe.approval.view'],
            '/purchase'   => ['purchase.requisitions.view', 'purchase.orders.view', 'purchase.grn.view'],
            '/inventory'  => ['inventory.stock.view', 'inventory.transfers.view', 'inventory.adjustments.view', 'inventory.wip.view', 'inventory.recovery.view', 'inventory.waste.view'],
            '/production' => ['production.plans.view', 'production.batches.view', 'production.execution.view', 'production.rework.view'],
            '/qc'         => ['qc.dashboard.view', 'qc.review.view', 'qc.history.view'],
            '/sales'      => ['commercial.orders.view', 'commercial.dispatch.view', 'commercial.sales_invoices.view'],
            '/costing'    => ['costing.expenses.view', 'costing.allocation.view', 'costing.batch_cost.view'],
            '/reports'    => ['reports.production.view', 'reports.material.view', 'reports.qc.view', 'reports.recovery.view', 'reports.cost.view', 'reports.commercial.view'],
            '/admin'      => ['admin.users.view', 'admin.roles.view', 'admin.permissions.view', 'admin.audit.view'],
            '/settings'   => ['admin.settings.view'],
        ];

        foreach ($modulePrefixMap as $prefix => $perms) {
            if ($user->hasAnyPermission($perms)) {
                $allowedPrefixes[] = $prefix;
            }
        }

        $query->where(function ($q) use ($allowedPrefixes, $user) {
            $q->where('user_id', $user->id)
                ->orWhereNull('link')
                ->orWhere('link', '');
            foreach ($allowedPrefixes as $prefix) {
                $q->orWhere('link', 'like', "%{$prefix}%");
            }
        });

        return $query;
    }

    /**
     * Build a query of unread notifications for the given user.
     */
    public static function unreadQueryForUser(User $user): Builder
    {
        return self::accessibleQueryForUser($user)
            ->where('is_read', false)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
    }

    /**
     * Count unread notifications for the given user.
     */
    public static function unreadCountForUser(User $user): int
    {
        return self::unreadQueryForUser($user)->count();
    }

    /**
     * Mark a single notification as read for the given user.
     */
    public static function markAsReadForUser(Notification $notification, User $user): void
    {
        $now = now();

        if ((int) $notification->user_id === (int) $user->id) {
            $notification->is_read = true;
            $notification->read_at = $now;
            $notification->save();
        }

        NotificationRead::upsert([[
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['notification_id', 'user_id'], ['read_at', 'updated_at']);
    }

    /**
     * Mark all accessible unread notifications as read for the given user.
     *
     * @return array<int, int> Notification IDs newly marked as read
     */
    public static function markAllReadForUser(User $user): array
    {
        $unreadIds = self::unreadQueryForUser($user)->pluck('id')->all();
        if (empty($unreadIds)) {
            return [];
        }

        $now = now();

        Notification::whereIn('id', $unreadIds)
            ->where('user_id', $user->id)
            ->update([
                'is_read' => true,
                'read_at' => $now,
            ]);

        $rows = array_map(fn ($id) => [
            'notification_id' => $id,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $unreadIds);

        NotificationRead::upsert($rows, ['notification_id', 'user_id'], ['read_at', 'updated_at']);

        return $unreadIds;
    }
}
