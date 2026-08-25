<?php

/**
 * Create a notification for a user.
 */
function createNotification(
    int $userId,
    ?int $complaintId,
    string $title,
    string $message,
    string $type = 'complaint_update'
): void {

    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO notifications
            (user_id, complaint_id, title, message, type)
        VALUES
            (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $complaintId,
        $title,
        $message,
        $type
    ]);
}


/**
 * Get notifications for a user.
 */
function getUserNotifications(int $userId): array {

    $db = getDB();

    $stmt = $db->prepare("
        SELECT *
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Get number of unread notifications.
 */
function getUnreadNotificationCount(int $userId): int {

    $db = getDB();

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
        AND is_read = 0
    ");

    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}


/**
 * Mark one notification as read.
 */

function markNotificationAsRead(int $notificationId, int $userId): void
{
    $db = getDB();

    $stmt = $db->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE id = ?
         AND user_id = ?"
    );

    $stmt->execute([$notificationId, $userId]);
}

function markAllNotificationsAsRead(int $userId): void
{
    $db = getDB();

    $stmt = $db->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE user_id = ?
         AND is_read = 0"
    );

    $stmt->execute([$userId]);
}