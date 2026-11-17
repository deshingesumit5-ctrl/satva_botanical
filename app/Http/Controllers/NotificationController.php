<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected function buildAccessibleQuery(User $user)
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

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notifications = $this->buildAccessibleQuery($user)
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->is_read = true;
        $notification->read_at = now();
        $notification->save();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function count()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $count = $this->buildAccessibleQuery($user)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
