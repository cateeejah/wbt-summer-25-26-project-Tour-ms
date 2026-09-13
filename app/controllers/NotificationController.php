<?php
function notificationsCtrl($conn) {
    $userId = $_SESSION['user']['id'];
    $role = $_SESSION['user']['role'];

    //notification marking
    if (isset($_GET['mark_read']) && isset($_GET['id'])) {
        csrf_check();
        markNotificationRead($conn, intval($_GET['id']));
        header('Location: index.php?page=notifications');
        exit;
    }

    if (isset($_GET['mark_all_read'])) {
        csrf_check();
        markAllNotificationsRead($conn, $userId, $role);
        header('Location: index.php?page=notifications');
        exit;
    }

    $notifications = getNotificationsForUser($conn, $userId, $role);
    require 'app/views/notifications/inbox.php';
}
?>
