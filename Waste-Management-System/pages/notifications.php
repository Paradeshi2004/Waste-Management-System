<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$user = currentUser();

$csrfToken = getCsrfToken();

$userId = (int)$user['id'];

$notifications = getUserNotifications($userId);

$unreadCount = getUnreadNotificationCount($userId);

/*
 * Mark one notification as read.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    $notificationId = (int)(
        $_POST['notification_id'] ?? 0
    );

    if (
        $action === 'mark_read' &&
        $notificationId > 0
    ) {

        markNotificationAsRead(
            $notificationId,
            $userId
        );

        header(
            'Location: ' .
            APP_URL .
            '/pages/notifications.php'
        );

        exit;
    }


    /*
     * Mark all notifications as read.
     */
    if ($action === 'mark_all_read') {

        markAllNotificationsAsRead($userId);

        header(
            'Location: ' .
            APP_URL .
            '/pages/notifications.php'
        );

        exit;
    }
}


include __DIR__ . '/../includes/header.php';

?>

<!-- Page Header -->

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

    <div>

        <h2 class="fw-bold mb-1">

            <i class="bi bi-bell text-success me-2"></i>

            Notifications

        </h2>

        <p class="text-muted mb-0">

            Stay updated about your complaints.

        </p>

    </div>


    <?php if ($unreadCount > 0): ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= sanitize($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="mark_all_read"
            >

            <button
                type="submit"
                class="btn btn-outline-success"
            >

                <i class="bi bi-check2-all me-1"></i>

                Mark All as Read

            </button>

        </form>

    <?php endif; ?>

</div>


<!-- Notification Summary -->

<div class="row g-3 mb-4">

    <div class="col-md-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="rounded-circle bg-success bg-opacity-10 p-3"
                    >

                        <i class="bi bi-bell text-success fs-4"></i>

                    </div>

                    <div>

                        <div class="small text-muted">
                            Total Notifications
                        </div>

                        <h4 class="fw-bold mb-0">
                            <?= count($notifications) ?>
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex align-items-center gap-3">

                    <div
                        class="rounded-circle bg-warning bg-opacity-10 p-3"
                    >

                        <i class="bi bi-envelope text-warning fs-4"></i>

                    </div>

                    <div>

                        <div class="small text-muted">
                            Unread
                        </div>

                        <h4 class="fw-bold mb-0">
                            <?= $unreadCount ?>
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- Notifications -->

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <div class="d-flex justify-content-between align-items-center">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-list-ul me-2"></i>

                Notification History

            </h5>

            <?php if ($unreadCount > 0): ?>

                <span class="badge bg-success">

                    <?= $unreadCount ?> unread

                </span>

            <?php endif; ?>

        </div>

    </div>


    <div class="card-body p-0">

        <?php if (empty($notifications)): ?>

            <div class="text-center py-5 px-3">

                <div
                    class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                    style="width:70px;height:70px;"
                >

                    <i class="bi bi-bell-slash text-muted fs-2"></i>

                </div>

                <h5 class="fw-bold">
                    No notifications
                </h5>

                <p class="text-muted mb-0">

                    You're all caught up.

                </p>

            </div>

        <?php else: ?>

            <div class="list-group list-group-flush">

                <?php foreach ($notifications as $notification): ?>

                    <?php

                    $isRead = !empty(
                        $notification['is_read']
                    );

                    $notificationId =
                        (int)$notification['id'];

                    $complaintId =
                        (int)($notification['complaint_id'] ?? 0);

                    ?>

                    <div
                        class="list-group-item px-3 px-md-4 py-3
                        <?= !$isRead ? 'bg-success bg-opacity-10' : '' ?>"
                    >

                        <div class="d-flex gap-3">

                            <!-- Icon -->

                            <div>

                                <div
                                    class="rounded-circle
                                    <?= !$isRead
                                        ? 'bg-success'
                                        : 'bg-light'
                                    ?>
                                    d-flex align-items-center justify-content-center"
                                    style="width:45px;height:45px;"
                                >

                                    <i
                                        class="bi
                                        <?= !$isRead
                                            ? 'bi-bell text-white'
                                            : 'bi-bell text-muted'
                                        ?>"
                                    ></i>

                                </div>

                            </div>


                            <!-- Content -->

                            <div class="flex-grow-1">

                                <div
                                    class="d-flex flex-wrap justify-content-between gap-2"
                                >

                                    <h6 class="fw-bold mb-1">

                                        <?= sanitize(
                                            $notification['title']
                                            ?? 'Notification'
                                        ) ?>

                                        <?php if (!$isRead): ?>

                                            <span
                                                class="badge bg-success ms-1"
                                            >
                                                New
                                            </span>

                                        <?php endif; ?>

                                    </h6>


                                    <small class="text-muted">

                                        <?= !empty(
                                            $notification['created_at']
                                        )
                                            ? date(
                                                'M d, Y H:i',
                                                strtotime(
                                                    $notification['created_at']
                                                )
                                            )
                                            : ''
                                        ?>

                                    </small>

                                </div>


                                <p class="text-muted mb-2">

                                    <?= nl2br(
                                        sanitize(
                                            $notification['message']
                                            ?? ''
                                        )
                                    ) ?>

                                </p>


                                <div class="d-flex flex-wrap gap-2">

                                    <?php if ($complaintId > 0): ?>

                                        <a
                                            href="<?= APP_URL ?>/pages/complaint.php?id=<?= $complaintId ?>&notification_id=<?= $notificationId ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >

                                            <i class="bi bi-eye me-1"></i>

                                            View Complaint

                                        </a>

                                    <?php endif; ?>


                                    <?php if (!$isRead): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= sanitize($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="mark_read"
                                            >

                                            <input
                                                type="hidden"
                                                name="notification_id"
                                                value="<?= $notificationId ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-success"
                                            >

                                                <i class="bi bi-check2 me-1"></i>

                                                Mark as Read

                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>