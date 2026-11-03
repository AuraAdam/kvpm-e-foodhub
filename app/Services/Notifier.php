<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * In-app notifications only. No e-mail or WhatsApp messages are sent.
 */
final class Notifier
{
    public static function send(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        Database::insert('notifications', [
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => mb_substr($title, 0, 150),
            'message'    => mb_substr($message, 0, 500),
            'link'       => $link,
            'is_read'    => 0,
            'created_at' => now(),
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
    }

    /** Send a notification to every active administrator. */
    public static function admins(string $type, string $title, string $message, ?string $link = null): void
    {
        foreach (Database::fetchAll("SELECT id FROM users WHERE role = 'admin' AND status = 'active'") as $admin) {
            self::send((int) $admin['id'], $type, $title, $message, $link);
        }
    }
}
