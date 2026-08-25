<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/complaints.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();
$user = currentUser();

$csrfToken = getCsrfToken();

$id = (int) ($_GET['id'] ?? 0);
$complaint = getComplaintById($id);
if (!$complaint) {
    header('Location: ' . APP_URL . '/admin/complaints.php');
    exit;
}

$success = $error = '';

    // AI Priority Recommendation
    $aiPriority = 'medium';

    if (!empty($complaint['ai_category'])) {

        $aiWaste = strtolower($complaint['ai_category']);

        if (
            str_contains($aiWaste, 'battery') ||
            str_contains($aiWaste, 'chemical') ||
            str_contains($aiWaste, 'hazardous') ||
            str_contains($aiWaste, 'medical')
        ) {
            $aiPriority = 'urgent';

        } elseif (
            str_contains($aiWaste, 'garbage') ||
            str_contains($aiWaste, 'trash') ||
            str_contains($aiWaste, 'dumping')
        ) {
            $aiPriority = 'high';

        } elseif (
            str_contains($aiWaste, 'plastic') ||
            str_contains($aiWaste, 'paper') ||
            str_contains($aiWaste, 'cardboard') ||
            str_contains($aiWaste, 'glass') ||
            str_contains($aiWaste, 'metal')
        ) {
            $aiPriority = 'medium';

        } else {
            $aiPriority = 'low';
        }
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        $error = 'Invalid CSRF token.';
    } else {

        $status = $_POST['status'] ?? $complaint['status'];
    $priority = $_POST['priority'] ?? $complaint['priority'];
    $note = trim($_POST['note'] ?? '');

    $validStatuses = [
        'pending',
        'in_progress',
        'resolved',
        'rejected'
    ];

    $validPriorities = [
        'low',
        'medium',
        'high', 
        'urgent'
    ];

    if (!in_array($status, $validStatuses)) {

        $error = "Invalid status.";

    } elseif (!in_array($priority, $validPriorities)) {

        $error = "Invalid priority.";

    } else {

        updateComplaintStatus(
            $id,
            $status,
            $user['id'],
            $note,
            $priority
        );

        $success = "Complaint status and priority updated successfully.";

        $complaint = getComplaintById($id);
    }
    }
}

$updates = getComplaintUpdates($id);
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="complaints.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h2 class="fw-bold mb-0">Manage Complaint #<?= $id ?></h2>
    <?= statusBadge($complaint['status']) ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Complaint Details</div>
            <div class="card-body">
                <h5><?= sanitize($complaint['title']) ?></h5>
                <p><?= nl2br(sanitize($complaint['description'])) ?></p>
                <ul class="list-unstyled small text-muted">
                    <li><strong>Category:</strong> <?= ucfirst(str_replace('_', ' ', $complaint['category'])) ?></li>
                    <li><strong>Location:</strong> <?= sanitize($complaint['location']) ?></li>
                    <li><strong>Priority:</strong> <?= ucfirst($complaint['priority']) ?></li>
                    <li><strong>Submitted:</strong> <?= date('M d, Y H:i', strtotime($complaint['created_at'])) ?></li>
                    <li><strong>Reported by:</strong> <?= sanitize($complaint['user_name']) ?> (<?= sanitize($complaint['user_email']) ?>)</li>
                </ul>
                <?php if ($complaint['image_path']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Complaint Photo</h6>

                        <img
                            src="<?= UPLOAD_URL . sanitize($complaint['image_path']) ?>"
                            class="img-fluid rounded"
                            style="max-height:250px;"
                            alt="Complaint image"
                        >
                    </div>
                <?php endif; ?>

                    <?php if (!empty($complaint['ai_category'])): ?>

                        <div class="card border-0 shadow-sm mb-4">

                            <div class="card-header bg-white fw-bold">
                                <i class="bi bi-robot text-success me-2"></i>
                                AI Waste Classification
                            </div>

                            <div class="card-body">

                                <p class="text-muted mb-4">
                                    Automatically analyzed from the uploaded complaint image.
                                </p>

                                <div class="row g-3">

                                    <!-- Detected Waste -->
                                    <div class="col-md-6">
                                        <div class="p-3 rounded bg-light">
                                            <small class="text-muted d-block mb-1">
                                                Detected Waste
                                            </small>

                                            <h5 class="fw-bold mb-0">
                                                <?= sanitize($complaint['ai_category']) ?>
                                            </h5>
                                        </div>
                                    </div>

                                    <!-- Confidence -->
                                    <div class="col-md-6">
                                        <div class="p-3 rounded bg-light">
                                            <small class="text-muted d-block mb-1">
                                                AI Confidence
                                            </small>

                                            <h5 class="fw-bold text-success mb-0">
                                                <?= number_format((float)$complaint['ai_confidence'], 2) ?>%
                                            </h5>
                                        </div>
                                    </div>

                                </div>

                                <?php
                                $aiWaste = strtolower($complaint['ai_category']);

                                $suggestedCategory = 'other';

                                if (
                                    str_contains($aiWaste, 'plastic') ||
                                    str_contains($aiWaste, 'paper') ||
                                    str_contains($aiWaste, 'cardboard') ||
                                    str_contains($aiWaste, 'glass') ||
                                    str_contains($aiWaste, 'metal') ||
                                    str_contains($aiWaste, 'clothes') ||
                                    str_contains($aiWaste, 'shoes')
                                ) {
                                    $suggestedCategory = 'recycling';

                                } elseif (
                                    str_contains($aiWaste, 'battery') ||
                                    str_contains($aiWaste, 'hazardous') ||
                                    str_contains($aiWaste, 'chemical')
                                ) {
                                    $suggestedCategory = 'hazardous';

                                } elseif (
                                    str_contains($aiWaste, 'garbage') ||
                                    str_contains($aiWaste, 'trash')
                                ) {
                                    $suggestedCategory = 'garbage';
                                }

                                $categoryLabels = [
                                    'garbage' => 'Garbage / Trash',
                                    'illegal_dumping' => 'Illegal Dumping',
                                    'recycling' => 'Recycling',
                                    'hazardous' => 'Hazardous Waste',
                                    'other' => 'Other'
                                ];
                                ?>

                                <div class="mt-4">

                                    <small class="text-muted d-block mb-2">
                                        Suggested Waste Category
                                    </small>

                                    <span class="badge bg-success fs-6">
                                        <i class="bi bi-recycle me-1"></i>
                                        <?= $categoryLabels[$suggestedCategory] ?>
                                    </span>

                                </div>

                                <?php if ((float)$complaint['ai_confidence'] >= 90): ?>

                                    <div class="alert alert-success mt-4 mb-0">
                                        <i class="bi bi-check-circle me-2"></i>

                                        <strong>High-confidence AI detection.</strong>

                                        The system is highly confident that this image contains
                                        <strong><?= sanitize($complaint['ai_category']) ?></strong>.
                                    </div>

                                <?php elseif ((float)$complaint['ai_confidence'] >= 70): ?>

                                    <div class="alert alert-warning mt-4 mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>

                                        <strong>Moderate-confidence detection.</strong>

                                        Please review the uploaded image before making a final decision.
                                    </div>

                                <?php else: ?>

                                    <div class="alert alert-danger mt-4 mb-0">
                                        <i class="bi bi-question-circle me-2"></i>

                                        <strong>Low-confidence detection.</strong>

                                        Manual verification is recommended.
                                    </div>

                                <?php endif; ?>


                                <!-- AI PRIORITY RECOMMENDATION -->

                                <?php

                                $aiWasteType = strtolower($complaint['ai_category'] ?? '');

                                $aiRecommendedPriority = 'medium';
                                $aiPriorityReason = 'AI recommends normal review priority.';

                                if (
                                    str_contains($aiWasteType, 'battery') ||
                                    str_contains($aiWasteType, 'chemical') ||
                                    str_contains($aiWasteType, 'hazardous')
                                ) {
                                    $aiRecommendedPriority = 'high';
                                    $aiPriorityReason =
                                        'Potentially hazardous waste requires faster attention.';

                                } elseif (
                                    str_contains($aiWasteType, 'electronic') ||
                                    str_contains($aiWasteType, 'e-waste')
                                ) {
                                    $aiRecommendedPriority = 'high';
                                    $aiPriorityReason =
                                        'Electronic waste detected; faster handling is recommended.';

                                } elseif (
                                    str_contains($aiWasteType, 'plastic') ||
                                    str_contains($aiWasteType, 'paper') ||
                                    str_contains($aiWasteType, 'cardboard') ||
                                    str_contains($aiWasteType, 'glass') ||
                                    str_contains($aiWasteType, 'metal')
                                ) {
                                    $aiRecommendedPriority = 'medium';
                                    $aiPriorityReason =
                                        'Recyclable material detected; normal collection priority is recommended.';
                                }

                                $priorityClass = [
                                    'low' => 'secondary',
                                    'medium' => 'warning',
                                    'high' => 'danger'
                                ][$aiRecommendedPriority];

                                ?>

                                <div class="alert alert-light border mt-4 mb-0">

                                    <div class="d-flex align-items-start">

                                        <i class="bi bi-stars text-warning fs-4 me-3"></i>

                                        <div>

                                            <h6 class="fw-bold mb-2">
                                                AI Priority Recommendation
                                            </h6>

                                            <div class="mb-2">

                                                <span class="text-muted">
                                                    Recommended Priority:
                                                </span>

                                                <span class="badge bg-<?= $priorityClass ?> ms-1">
                                                    <?= ucfirst($aiRecommendedPriority) ?>
                                                </span>

                                            </div>

                                            <p class="text-muted small mb-2">
                                                <?= sanitize($aiPriorityReason) ?>
                                            </p>

                                            <small class="text-muted">
                                                Current admin priority:
                                                <strong>
                                                    <?= ucfirst($complaint['priority']) ?>
                                                </strong>
                                            </small>

                                        </div>

                                    </div>

                                </div>


                            </div>

                        </div>

                    <?php endif; ?>

            </div>
        </div>

        <!-- Update Status Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Update Status</div>
            <div class="card-body">
                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= sanitize($csrfToken) ?>"
                    >

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">New Status</label>

                        <select name="status" class="form-select">

                            <?php foreach (['pending', 'in_progress', 'resolved', 'rejected'] as $s): ?>

                            <option
                                value="<?= $s ?>"
                                <?= $complaint['status'] === $s ? 'selected' : '' ?>
                            >
                                <?= ucfirst(str_replace('_', ' ', $s)) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Priority -->
                <div class="mb-3">

                    <label class="form-label">Priority</label>

                    <select name="priority" class="form-select">

                        <option value="low"
                            <?= $complaint['priority'] === 'low' ? 'selected' : '' ?>>
                            Low
                        </option>

                        <option value="medium"
                            <?= $complaint['priority'] === 'medium' ? 'selected' : '' ?>>
                            Medium
                        </option>

                        <option value="high"
                            <?= $complaint['priority'] === 'high' ? 'selected' : '' ?>>
                            High
                        </option>

                        <option value="urgent"
                            <?= $complaint['priority'] === 'urgent' ? 'selected' : '' ?>>
                            Urgent
                        </option>

                    </select>

                    <div class="form-text">
                        AI Recommendation:
                        <strong><?= ucfirst($aiPriority) ?></strong>
                    </div>

                </div>

            <!-- Admin Note -->
            <div class="mb-3">
                <label class="form-label">
                    Admin Notes / Update Message
                </label>

                <textarea
                    name="note"
                    class="form-control"
                    rows="3"
                    placeholder="Optional note visible to the resident..."
                ></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                Update Status
            </button>

        </form>
            </div>
        </div>

        <!-- Timeline -->
        <h6 class="fw-bold">Activity Log</h6>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-dot bg-secondary"></div>
                <div class="timeline-content">
                    <strong>Submitted</strong>
                    <div class="text-muted small"><?= date('M d, Y H:i', strtotime($complaint['created_at'])) ?></div>
                </div>
            </div>
            <?php foreach ($updates as $u): ?>

                <div class="border-start border-3 ps-3 mb-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <div class="fw-bold">

                                <i class="bi bi-clock-history me-1"></i>

                                <?= sanitize(
                                    $u['updated_by_name'] ?? 'Admin'
                                ) ?>

                            </div>

                            <div class="small text-muted">

                                <?= date(
                                    'M d, Y H:i',
                                    strtotime($u['created_at'])
                                ) ?>

                            </div>

                        </div>

                    </div>


                    <!-- Status change -->

                    <?php
                    $statusChanged =
                        ($u['old_status'] ?? '') !==
                        ($u['new_status'] ?? '');
                    ?>

                    <?php if ($statusChanged): ?>

                        <div class="mt-3">

                            <div class="small text-muted mb-1">
                                Status changed
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">

                                <?= statusBadge(
                                    $u['old_status']
                                ) ?>

                                <i class="bi bi-arrow-right"></i>

                                <?= statusBadge(
                                    $u['new_status']
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- Priority change -->

                    <?php
                    $priorityChanged =
                        isset($u['old_priority'], $u['new_priority']) &&
                        $u['old_priority'] !== $u['new_priority'];
                    ?>

                    <?php if ($priorityChanged): ?>

                        <div class="mt-3">

                            <div class="small text-muted mb-1">
                                Priority changed
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">

                                <span class="badge bg-<?=
                                    priorityBadge(
                                        $u['old_priority']
                                    )
                                ?>">

                                    <?= sanitize(
                                        ucfirst($u['old_priority'])
                                    ) ?>

                                </span>

                                <i class="bi bi-arrow-right"></i>

                                <span class="badge bg-<?=
                                    priorityBadge(
                                        $u['new_priority']
                                    )
                                ?>">

                                    <?= sanitize(
                                        ucfirst($u['new_priority'])
                                    ) ?>

                                </span>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- Admin note -->

                    <?php if (!empty($u['note'])): ?>

                        <div class="mt-3">

                            <div class="small text-muted mb-1">
                                Admin note
                            </div>

                            <div class="bg-light rounded p-3">

                                <?= nl2br(
                                    sanitize($u['note'])
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
