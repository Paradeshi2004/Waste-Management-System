<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireLogin();

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        exit('Invalid CSRF token.');
    }

    markAllNotificationsAsRead((int) $user['id']);
}

header('Location: ' . APP_URL . '/pages/dashboard.php');
exit;
