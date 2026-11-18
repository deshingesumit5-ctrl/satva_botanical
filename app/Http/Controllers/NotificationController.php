<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected function buildAccessibleQuery(User $user)
    {
        return NotificationService::accessibleQueryForUser($user);
    }

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notifications = $this->buildAccessibleQuery($user)
            ->with(['reads' => fn ($q) => $q->where('user_id', $user->id)])
            ->latest()
            ->paginate(20);

        $justReadIds = NotificationService::markAllReadForUser($user);

        return view('notifications.index', compact('notifications', 'justReadIds'));
    }

    public function markRead($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notification = $this->buildAccessibleQuery($user)->findOrFail($id);

        NotificationService::markAsReadForUser($notification, $user);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function count()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $count = NotificationService::unreadCountForUser($user);

        return response()->json(['count' => $count]);
    }
}
