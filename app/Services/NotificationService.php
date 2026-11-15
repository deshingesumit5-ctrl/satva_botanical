<?php

namespace App\Services;

use App\Models\Notification;

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
}
