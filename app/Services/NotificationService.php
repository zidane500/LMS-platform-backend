<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    // ── Envoyer à un utilisateur précis ───────────────────────
    public static function send(int $userId, string $message, string $type = 'info'): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'message' => $message,
            'lu'      => false,
        ]);
    }

    // ── Envoyer à tous les admins (admin + super_admin) ───────
    public static function notifyAdmins(string $message, string $type = 'info'): void
    {
        User::whereIn('role', ['admin', 'super_admin'])
            ->get()
            ->each(fn($admin) => self::send($admin->id, $message, $type));
    }

    // ── Envoyer à un user ET aux admins ───────────────────────
    public static function notifyUserAndAdmins(int $userId, string $messageUser, string $messageAdmin, string $type = 'info'): void
    {
        self::send($userId, $messageUser, $type);
        self::notifyAdmins($messageAdmin, $type);
    }
}