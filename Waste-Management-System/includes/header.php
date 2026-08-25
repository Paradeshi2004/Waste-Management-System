<?php

require_once __DIR__ . '/auth.php';

startSession();

$user = currentUser();

$notificationCount = 0;
$notifications = [];

if ($user) {
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT *
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 10"
    );

    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();

    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM notifications
         WHERE user_id = ?
         AND is_read = 0"
    );

    $stmt->execute([$user['id']]);
    $notificationCount = (int) $stmt->fetchColumn();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= APP_URL ?>">
            <i class="bi bi-recycle me-1"></i><?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/pages/tips.php">Recycling Tips</a></li>
                <?php if ($user): ?>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="<?= APP_URL ?>/pages/dashboard.php"
                        >
                            My Complaints
                        </a>
                    </li>

                    <!-- Step 12.4: Notifications -->

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="<?= APP_URL ?>/pages/notifications.php"
                        >
                            <i class="bi bi-bell me-1"></i>
                            Notifications
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="<?= APP_URL ?>/pages/submit.php"
                        >
                            Report Issue
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" 
                           href="#"
                           id="notificationDropdown"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false">

                            <i class="bi bi-bell fs-5"></i>

                            <?php if ($notificationCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notificationCount > 9 ? '9+' : $notificationCount ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow" 
                            aria-labelledby="notificationDropdown"
                            style="width: 350px; max-height: 450px; overflow-y: auto;">
                            <li>
                                <h6 class="dropdown-header">Notifications</h6>
                            </li>

                            <li>
                                <form method="POST" action="<?= APP_URL ?>/pages/mark-notifications-read.php" class="px-3 pb-2">

                                    <input type="hidden" name="csrf_token" value="<?= sanitize(getCsrfToken()) ?>">

                                    <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                        <i class="bi bi-check2-all me-1"></i>
                                        Mark all as read
                                    </button>
                                </form>
                            </li>

                            <?php if (empty($notifications)): ?>

                                <li>
                                    <span class="dropdown-item-text text-muted">
                                        No notifications
                                    </span>
                                </li>

                            <?php else: ?>

                                <?php foreach ($notifications as $notification): ?>
                                    <li>
                                        <a class="dropdown-item <?= !$notification['is_read'] ? 'fw-bold bg-light' : '' ?>"
                                           href="<?= APP_URL ?>/pages/complaint.php?id=<?= (int)$notification['complaint_id'] ?>&notification_id=<?= (int)$notification['id'] ?>">

                                            <div>
                                                <?= sanitize($notification['title']) ?>
                                            </div>

                                            <small class="text-muted">
                                                <?= sanitize($notification['message']) ?>
                                            </small>

                                            <div class="small text-muted mt-1">
                                                <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                            </div>

                                        </a>
                                    </li>
                                <?php endforeach; ?>

                            <?php endif; ?>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>

                                <a
                                    href="<?= APP_URL ?>/pages/notifications.php"
                                    class="dropdown-item text-center fw-semibold"
                                >

                                    <i class="bi bi-bell me-1"></i>

                                    View All Notifications

                                </a>

                            </li>
                            
                        </ul>
                    </li>

                    <?php if ($user['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/index.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= sanitize($user['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/pages/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/pages/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/pages/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light btn-sm px-3 ms-2" href="<?= APP_URL ?>/pages/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container my-4">
