<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereNull('user_id');
        })
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
        $user = Auth::user();
        $count = Notification::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereNull('user_id');
        })
        ->where('is_read', false)
        ->count();

        return response()->json(['count' => $count]);
    }
}
