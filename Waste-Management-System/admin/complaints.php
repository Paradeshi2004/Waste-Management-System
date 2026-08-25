<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/complaints.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notifications.php';

requireAdmin();

$db = getDB();

$csrfToken = getCsrfToken();

// Quick status / priority update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';
    $complaintId = (int)($_POST['complaint_id'] ?? 0);

    if ($complaintId > 0) {

        /*
         * Get current complaint values first.
         * We need both status and priority because
         * updateComplaintStatus() updates both.
         */
        $complaint = getComplaintById($complaintId);

        if ($complaint) {

            // -----------------------------------------
            // QUICK STATUS UPDATE
            // -----------------------------------------

            if ($action === 'quick_status') {

                $newStatus = $_POST['status'] ?? '';

                $allowedStatuses = [
                    'pending',
                    'in_progress',
                    'resolved',
                    'rejected'
                ];

                if (in_array($newStatus, $allowedStatuses, true)) {

                    updateComplaintStatus(
                        $complaintId,
                        $newStatus,
                        $user['id'],
                        '',
                        $complaint['priority']
                    );
                }

            // -----------------------------------------
            // QUICK PRIORITY UPDATE
            // -----------------------------------------

            } elseif ($action === 'quick_priority') {

                $newPriority = $_POST['priority'] ?? '';

                $allowedPriorities = [
                    'low',
                    'medium',
                    'high',
                    'urgent'
                ];

                if (in_array($newPriority, $allowedPriorities, true)) {

                    updateComplaintStatus(
                        $complaintId,
                        $complaint['status'],
                        $user['id'],
                        '',
                        $newPriority
                    );
                }
            }
        }
    }

    // Preserve current filters after update
    $query = $_GET;

    $redirectUrl = APP_URL . '/admin/complaints.php';

    if (!empty($query)) {
        $redirectUrl .= '?' . http_build_query($query);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$category = trim($_GET['category'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {

    $where[] = "(
        c.title LIKE ?
        OR c.description LIKE ?
        OR c.location LIKE ?
        OR u.name LIKE ?
        OR u.email LIKE ?
    )";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

if ($status !== '') {
    $where[] = "c.status = ?";
    $params[] = $status;
}

if ($priority !== '') {
    $where[] = "c.priority = ?";
    $params[] = $priority;
}

if ($category !== '') {
    $where[] = "c.category = ?";
    $params[] = $category;
}

if ($dateFrom !== '') {
    $where[] = "DATE(c.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = "DATE(c.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$countSql = "
    SELECT COUNT(*)
    FROM complaints c
    LEFT JOIN users u ON u.id = c.user_id
    $whereSql
";

$stmt = $db->prepare($countSql);
$stmt->execute($params);

$totalComplaints = (int)$stmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalComplaints / $perPage)
);

$sql = "
    SELECT
        c.*,
        u.name AS user_name,
        u.email AS user_email
    FROM complaints c
    LEFT JOIN users u ON u.id = c.user_id
    $whereSql
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryStmt = $db->query("
    SELECT DISTINCT category
    FROM complaints
    WHERE category IS NOT NULL
      AND category != ''
    ORDER BY category
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <div>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-clipboard-data me-2"></i>
            Manage Complaints
        </h2>

        <p class="text-muted mb-0">
            Search, filter and manage resident complaints.
        </p>
    </div>

</div>


<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white">

        <h5 class="fw-bold mb-0">
            <i class="bi bi-funnel me-2"></i>
            Filter Complaints
        </h5>

    </div>


    <div class="card-body">

        <form method="GET">

            <div class="row g-3">

                <!-- Search -->

                <div class="col-lg-4">

                    <label class="form-label fw-semibold">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="<?= sanitize($search) ?>"
                        placeholder="Title, resident, email, location..."
                    >

                </div>


                <!-- Status -->

                <div class="col-md-6 col-lg-2">

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

                        <?php foreach ([
                            'pending' => 'Pending',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved',
                            'rejected' => 'Rejected'
                        ] as $value => $label): ?>

                            <option
                                value="<?= $value ?>"
                                <?= $status === $value ? 'selected' : '' ?>
                            >
                                <?= $label ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Priority -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        Priority
                    </label>

                    <select
                        name="priority"
                        class="form-select"
                    >

                        <option value="">
                            All Priorities
                        </option>

                        <?php foreach ([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'urgent' => 'Urgent'
                        ] as $value => $label): ?>

                            <option
                                value="<?= $value ?>"
                                <?= $priority === $value ? 'selected' : '' ?>
                            >
                                <?= $label ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Category -->

                <div class="col-md-6 col-lg-2">

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

                        <?php foreach ($categories as $cat): ?>

                            <option
                                value="<?= sanitize($cat) ?>"
                                <?= $category === $cat ? 'selected' : '' ?>
                            >
                                <?= sanitize(categoryLabel($cat)) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Date From -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        From
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="<?= sanitize($dateFrom) ?>"
                    >

                </div>


                <!-- Date To -->

                <div class="col-md-6 col-lg-2">

                    <label class="form-label fw-semibold">
                        To
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="<?= sanitize($dateTo) ?>"
                    >

                </div>


                <!-- Buttons -->

                <div class="col-12">

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        <i class="bi bi-search me-1"></i>
                        Apply Filters
                    </button>


                    <a
                        href="<?= APP_URL ?>/admin/complaints.php"
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

<!-- Leaflet CSS -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="fw-bold">
        <i class="bi bi-list-check text-success me-2"></i>
        All Complaints
    </h2>

    <a
        href="index.php"
        class="btn btn-outline-secondary btn-sm"
    >
        <i class="bi bi-arrow-left me-1"></i>
        Dashboard
    </a>

</div>

<!-- ========================================================= -->
<!-- COMPLAINT MAP -->
<!-- ========================================================= -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white">

        <div class="d-flex justify-content-between align-items-center">

            <h5 class="fw-bold mb-0">

                <i class="bi bi-geo-alt-fill text-danger me-2"></i>

                Complaint Locations

            </h5>

            <span class="text-muted small">

                Click a marker to view complaint details

            </span>

        </div>

    </div>


    <div class="card-body p-0">

        <div
            id="complaintMap"
            style="
                height: 450px;
                width: 100%;
                border-radius: 0 0 8px 8px;
            "
        ></div>

    </div>

</div>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="text-muted">

        Showing

        <strong>
            <?= $totalComplaints > 0 ? $offset + 1 : 0 ?>
        </strong>

        -

        <strong>
            <?= min(
                $offset + $perPage,
                $totalComplaints
            ) ?>
        </strong>

        of

        <strong>
            <?= $totalComplaints ?>
        </strong>

        complaints

    </div>

</div>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="text-muted small">

        <i class="bi bi-list-check me-1"></i>

        Showing
        <strong>
            <?= count($complaints) ?>
        </strong>
        complaints

    </div>

</div>

<!-- ========================================================= -->
<!-- COMPLAINT TABLE -->
<!-- ========================================================= -->

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <h5 class="fw-bold mb-0">
            <i class="bi bi-list-ul me-2"></i>
            Complaints
        </h5>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

        <thead class="table-light">

            <tr>

                <th style="width: 70px;">
                    #
                </th>

                <th>
                    Complaint
                </th>

                <th>
                    Resident
                </th>

                <th>
                    Category
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

                <th class="text-end">
                    Action
                </th>

            </tr>

        </thead>


        <tbody>

        <?php if (empty($complaints)): ?>

            <tr>

                <td
                    colspan="8"
                    class="text-center py-5"
                >

                    <div class="py-3">

                        <i
                            class="bi bi-inbox text-muted"
                            style="font-size: 3rem;"
                        ></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            No complaints found
                        </h5>

                        <p class="text-muted mb-3">
                            Try changing your search or filter criteria.
                        </p>

                        <a
                            href="<?= APP_URL ?>/admin/complaints.php"
                            class="btn btn-outline-success"
                        >
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Clear Filters
                        </a>

                    </div>

                </td>

            </tr>

        <?php else: ?>


            <?php foreach ($complaints as $complaint): ?>

                <tr>

                    <!-- ID -->

                    <td>

                        <span class="fw-semibold">
                            #<?= (int)$complaint['id'] ?>
                        </span>

                    </td>


                    <!-- Complaint -->

                    <td>

                        <div class="fw-semibold">

                            <?= sanitize(
                                $complaint['title']
                            ) ?>

                        </div>


                        <div class="small text-muted mt-1">

                            <?php if (!empty($complaint['image_path'])): ?>

                                <i
                                    class="bi bi-image me-1"
                                    title="Complaint has an image"
                                ></i>

                            <?php endif; ?>


                            <?php if (
                                !empty($complaint['location'])
                            ): ?>

                                <i
                                    class="bi bi-geo-alt me-1"
                                    title="Location available"
                                ></i>

                                <?= sanitize(
                                    mb_strimwidth(
                                        $complaint['location'],
                                        0,
                                        45,
                                        '...'
                                    )
                                ) ?>

                            <?php endif; ?>

                        </div>

                    </td>


                    <!-- Resident -->

                    <td>

                        <div class="fw-semibold">

                            <?= sanitize(
                                $complaint['user_name']
                                ?? 'Unknown'
                            ) ?>

                        </div>


                        <?php if (
                            !empty($complaint['user_email'])
                        ): ?>

                            <div class="small text-muted">

                                <?= sanitize(
                                    $complaint['user_email']
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </td>


                    <!-- Category -->

                    <td>

                        <span class="badge bg-secondary">

                            <?= sanitize(
                                categoryLabel(
                                    $complaint['category']
                                )
                            ) ?>

                        </span>

                    </td>


                    <!-- Priority -->

                    <td>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="quick_priority"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= sanitize($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="complaint_id"
                                value="<?= (int)$complaint['id'] ?>"
                            >

                            <select
                                name="priority"
                                class="form-select form-select-sm"
                                onchange="this.form.submit()"
                                title="Change complaint priority"
                            >

                                <option
                                    value="low"
                                    <?= $complaint['priority'] === 'low' ? 'selected' : '' ?>
                                >
                                    Low
                                </option>

                                <option
                                    value="medium"
                                    <?= $complaint['priority'] === 'medium' ? 'selected' : '' ?>
                                >
                                    Medium
                                </option>

                                <option
                                    value="high"
                                    <?= $complaint['priority'] === 'high' ? 'selected' : '' ?>
                                >
                                    High
                                </option>

                                <option
                                    value="urgent"
                                    <?= $complaint['priority'] === 'urgent' ? 'selected' : '' ?>
                                >
                                    Urgent
                                </option>

                            </select>

                        </form>

                    </td>


                    <!-- Status -->

                    <td>

                        <form method="POST" class="mb-2">

                            <input
                                type="hidden"
                                name="action"
                                value="quick_status"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= sanitize($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="complaint_id"
                                value="<?= (int)$complaint['id'] ?>"
                            >

                            <select
                                name="status"
                                class="form-select form-select-sm"
                                onchange="this.form.submit()"
                                title="Change complaint status"
                            >

                                <option
                                    value="pending"
                                    <?= $complaint['status'] === 'pending' ? 'selected' : '' ?>
                                >
                                    Pending
                                </option>

                                <option
                                    value="in_progress"
                                    <?= $complaint['status'] === 'in_progress' ? 'selected' : '' ?>
                                >
                                    In Progress
                                </option>

                                <option
                                    value="resolved"
                                    <?= $complaint['status'] === 'resolved' ? 'selected' : '' ?>
                                >
                                    Resolved
                                </option>

                                <option
                                    value="rejected"
                                    <?= $complaint['status'] === 'rejected' ? 'selected' : '' ?>
                                >
                                    Rejected
                                </option>

                            </select>

                        </form>

                    </td>

                    <!-- Date -->

                    <td>

                        <div class="fw-semibold">

                            <?= date(
                                'd M Y',
                                strtotime(
                                    $complaint['created_at']
                                )
                            ) ?>

                        </div>

                        <div class="small text-muted">

                            <?= date(
                                'H:i',
                                strtotime(
                                    $complaint['created_at']
                                )
                            ) ?>

                        </div>

                    </td>


                    <!-- Actions -->

                    <td class="text-end">

                        <div
                            class="btn-group"
                            role="group"
                        >

                            <!-- View -->

                            <a
                                href="<?= APP_URL ?>/pages/complaint.php?id=<?= (int)$complaint['id'] ?>"
                                class="btn btn-sm btn-outline-primary"
                                title="View complaint"
                            >

                                <i class="bi bi-eye"></i>

                            </a>


                            <!-- Edit -->

                            <a
                                href="<?= APP_URL ?>/admin/edit-complaint.php?id=<?= (int)$complaint['id'] ?>"
                                class="btn btn-sm btn-outline-success"
                                title="Edit complaint"
                            >

                                <i class="bi bi-pencil"></i>

                            </a>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>


        <?php endif; ?>

        </tbody>

        </table>
    </div>

</div>

<?php if ($totalPages > 1): ?>

    <nav class="mt-4" aria-label="Complaint pagination">

        <ul class="pagination justify-content-center">

            <?php
            $queryParams = $_GET;
            ?>

            <!-- Previous -->

            <?php
            $queryParams['page'] = $page - 1;
            ?>

            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">

                <a
                    class="page-link"
                    href="?<?= http_build_query($queryParams) ?>"
                    aria-label="Previous"
                >
                    <i class="bi bi-chevron-left"></i>
                    Previous
                </a>

            </li>


            <!-- Page Numbers -->

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>

                <?php
                $queryParams['page'] = $p;
                ?>

                <li class="page-item <?= $page === $p ? 'active' : '' ?>">

                    <a
                        class="page-link"
                        href="?<?= http_build_query($queryParams) ?>"
                    >
                        <?= $p ?>
                    </a>

                </li>

            <?php endfor; ?>


            <!-- Next -->

            <?php
            $queryParams['page'] = $page + 1;
            ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">

                <a
                    class="page-link"
                    href="?<?= http_build_query($queryParams) ?>"
                    aria-label="Next"
                >
                    Next
                    <i class="bi bi-chevron-right"></i>
                </a>

            </li>

        </ul>

    </nav>

<?php endif; ?>


<!-- Leaflet JS -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Complaint data from PHP
    |--------------------------------------------------------------------------
    */

    const complaints = <?= json_encode(
        array_values(
            array_filter(
                $complaints,
                function ($c) {
                    return
                        $c['latitude'] !== null &&
                        $c['longitude'] !== null &&
                        $c['latitude'] !== '' &&
                        $c['longitude'] !== '';
                }
            )
        ),
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;


    /*
    |--------------------------------------------------------------------------
    | Create Map
    |--------------------------------------------------------------------------
    */

    const map = L.map('complaintMap');


    /*
    |--------------------------------------------------------------------------
    | OpenStreetMap Tiles
    |--------------------------------------------------------------------------
    */

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    /*
    |--------------------------------------------------------------------------
    | If there are no coordinates
    |--------------------------------------------------------------------------
    */

    if (complaints.length === 0) {

        map.setView(
            [20.5937, 78.9629],
            5
        );

        L.marker(
            [20.5937, 78.9629]
        )
        .addTo(map)
        .bindPopup(
            '<strong>No complaint coordinates available.</strong><br>' +
            'Report an issue using "Use My Location" to add a map marker.'
        )
        .openPopup();

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Add Complaint Markers
    |--------------------------------------------------------------------------
    */

    const markers = [];


    complaints.forEach(function (complaint) {

        const lat = parseFloat(
            complaint.latitude
        );

        const lng = parseFloat(
            complaint.longitude
        );


        if (
            Number.isNaN(lat) ||
            Number.isNaN(lng)
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        const status =
            complaint.status
                .replace('_', ' ')
                .replace(/\b\w/g, function (letter) {
                    return letter.toUpperCase();
                });


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        const category =
            complaint.category
                .replace('_', ' ')
                .replace(/\b\w/g, function (letter) {
                    return letter.toUpperCase();
                });


        /*
        |--------------------------------------------------------------------------
        | Popup
        |--------------------------------------------------------------------------
        */

        const popup = `

            <div style="min-width:240px">

                <h6 class="fw-bold mb-2">
                    ${escapeHtml(complaint.title)}
                </h6>

                <div class="mb-1">

                    <strong>Resident:</strong>
                    ${escapeHtml(complaint.user_name)}

                </div>

                <div class="mb-1">

                    <strong>Category:</strong>
                    ${escapeHtml(category)}

                </div>

                <div class="mb-1">

                    <strong>Status:</strong>
                    ${escapeHtml(status)}

                </div>

                <div class="mb-1">

                    <strong>Priority:</strong>
                    ${escapeHtml(complaint.priority)}

                </div>

                <div class="mb-3">

                    <strong>Location:</strong>
                    ${escapeHtml(complaint.location)}

                </div>

                <a
                    href="edit-complaint.php?id=${Number(complaint.id)}"
                    class="btn btn-success btn-sm"
                >
                    View / Manage
                </a>

            </div>

        `;


        /*
        |--------------------------------------------------------------------------
        | Marker
        |--------------------------------------------------------------------------
        */

        const marker = L.marker(
            [lat, lng]
        )
        .addTo(map)
        .bindPopup(popup);


        markers.push(marker);

    });


    /*
    |--------------------------------------------------------------------------
    | Automatically Fit Map to Complaints
    |--------------------------------------------------------------------------
    */

    if (markers.length > 0) {

        const group = L.featureGroup(
            markers
        );

        map.fitBounds(
            group.getBounds().pad(0.15)
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Simple HTML Escape
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }

});

</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>