<?php
//Admin broadcast
function createNotification($conn, $title, $message, $targetRole) {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (title, message, target_role) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $title, $message, $targetRole);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

//all admin notification
function getAllNotifications($conn) {
    $r = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC");
    return mysqli_fetch_all($r, MYSQLI_ASSOC);
}

function deleteNotification($conn, $id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// Single user notification
function notifyUser($conn, $userId, $title, $message) {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, title, message, target_role) VALUES (?, ?, ?, 'all')");
    mysqli_stmt_bind_param($stmt, 'iss', $userId, $title, $message);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function getNotificationsForUser($conn, $userId, $role) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM notifications
                                    WHERE user_id = ?
                                       OR (user_id IS NULL AND (target_role = 'all' OR target_role = ?))
                                    ORDER BY created_at DESC
                                    LIMIT 50");
    mysqli_stmt_bind_param($stmt, 'is', $userId, $role);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

//Count of unread notifications
function getUnreadNotificationCount($conn, $userId, $role) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM notifications
                                    WHERE is_read = 0
                                      AND (user_id = ? OR (user_id IS NULL AND (target_role = 'all' OR target_role = ?)))");
    mysqli_stmt_bind_param($stmt, 'is', $userId, $role);
    mysqli_stmt_execute($stmt);
    $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);
    return (int)$count;
}

// mark notifications as read
function markNotificationRead($conn, $notificationId) {
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $notificationId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function markAllNotificationsRead($conn, $userId, $role) {
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1
                                    WHERE user_id = ? OR (user_id IS NULL AND (target_role = 'all' OR target_role = ?))");
    mysqli_stmt_bind_param($stmt, 'is', $userId, $role);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
?>
