<?php
/**
 * Dashboard-to-dashboard support messaging: one running conversation
 * thread per user, stored flat in `support_messages` and scoped by
 * user_id regardless of which side sent a given row.
 */

declare(strict_types=1);

if (!function_exists('support_send_user_message')) {
    function support_send_user_message(int $userId, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['success' => false, 'message' => 'Please enter a message.'];
        }

        $stmt = db()->prepare(
            'INSERT INTO support_messages (user_id, sender, message, is_read_by_user, is_read_by_admin, created_at)
             VALUES (?, ?, ?, 1, 0, NOW())'
        );
        $stmt->execute([$userId, 'user', $message]);

        return ['success' => true, 'message' => 'Message sent.'];
    }
}

if (!function_exists('support_send_admin_reply')) {
    function support_send_admin_reply(int $userId, int $adminId, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['success' => false, 'message' => 'Please enter a reply.'];
        }

        $stmt = db()->prepare(
            'INSERT INTO support_messages (user_id, sender, admin_id, message, is_read_by_user, is_read_by_admin, created_at)
             VALUES (?, ?, ?, ?, 0, 1, NOW())'
        );
        $stmt->execute([$userId, 'admin', $adminId, $message]);

        notify_user($userId, 'New support reply', 'Our support team has replied to your message.', NOTIFY_TYPE_SUPPORT);

        return ['success' => true, 'message' => 'Reply sent.'];
    }
}

if (!function_exists('support_thread_for_user')) {
    function support_thread_for_user(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT sm.*, a.full_name AS admin_name
             FROM support_messages sm
             LEFT JOIN admins a ON a.id = sm.admin_id
             WHERE sm.user_id = ?
             ORDER BY sm.created_at ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}

if (!function_exists('support_mark_read_by_user')) {
    function support_mark_read_by_user(int $userId): void
    {
        $stmt = db()->prepare(
            "UPDATE support_messages SET is_read_by_user = 1 WHERE user_id = ? AND sender = 'admin' AND is_read_by_user = 0"
        );
        $stmt->execute([$userId]);
    }
}

if (!function_exists('support_mark_read_by_admin')) {
    function support_mark_read_by_admin(int $userId): void
    {
        $stmt = db()->prepare(
            "UPDATE support_messages SET is_read_by_admin = 1 WHERE user_id = ? AND sender = 'user' AND is_read_by_admin = 0"
        );
        $stmt->execute([$userId]);
    }
}

if (!function_exists('support_unread_count_for_user')) {
    function support_unread_count_for_user(int $userId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) AS c FROM support_messages WHERE user_id = ? AND sender = 'admin' AND is_read_by_user = 0"
        );
        $stmt->execute([$userId]);

        return (int) $stmt->fetch()['c'];
    }
}

if (!function_exists('support_unread_total_for_admin')) {
    function support_unread_total_for_admin(): int
    {
        $stmt = db()->query(
            "SELECT COUNT(*) AS c FROM support_messages WHERE sender = 'user' AND is_read_by_admin = 0"
        );

        return (int) $stmt->fetch()['c'];
    }
}

/**
 * One row per user who has ever sent/received a support message,
 * ordered by most recent activity, with an unread-by-admin count and
 * a preview of the last message - powers the admin inbox list.
 */
if (!function_exists('support_conversation_list_for_admin')) {
    function support_conversation_list_for_admin(): array
    {
        $sql = "SELECT
                    u.id AS user_id,
                    u.username,
                    u.full_name,
                    u.email,
                    MAX(sm.created_at) AS last_message_at,
                    SUM(CASE WHEN sm.sender = 'user' AND sm.is_read_by_admin = 0 THEN 1 ELSE 0 END) AS unread_count
                FROM support_messages sm
                INNER JOIN users u ON u.id = sm.user_id
                GROUP BY u.id, u.username, u.full_name, u.email
                ORDER BY last_message_at DESC";

        return db()->query($sql)->fetchAll();
    }
}

if (!function_exists('support_last_message_preview')) {
    function support_last_message_preview(int $userId): ?string
    {
        $stmt = db()->prepare('SELECT message FROM support_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ? $row['message'] : null;
    }
}
