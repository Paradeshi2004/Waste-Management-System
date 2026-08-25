<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/complaints.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();

$db = getDB();

/*
|--------------------------------------------------------------------------
| Basic Statistics
|--------------------------------------------------------------------------
*/

$totalComplaints = (int)$db
    ->query("SELECT COUNT(*) FROM complaints")
    ->fetchColumn();

$pendingComplaints = (int)$db
    ->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'")
    ->fetchColumn();

$inProgressComplaints = (int)$db
    ->query("SELECT COUNT(*) FROM complaints WHERE status = 'in_progress'")
    ->fetchColumn();

$resolvedComplaints = (int)$db
    ->query("SELECT COUNT(*) FROM complaints WHERE status = 'resolved'")
    ->fetchColumn();

$rejectedComplaints = (int)$db
    ->query("SELECT COUNT(*) FROM complaints WHERE status = 'rejected'")
    ->fetchColumn();

$totalResidents = (int)$db
    ->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")
    ->fetchColumn();

$highPriorityComplaints = (int)$db
    ->query("SELECT COUNT(*) FROM complaints WHERE priority = 'high'")
    ->fetchColumn();

/* 
|--------------------------------------------------------------------------
| Update #13 — Additional Dashboard Statistics
|--------------------------------------------------------------------------
*/

/* Urgent complaints */

$urgentPriorityComplaints = (int)$db
    ->query(
        "SELECT COUNT(*)
         FROM complaints
         WHERE priority = 'urgent'"
    )
    ->fetchColumn();


/* Today's complaints */

$todayComplaints = (int)$db
    ->query(
        "SELECT COUNT(*)
         FROM complaints
         WHERE DATE(created_at) = CURDATE()"
    )
    ->fetchColumn();


/* Resolution rate */

$resolutionRate = $totalComplaints > 0
    ? round(
        ($resolvedComplaints / $totalComplaints) * 100
    )
    : 0;

/*
|--------------------------------------------------------------------------
| Update #14 — AI / Waste Insights
|--------------------------------------------------------------------------
*/

/* Number of complaints classified by AI */

$aiClassifiedComplaints = (int)$db
    ->query(
        "SELECT COUNT(*)
         FROM complaints
         WHERE ai_category IS NOT NULL
         AND ai_category <> ''"
    )
    ->fetchColumn();


/* AI classification coverage */

$aiCoverageRate = $totalComplaints > 0
    ? round(
        ($aiClassifiedComplaints / $totalComplaints) * 100
    )
    : 0;


/* Average AI confidence */

$stmt = $db->query(
    "SELECT AVG(ai_confidence)
     FROM complaints
     WHERE ai_confidence IS NOT NULL"
);

$averageAiConfidence = $stmt->fetchColumn();

$averageAiConfidence = $averageAiConfidence !== null
    ? round((float)$averageAiConfidence, 1)
    : 0;


/* Most frequently detected AI waste category */

$stmt = $db->query(
    "SELECT
        ai_category,
        COUNT(*) AS total
     FROM complaints
     WHERE ai_category IS NOT NULL
     AND ai_category <> ''
     GROUP BY ai_category
     ORDER BY total DESC
     LIMIT 1"
);

$topAiCategory = $stmt->fetch(PDO::FETCH_ASSOC);

$topAiCategoryName = $topAiCategory['ai_category'] ?? 'None';

$topAiCategoryCount = (int)(
    $topAiCategory['total'] ?? 0
);

/*
|--------------------------------------------------------------------------
| Category Statistics
|--------------------------------------------------------------------------
*/

$categoryStats = [];

$stmt = $db->query(
    "SELECT category, COUNT(*) AS total
     FROM complaints
     GROUP BY category
     ORDER BY total DESC"
);

foreach ($stmt->fetchAll() as $row) {
    $categoryStats[$row['category']] = (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| Status Statistics
|--------------------------------------------------------------------------
*/

$statusStats = [];

$stmt = $db->query(
    "SELECT status, COUNT(*) AS total
     FROM complaints
     GROUP BY status"
);

foreach ($stmt->fetchAll() as $row) {
    $statusStats[$row['status']] = (int)$row['total'];
}


/*
|--------------------------------------------------------------------------
| Recent Complaints
|--------------------------------------------------------------------------
*/

$stmt = $db->query(
    "SELECT
        c.id,
        c.title,
        c.category,
        c.status,
        c.priority,
        c.created_at,
        u.name AS resident_name
     FROM complaints c
     INNER JOIN users u ON u.id = c.user_id
     ORDER BY c.created_at DESC
     LIMIT 8"
);

$recentComplaints = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container-fluid py-4">

    <!-- Header -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                <i class="bi bi-speedometer2 me-2"></i>

                Admin Dashboard

            </h2>

            <p class="text-muted mb-0">

                Monitor and manage waste complaints.

            </p>

        </div>


        <div class="d-flex gap-2 mt-2 mt-md-0">

            <a
                href="<?= APP_URL ?>/admin/complaints.php"
                class="btn btn-success"
            >

                <i class="bi bi-list-check me-1"></i>

                All Complaints

            </a>


            <a
                href="<?= APP_URL ?>/admin/tips.php"
                class="btn btn-outline-warning"
            >

                <i class="bi bi-lightbulb me-1"></i>

                Manage Tips

            </a>

        </div>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-4">


        <!-- Total -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Total Complaints
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $totalComplaints ?>
                            </h2>

                        </div>

                        <div class="fs-1 text-success">
                            <i class="bi bi-clipboard-data"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Pending -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Pending
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $pendingComplaints ?>
                            </h2>

                        </div>

                        <div class="fs-1 text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- In Progress -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                In Progress
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $inProgressComplaints ?>
                            </h2>

                        </div>

                        <div class="fs-1 text-primary">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Resolved -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Resolved
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $resolvedComplaints ?>
                            </h2>

                        </div>

                        <div class="fs-1 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Update #13 — Additional KPI Cards -->

    <div class="row g-4 mb-4">

        <!-- Resolution Rate -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                Resolution Rate
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $resolutionRate ?>%
                            </h2>

                        </div>

                        <div class="fs-1 text-success">

                            <i class="bi bi-check2-circle"></i>

                        </div>

                    </div>

                    <div
                        class="progress mt-3"
                        style="height: 7px;"
                    >

                        <div
                            class="progress-bar bg-success"
                            role="progressbar"
                            style="width: <?= $resolutionRate ?>%;"
                            aria-valuenow="<?= $resolutionRate ?>"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Update #13.5 — Quick Actions -->

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-lightning-charge text-warning me-2"></i>

                    Quick Actions

                </h5>

            </div>

            <div class="card-body">

                <div class="row g-2">

                    <!-- All Complaints -->

                    <div class="col-6 col-md-3">

                        <a
                            href="<?= APP_URL ?>/admin/complaints.php"
                            class="btn btn-outline-success w-100"
                        >

                            <i class="bi bi-list-check me-1"></i>

                            All Complaints

                        </a>

                    </div>


                    <!-- Pending -->

                    <div class="col-6 col-md-3">

                        <a
                            href="<?= APP_URL ?>/admin/complaints.php?status=pending"
                            class="btn btn-outline-warning w-100"
                        >

                            <i class="bi bi-hourglass-split me-1"></i>

                            Pending

                        </a>

                    </div>


                    <!-- Urgent -->

                    <div class="col-6 col-md-3">

                        <a
                            href="<?= APP_URL ?>/admin/complaints.php?priority=urgent"
                            class="btn btn-outline-danger w-100"
                        >

                            <i class="bi bi-exclamation-triangle me-1"></i>

                            Urgent

                        </a>

                    </div>


                    <!-- Resolved -->

                    <div class="col-6 col-md-3">

                        <a
                            href="<?= APP_URL ?>/admin/complaints.php?status=resolved"
                            class="btn btn-outline-primary w-100"
                        >

                            <i class="bi bi-check-circle me-1"></i>

                            Resolved

                        </a>

                    </div>

                </div>

            </div>

        </div>

        <!-- Today -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">
 
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                Today
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $todayComplaints ?>
                            </h2>

                            <small class="text-muted">
                                New complaints
                            </small>

                        </div>

                        <div class="fs-1 text-primary">

                            <i class="bi bi-calendar-day"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- High Priority -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                High Priority
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $highPriorityComplaints ?>
                            </h2>

                            <small class="text-muted">
                                Requires attention
                            </small>

                        </div>

                        <div class="fs-1 text-warning">

                            <i class="bi bi-exclamation-triangle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Urgent -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                Urgent
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $urgentPriorityComplaints ?>
                            </h2>

                            <small class="text-muted">
                                Immediate attention
                            </small>

                        </div>

                        <div class="fs-1 text-danger">

                            <i class="bi bi-fire"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Update #13.6 — Needs Attention -->

    <?php
    $needsAttention =
        $pendingComplaints
        + $inProgressComplaints
        + $urgentPriorityComplaints;
    ?>

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

                <div>

                    <div class="d-flex align-items-center mb-1">

                        <i class="bi bi-exclamation-circle-fill text-warning fs-4 me-2"></i>

                        <h5 class="fw-bold mb-0">
                            Needs Attention
                        </h5>

                    </div>

                    <p class="text-muted mb-0">
                        <?= $needsAttention ?> complaint(s) need attention.
                    </p>

                </div>

                <a
                    href="<?= APP_URL ?>/admin/complaints.php"
                    class="btn btn-warning"
                >

                    <i class="bi bi-eye me-1"></i>

                    Review Complaints

                </a>

            </div>

        </div>

    </div>

    <!-- Secondary Statistics -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Rejected Complaints
                    </p>

                    <h3 class="fw-bold">
                        <?= $rejectedComplaints ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        High Priority
                    </p>

                    <h3 class="fw-bold text-danger">
                        <?= $highPriorityComplaints ?>
                    </h3>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Registered Residents
                    </p>

                    <h3 class="fw-bold">
                        <?= $totalResidents ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <!-- Update #14.5 — AI Classification Coverage -->

    <div class="row g-4 mb-4">

        <!-- AI Coverage -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                AI Coverage
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $aiCoverageRate ?>%
                            </h2>

                            <small class="text-muted">

                                <?= $aiClassifiedComplaints ?>

                                of

                                <?= $totalComplaints ?>

                                classified

                            </small>

                        </div>

                        <div class="fs-1 text-success">

                            <i class="bi bi-robot"></i>

                        </div>

                    </div>

                    <div
                        class="progress mt-3"
                        style="height: 7px;"
                    >

                        <div
                            class="progress-bar bg-success"
                            role="progressbar"
                            style="width: <?= $aiCoverageRate ?>%;"
                            aria-valuenow="<?= $aiCoverageRate ?>"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Update #14.6 — Average AI Confidence -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                AI Confidence
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $averageAiConfidence ?>%
                            </h2>

                            <small class="text-muted">
                                Average classification confidence
                            </small>

                        </div>

                        <div class="fs-1 text-primary">

                            <i class="bi bi-graph-up-arrow"></i>

                        </div>

                    </div>

                </div>

            </div>
    
        </div>

        <!-- Top AI Category -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                Top Waste Type
                            </p>

                            <h4 class="fw-bold mb-1">

                                <?= sanitize($topAiCategoryName) ?>

                            </h4>

                            <small class="text-muted">

                                <?= $topAiCategoryCount ?>
                                complaint(s)

                            </small>

                        </div>

                        <div class="fs-1 text-warning">

                            <i class="bi bi-recycle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Update #14.8 — AI Classified Count -->

        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <p class="text-muted mb-1">
                                AI Classified
                            </p>

                            <h2 class="fw-bold mb-0">
                                <?= $aiClassifiedComplaints ?>
                            </h2>

                            <small class="text-muted">
                                Complaints analyzed by AI
                            </small>

                        </div>

                        <div class="fs-1 text-info">

                            <i class="bi bi-cpu"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Update #14 — AI Waste Insights -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="bi bi-robot text-success me-2"></i>

                        AI Waste Insights

                    </h5>

                    <small class="text-muted">

                        Waste categories detected from complaint images.

                    </small>

                </div>

            </div>

        </div>

        <div class="card-body">

            <?php

            $stmt = $db->query(
                "SELECT
                    ai_category,
                    COUNT(*) AS total,
                    AVG(ai_confidence) AS avg_confidence
                FROM complaints
                WHERE ai_category IS NOT NULL
                AND ai_category <> ''
                GROUP BY ai_category
                ORDER BY total DESC"
            );

            $aiCategoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ?>

            <?php if (empty($aiCategoryStats)): ?>

                <div class="text-center text-muted py-4">

                    <i class="bi bi-robot fs-1 d-block mb-2"></i>

                    No AI classifications available yet.

                </div>

            <?php else: ?>

                <div class="row g-3">

                    <?php foreach ($aiCategoryStats as $aiStat): ?>

                        <?php

                        $categoryTotal = (int)$aiStat['total'];

                        $categoryPercentage = $aiClassifiedComplaints > 0
                            ? round(
                                ($categoryTotal / $aiClassifiedComplaints) * 100
                            )
                            : 0;

                        $confidence = round(
                            (float)$aiStat['avg_confidence'],
                            1
                        );

                        ?>

                        <div class="col-md-6 col-xl-4">

                            <div class="border rounded p-3 h-100">

                                <div class="d-flex justify-content-between align-items-center mb-2">

                                    <strong>
                                        <?= sanitize($aiStat['ai_category']) ?>
                                    </strong>

                                    <span class="badge bg-success">

                                        <?= $categoryTotal ?>

                                    </span>

                                </div>

                                <div
                                    class="progress mb-2"
                                    style="height: 6px;"
                                >

                                    <div
                                        class="progress-bar bg-success"
                                        style="width: <?= $categoryPercentage ?>%;"
                                    ></div>

                                </div>

                                <div class="d-flex justify-content-between small text-muted">

                                    <span>
                                        <?= $categoryPercentage ?>%
                                        of AI classifications
                                    </span>

                                    <span>
                                        <?= $confidence ?>% confidence
                                    </span>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <?php

    if ($averageAiConfidence >= 90) {

        $aiQualityLabel = 'Excellent';
        $aiQualityClass = 'success';

    } elseif ($averageAiConfidence >= 75) {

        $aiQualityLabel = 'Good';
        $aiQualityClass = 'primary';

    } elseif ($averageAiConfidence >= 60) {

        $aiQualityLabel = 'Moderate';
        $aiQualityClass = 'warning';

    } else {

        $aiQualityLabel = 'Needs Review';
        $aiQualityClass = 'danger';

    }

    ?>

    <div class="alert alert-<?= $aiQualityClass ?> border-0 shadow-sm mb-4">

        <div class="d-flex align-items-center gap-3">

            <i class="bi bi-shield-check fs-3"></i>

            <div>

                <strong>
                    AI Classification Quality:
                    <?= $aiQualityLabel ?>
                </strong>

                <div class="small">
                    Average confidence:
                    <?= $averageAiConfidence ?>%
                </div>

            </div>

        </div>

    </div>


    <div class="row g-4 mb-4">


        <!-- Category Chart -->

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white">

                    <h5 class="fw-bold mb-0">

                        <i class="bi bi-bar-chart me-2"></i>

                        Complaints by Category

                    </h5>

                </div>


                <div class="card-body">

                    <?php if (!$categoryStats): ?>

                        <div class="text-center text-muted py-5">

                            No complaint data available.

                        </div>

                    <?php else: ?>

                        <?php foreach ($categoryStats as $category => $count): ?>

                            <?php
                            $categoryPercentage = $totalComplaints > 0
                                ? round(($count / $totalComplaints) * 100)
                                : 0;
                            ?>

                            <div class="mb-3">

                                <div class="d-flex justify-content-between align-items-center mb-2">

                                    <span>
                                        <?= sanitize(ucfirst(str_replace('_', ' ', $category))) ?>
                                    </span>

                                    <strong>
                                        <?= (int)$count ?> • <?= $categoryPercentage ?>%
                                    </strong>

                                </div>

                                <div class="progress" style="height: 8px;">

                                    <div
                                        class="progress-bar bg-success"
                                        role="progressbar"
                                        style="width: <?= $categoryPercentage ?>%;"
                                        aria-valuenow="<?= $categoryPercentage ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- Status Chart -->

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white">

                    <h5 class="fw-bold mb-0">

                        <i class="bi bi-pie-chart me-2"></i>

                        Complaints by Status

                    </h5>

                </div>


                <div class="card-body">

                    <div class="mb-4" >
                        <div class="status-chart-wrapper">
                            <canvas id="statusChart"></canvas>

                            <div class="chart-center-text">
                                <strong><?= (int)$totalComplaints ?></strong>
                                <span>Total</span>
                            </div>
                        </div>
                    </div>

                    <?php

                    $statusOrder = [
                        'pending',
                        'in_progress',
                        'resolved',
                        'rejected'
                    ];

                    ?>

                    <?php foreach ($statusOrder as $status): ?>

                        <?php

                        $total = $statusStats[$status] ?? 0;

                        if ($total <= 0) {
                            continue;
                        }

                        $percentage = $totalComplaints > 0
                            ? round(($total / $totalComplaints) * 100)
                            : 0;

                        ?>

                        <div class="mb-3">

                            <div class="d-flex justify-content-between mb-1">

                                <span>
                                    <?= sanitize(statusLabel($status)) ?>
                                </span>

                                <strong>
                                    <?= $total ?> + <?= $percentage ?>%
                                </strong>

                            </div>


                            <div class="progress" style="height: 8px;">

                                <div
                                    class="progress-bar bg-<?= statusBadge($status) ?>"
                                    role="progressbar"
                                    style="width: <?= $percentage ?>%;"
                                    aria-valuenow="<?= $percentage ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </div>


    <!-- Recent Complaints -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

                <h5 class="fw-bold mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Recent Complaints
                </h5>

                <a
                    href="<?= APP_URL ?>/admin/complaints.php"
                    class="btn btn-sm btn-outline-success"
                >
                    <i class="bi bi-list-ul me-1"></i>
                    View All
                </a>

            </div>

        </div>

        <div class="card-body">

            <?php if (!empty($recentComplaints)): ?>

                <!-- Filters -->

                <div class="row g-2 mb-3" id="recentComplaintFilters">

                    <!-- Search -->

                    <div class="col-lg-4">

                        <div class="input-group">

                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="text"
                                id="recentComplaintSearch"
                                class="form-control"
                                placeholder="Search complaints..."
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    <!-- Status -->

                    <div class="col-md-3 col-lg-2">

                        <select
                            id="recentStatusFilter"
                            class="form-select"
                        >

                            <option value="">All Statuses</option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="in_progress">
                                In Progress
                            </option>

                            <option value="resolved">
                                Resolved
                            </option>

                            <option value="rejected">
                                Rejected
                            </option>

                        </select>

                    </div>


                    <!-- Priority -->

                    <div class="col-md-3 col-lg-2">

                        <select
                            id="recentPriorityFilter"
                            class="form-select"
                        >

                            <option value="">All Priorities</option>

                            <option value="low">
                                Low
                            </option>

                            <option value="medium">
                                Medium
                            </option>

                            <option value="high">
                                High
                            </option>

                            <option value="urgent">
                                Urgent
                            </option>

                        </select>

                    </div>


                    <!-- Category -->

                    <div class="col-md-3 col-lg-2">

                        <select
                            id="recentCategoryFilter"
                            class="form-select"
                        >

                            <option value="">All Categories</option>

                            <?php

                            $recentCategories = [];

                            foreach ($recentComplaints as $recentComplaint) {

                                $recentCategory = $recentComplaint['category'];

                                if (
                                    !in_array(
                                        $recentCategory,
                                        $recentCategories,
                                        true
                                    )
                                ) {
                                    $recentCategories[] = $recentCategory;
                                }

                            }

                            sort($recentCategories);

                            ?>

                            <?php foreach ($recentCategories as $category): ?>

                                <option value="<?= sanitize($category) ?>">
                                    <?= sanitize(categoryLabel($category)) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Reset -->

                    <div class="col-md-3 col-lg-2">

                        <button
                            type="button"
                            id="resetRecentFilters"
                            class="btn btn-outline-secondary w-100"
                    >
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Reset
                        </button>

                    </div>

                </div>


                <!-- Result count -->

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <small class="text-muted">

                        Showing

                        <strong id="recentComplaintCount">
                            <?= count($recentComplaints) ?>
                        </strong>

                        recent complaint(s)

                    </small>

                </div>


                <!-- Table -->

                <div class="table-responsive">

                    <table
                        id="recentComplaintsTable"
                        class="table table-hover align-middle mb-0"
                    >

                        <thead>

                            <tr>

                                <th>Complaint</th>

                                <th>Resident</th>

                                <th>Category</th>

                                <th>Priority</th>

                                <th>Status</th>

                                <th>Date</th>

                                <th class="text-end">Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($recentComplaints as $complaint): ?>

                                <tr
                                    class="recent-complaint-row"
                                    data-title="<?= sanitize(strtolower($complaint['title'])) ?>"
                                    data-resident="<?= sanitize(strtolower($complaint['resident_name'])) ?>"
                                    data-category="<?= sanitize(strtolower($complaint['category'])) ?>"
                                    data-status="<?= sanitize(strtolower($complaint['status'])) ?>"
                                    data-priority="<?= sanitize(strtolower($complaint['priority'])) ?>"
                                >

                                    <td>

                                        <div class="fw-semibold">
                                            <?= sanitize($complaint['title']) ?>
                                        </div>

                                    </td>


                                    <td>
                                        <?= sanitize($complaint['resident_name']) ?>
                                    </td>


                                    <td>
                                        <?= sanitize(
                                            categoryLabel($complaint['category'])
                                        ) ?>
                                    </td>


                                    <td>

                                        <span class="badge bg-<?= priorityBadge($complaint['priority']) ?>">

                                            <?= sanitize(
                                                ucfirst($complaint['priority'])
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="badge bg-<?= statusBadge($complaint['status']) ?>">

                                            <?= sanitize(
                                                statusLabel($complaint['status'])
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <small class="text-muted">

                                            <?= date(
                                                'M d, Y H:i',
                                                strtotime($complaint['created_at'])
                                            ) ?>

                                        </small>

                                    </td>


                                    <td class="text-end">

                                        <a
                                            href="<?= APP_URL ?>/admin/edit-complaint.php?id=<?= (int)$complaint['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit Complaint"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <!-- No search results -->

                            <tr
                                id="noRecentComplaintResults"
                                class="d-none"
                            >

                                <td
                                    colspan="7"
                                    class="text-center text-muted py-4"
                                >

                                    <i class="bi bi-search fs-3 d-block mb-2"></i>

                                    No complaints match your filters.

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>

                <!-- No complaints -->

                <div class="text-center text-muted py-5">

                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>

                    <h6>No complaints yet.</h6>

                    <p class="mb-0">
                        Complaints submitted by residents will appear here.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const statusCanvas = document.getElementById('statusChart');

    if (!statusCanvas) {
        return;
    }

    const statusData = {
        'Pending': <?= (int)($statusStats['pending'] ?? 0) ?>,
        'In Progress': <?= (int)($statusStats['in_progress'] ?? 0) ?>,
        'Resolved': <?= (int)($statusStats['resolved'] ?? 0) ?>,
        'Rejected': <?= (int)($statusStats['rejected'] ?? 0) ?>
    };

    /*
     * Remove statuses having 0 complaints.
     */
    const filteredLabels = [];
    const filteredValues = [];

    Object.entries(statusData).forEach(([label, value]) => {

        if (value > 0) {
            filteredLabels.push(label);
            filteredValues.push(value);
        }

    });

    /*
     * If there are no complaints.
     */
    if (filteredLabels.length === 0) {

        filteredLabels.push('No Complaints');
        filteredValues.push(1);

    }

    new Chart(statusCanvas, {

        type: 'doughnut',

        data: {
            labels: filteredLabels,

            datasets: [{
                data: filteredValues,

                borderWidth: 2,

                borderColor: '#ffffff'
            }]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            cutout: '58%',

            plugins: {

                legend: {
                    position: 'top',

                    labels: {
                        usePointStyle: true,

                        padding: 18
                    }
                },

                tooltip: {

                    callbacks: {

                        label: function(context) {

                            const value = context.raw;

                            const total = filteredValues.reduce(
                                (sum, number) => sum + number,
                                0
                            );

                            const percentage =
                                ((value / total) * 100).toFixed(1);

                            return ` ${context.label}: ${value} (${percentage}%)`;
                        }

                    }

                }

            }
        }

    });

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('recentComplaintSearch');

    const statusFilter =
        document.getElementById('recentStatusFilter');

    const priorityFilter =
        document.getElementById('recentPriorityFilter');

    const categoryFilter =
        document.getElementById('recentCategoryFilter');

    const resetButton =
        document.getElementById('resetRecentFilters');

    const countElement =
        document.getElementById('recentComplaintCount');

    const noResultsRow =
        document.getElementById('noRecentComplaintResults');

    const rows =
        document.querySelectorAll('.recent-complaint-row');


    // Stop if the filter section does not exist
    if (
        !searchInput ||
        !statusFilter ||
        !priorityFilter ||
        !categoryFilter
    ) {
        return;
    }


    function filterComplaints() {

        const search =
            searchInput.value.trim().toLowerCase();

        const status =
            statusFilter.value.toLowerCase();

        const priority =
            priorityFilter.value.toLowerCase();

        const category =
            categoryFilter.value.toLowerCase();


        let visibleCount = 0;


        rows.forEach(function (row) {

            const title =
                row.dataset.title || '';

            const resident =
                row.dataset.resident || '';

            const rowCategory =
                row.dataset.category || '';

            const rowStatus =
                row.dataset.status || '';

            const rowPriority =
                row.dataset.priority || '';


            const matchesSearch =
                !search ||
                title.includes(search) ||
                resident.includes(search) ||
                rowCategory.includes(search);


            const matchesStatus =
                !status ||
                rowStatus === status;


            const matchesPriority =
                !priority ||
                rowPriority === priority;


            const matchesCategory =
                !category ||
                rowCategory === category;


            const visible =
                matchesSearch &&
                matchesStatus &&
                matchesPriority &&
                matchesCategory;


            row.style.display =
                visible ? '' : 'none';


            if (visible) {
                visibleCount++;
            }

        });


        // Update result count

        if (countElement) {
            countElement.textContent = visibleCount;
        }


        // Show/hide "no results"

        if (noResultsRow) {

            noResultsRow.classList.toggle(
                'd-none',
                visibleCount !== 0
            );

        }

    }


    // Search

    searchInput.addEventListener(
        'input',
        filterComplaints
    );


    // Status

    statusFilter.addEventListener(
        'change',
        filterComplaints
    );


    // Priority

    priorityFilter.addEventListener(
        'change',
        filterComplaints
    );


    // Category

    categoryFilter.addEventListener(
        'change',
        filterComplaints
    );


    // Reset

    if (resetButton) {

        resetButton.addEventListener(
            'click',
            function () {

                searchInput.value = '';

                statusFilter.value = '';

                priorityFilter.value = '';

                categoryFilter.value = '';

                filterComplaints();

            }
        );

    }

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>