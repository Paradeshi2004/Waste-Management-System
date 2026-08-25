<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/complaints.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$user = currentUser();

$unreadNotificationCount = 0;

$db = getDB();

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

$stmt->execute([$user['id']]);

$unreadNotificationCount = (int)$stmt->fetchColumn();

$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$filters = [
    'user_id' => $user['id']
];

if ($statusFilter) {
    $filters['status'] = $statusFilter;
}

if ($categoryFilter) {
    $filters['category'] = $categoryFilter;
}

$complaints = getComplaints($filters);

/*
 * Resident statistics
 */
$allComplaints = getComplaints([
    'user_id' => $user['id']
]);

$totalComplaints = count($allComplaints);

$statusCounts = [
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'rejected' => 0
];

foreach ($allComplaints as $complaint) {

    $status = $complaint['status'] ?? '';

    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

include __DIR__ . '/../includes/header.php';

?>

<!-- Page Header -->

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

    <div>

        <h2 class="fw-bold mb-1">

            <i class="bi bi-clipboard2-list text-success me-2"></i>

            My Complaints

        </h2>

        <p class="text-muted mb-0">

            Track and manage your reported waste issues.

        </p>

    </div>

    <div class="d-flex flex-wrap gap-2">

        <a
            href="<?= APP_URL ?>/pages/notifications.php"
            class="btn btn-outline-success position-relative"
        >
            <i class="bi bi-bell me-1"></i>
            Notifications

            <?php if ($unreadNotificationCount > 0): ?>

                <span class="badge bg-danger ms-1">
                    <?= $unreadNotificationCount > 9
                        ? '9+'
                        : $unreadNotificationCount ?>
                </span>

            <?php endif; ?>

        </a>

        <a
            href="submit.php"
            class="btn btn-success"
        >
            <i class="bi bi-plus-circle me-1"></i>
            Report New Issue
        </a>

    </div>

</div>


<!-- Statistics -->

<div class="row g-3 mb-4">

    <!-- Total -->

    <div class="col-6 col-lg">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Total
                        </small>

                        <h3 class="fw-bold mb-0">
                            <?= $totalComplaints ?>
                        </h3>

                    </div>

                    <div class="rounded-circle bg-success bg-opacity-10 p-3">

                        <i class="bi bi-clipboard-check text-success fs-4"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Pending -->

    <div class="col-6 col-lg">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Pending
                        </small>

                        <h3 class="fw-bold mb-0">
                            <?= $statusCounts['pending'] ?>
                        </h3>

                    </div>

                    <div class="rounded-circle bg-warning bg-opacity-10 p-3">

                        <i class="bi bi-hourglass-split text-warning fs-4"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- In Progress -->

    <div class="col-6 col-lg">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            In Progress
                        </small>

                        <h3 class="fw-bold mb-0">
                            <?= $statusCounts['in_progress'] ?>
                        </h3>

                    </div>

                    <div class="rounded-circle bg-primary bg-opacity-10 p-3">

                        <i class="bi bi-arrow-repeat text-primary fs-4"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Resolved -->

    <div class="col-6 col-lg">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Resolved
                        </small>

                        <h3 class="fw-bold mb-0">
                            <?= $statusCounts['resolved'] ?>
                        </h3>

                    </div>

                    <div class="rounded-circle bg-success bg-opacity-10 p-3">

                        <i class="bi bi-check-circle text-success fs-4"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Rejected -->

    <div class="col-6 col-lg">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-muted">
                            Rejected
                        </small>

                        <h3 class="fw-bold mb-0">
                            <?= $statusCounts['rejected'] ?>
                        </h3>

                    </div>

                    <div class="rounded-circle bg-danger bg-opacity-10 p-3">

                        <i class="bi bi-x-circle text-danger fs-4"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- Filters Card -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white">

        <div class="d-flex align-items-center gap-2">

            <i class="bi bi-funnel text-success"></i>

            <strong>
                Filter Complaints
            </strong>

        </div>

    </div>

    <div class="card-body">

        <form method="GET">

            <div class="row g-3">

                <!-- Status -->

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="pending"
                            <?= $statusFilter === 'pending' ? 'selected' : '' ?>
                        >
                            Pending
                        </option>

                        <option
                            value="in_progress"
                            <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>
                        >
                            In Progress
                        </option>

                        <option
                            value="resolved"
                            <?= $statusFilter === 'resolved' ? 'selected' : '' ?>
                        >
                            Resolved
                        </option>

                        <option
                            value="rejected"
                            <?= $statusFilter === 'rejected' ? 'selected' : '' ?>
                        >
                            Rejected
                        </option>

                    </select>

                </div>


                <!-- Category -->

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        Category
                    </label>

                    <select
                        name="category"
                        class="form-select"
                    >

                        <option value="">
                            All Categories
                        </option>

                        <option
                            value="garbage"
                            <?= $categoryFilter === 'garbage' ? 'selected' : '' ?>
                        >
                            Garbage
                        </option>

                        <option
                            value="illegal_dumping"
                            <?= $categoryFilter === 'illegal_dumping' ? 'selected' : '' ?>
                        >
                            Illegal Dumping
                        </option>

                        <option
                            value="recycling"
                            <?= $categoryFilter === 'recycling' ? 'selected' : '' ?>
                        >
                            Recycling
                        </option>

                        <option
                            value="hazardous"
                            <?= $categoryFilter === 'hazardous' ? 'selected' : '' ?>
                        >
                            Hazardous
                        </option>

                        <option
                            value="other"
                            <?= $categoryFilter === 'other' ? 'selected' : '' ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <!-- Buttons -->

                <div class="col-md-4 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-outline-success"
                    >

                        <i class="bi bi-funnel me-1"></i>

                        Apply Filters

                    </button>


                    <a
                        href="dashboard.php"
                        class="btn btn-outline-secondary"
                    >

                        <i class="bi bi-arrow-counterclockwise me-1"></i>

                        Clear Filters

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- Complaints -->

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

            <div>

                <h5 class="fw-bold mb-1">

                    <i class="bi bi-list-check text-success me-2"></i>

                    My Complaint History

                </h5>

                <small class="text-muted">

                    <?= count($complaints) ?> complaint(s) shown

                </small>

            </div>


            <a
                href="submit.php"
                class="btn btn-sm btn-success"
            >

                <i class="bi bi-plus-circle me-1"></i>

                New Complaint

            </a>

        </div>

    </div>


    <div class="card-body p-0">

        <?php if (empty($complaints)): ?>

            <div class="text-center py-5 px-3">

                <div
                    class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                    style="width:70px;height:70px;"
                >

                    <i class="bi bi-inbox text-muted fs-2"></i>

                </div>

                <h5 class="fw-bold">
                    No complaints found
                </h5>

                <p class="text-muted mb-3">

                    <?= ($statusFilter || $categoryFilter)
                        ? 'Try changing your filters.'
                        : 'You have not reported any complaints yet.'
                    ?>

                </p>

                <?php if ($statusFilter || $categoryFilter): ?>

                    <a
                        href="dashboard.php"
                        class="btn btn-outline-secondary me-2"
                    >

                        <i class="bi bi-arrow-counterclockwise me-1"></i>

                        Clear Filters

                    </a>

                <?php endif; ?>


                <a
                    href="submit.php"
                    class="btn btn-success"
                >

                    <i class="bi bi-plus-circle me-1"></i>

                    Report an Issue

                </a>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th class="ps-3">
                                #
                            </th>

                            <th>
                                Complaint
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                            <th class="text-end pe-3">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($complaints as $c): ?>

                            <tr>

                                <!-- ID -->

                                <td class="ps-3">

                                    <span class="text-muted fw-semibold">

                                        #<?= (int)$c['id'] ?>

                                    </span>

                                </td>


                                <!-- Complaint -->

                                <td>

                                    <div class="fw-semibold">

                                        <?= sanitize($c['title']) ?>

                                    </div>

                                    <?php if (!empty($c['description'])): ?>

                                        <small class="text-muted">

                                            <?= sanitize(
                                                mb_substr(
                                                    $c['description'],
                                                    0,
                                                    55
                                                )
                                            ) ?>

                                            <?= mb_strlen($c['description']) > 55 ? '...' : '' ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- Category -->

                                <td>

                                    <span class="badge bg-secondary">

                                        <?= sanitize(
                                            categoryLabel($c['category'])
                                        ) ?>

                                    </span>

                                </td>


                                <!-- Location -->

                                <td>

                                    <small>

                                        <i class="bi bi-geo-alt text-danger me-1"></i>

                                        <?= sanitize(
                                            mb_substr(
                                                $c['location'],
                                                0,
                                                35
                                            )
                                        ) ?>

                                        <?= mb_strlen($c['location']) > 35 ? '...' : '' ?>

                                    </small>

                                </td>


                                <!-- Priority -->

                                <td>

                                    <?php
                                    $priorityClass = priorityBadge(
                                        $c['priority'] ?? 'medium'
                                    );
                                    ?>

                                    <span class="badge bg-<?= sanitize($priorityClass) ?>">

                                        <?= sanitize(
                                            ucfirst(
                                                $c['priority'] ?? 'medium'
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?= statusBadge($c['status']) ?>

                                    <?php
                                    $statusProgress = match ($c['status']) {
                                        'pending' => 25,
                                        'in_progress' => 50,
                                        'resolved' => 100,
                                        'rejected' => 100,
                                        default => 0
                                    };
                                    ?>

                                    <div
                                        class="progress mt-2"
                                        style="height: 5px; min-width: 80px;"
                                        title="<?= $statusProgress ?>% complete"
                                    >

                                        <div
                                            class="progress-bar"
                                            role="progressbar"
                                            style="width: <?= $statusProgress ?>%;"
                                            aria-valuenow="<?= $statusProgress ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>

                                    </div>

                                    <small class="text-muted">
                                        <?= $statusProgress ?>% complete
                                    </small>

                                </td>


                                <!-- Date -->

                                <td>

                                    <small class="text-muted d-block">

                                        <?= date(
                                            'M d, Y',
                                            strtotime($c['created_at'])
                                        ) ?>

                                    </small>

                                    <?php if (!empty($c['updated_at'])): ?>

                                        <small class="text-muted">

                                            <i class="bi bi-clock-history me-1"></i>

                                            Updated
                                            <?= date(
                                                'M d, Y',
                                                strtotime($c['updated_at'])
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- Action -->

                                <td class="text-end pe-3">

                                    <a
                                        href="complaint.php?id=<?= (int)$c['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="View complaint"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        View

                                    </a>

                                    <a
                                        href="complaint.php?id=<?= (int)$c['id'] ?>#updates"
                                        class="btn btn-sm btn-outline-success ms-1"
                                        title="View complaint updates"
                                    >
                                        <i class="bi bi-clock-history"></i>
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>