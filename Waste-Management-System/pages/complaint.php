<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/complaints.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$user = currentUser();

$csrfToken = getCsrfToken();

$id = (int) ($_GET['id'] ?? 0);
$complaint = getComplaintById($id);

$notificationId = (int) ($_GET['notification_id'] ?? 0);

// Only allow owner or admin
if (!$complaint || ($complaint['user_id'] !== $user['id'] && !isAdmin())) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}

// Mark notification as read only after access is confirmed
if ($notificationId > 0 && $user) {
    markNotificationAsRead($notificationId, (int)$user['id']);
}

$feedbackMessage = '';
$feedbackError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_POST['action']) &&
        $_POST['action'] === 'submit_feedback'
    ) {

        if (!verifyCsrfToken()) {
            $feedbackError = 'Invalid CSRF token.';
        } elseif ($complaint['status'] !== 'resolved') {

            $feedbackError =
                'Feedback can only be submitted after the complaint is resolved.';

        } else {

            $rating = (int) ($_POST['rating'] ?? 0);

            $comment = trim(
                $_POST['feedback_comment'] ?? ''
            );

            $existingFeedback = getComplaintFeedback(
                $id,
                (int) $user['id']
            );

            if ($existingFeedback) {

                $feedbackError =
                    'You have already submitted feedback for this complaint.';

            } elseif ($rating < 1 || $rating > 5) {

                $feedbackError =
                    'Please select a rating from 1 to 5.';

            } else {

                $saved = submitComplaintFeedback(
                    $id,
                    (int) $user['id'],
                    $rating,
                    $comment
                );

                if ($saved) {

                    $feedbackMessage =
                        'Thank you for your feedback!';

                } else {

                    $feedbackError =
                        'Unable to save your feedback. Please try again.';

                }
            }
        }
    }
}

$updates = getComplaintUpdates($id);

$feedback = getComplaintFeedback(
    $id,
    (int) $user['id']
);

$submitted = isset($_GET['submitted']);
include __DIR__ . '/../includes/header.php';
?>

<?php if ($submitted): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>Your complaint has been submitted successfully! We'll notify you as it progresses.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="d-flex align-items-center gap-2 mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
            <h2 class="fw-bold mb-0"><?= sanitize($complaint['title']) ?></h2>
            <?= statusBadge($complaint['status']) ?>
        </div>

        <!-- Details Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <small class="text-muted">Category</small>
                        <p class="fw-semibold mb-0"><?= ucfirst(str_replace('_', ' ', $complaint['category'])) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted">Priority</small>
                        <p class="fw-semibold mb-0"><?= ucfirst($complaint['priority']) ?></p>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Location</small>
                    <p class="mb-0"><i class="bi bi-geo-alt text-danger me-1"></i><?= sanitize($complaint['location']) ?></p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Description</small>
                    <p class="mb-0"><?= nl2br(sanitize($complaint['description'])) ?></p>
                </div>
                <?php if ($complaint['image_path']): ?>
                    <div>
                        <small class="text-muted">Photo</small>
                        <br>
                        <img src="<?= UPLOAD_URL . sanitize($complaint['image_path']) ?>"
                             class="img-fluid rounded mt-1" style="max-height:300px;" alt="Complaint image">
                    </div>
                <?php endif; ?>
                <?php if ($complaint['admin_notes']): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Admin Notes:</strong> <?= sanitize($complaint['admin_notes']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($complaint['ai_category'])): ?>

                    <?php
                    $aiCategory = $complaint['ai_category'];
                    $aiConfidence = $complaint['ai_confidence'];

                    $aiCategoryLower = strtolower($aiCategory);
                    
                    // Suggested waste category
                    if (
                        str_contains($aiCategoryLower, 'plastic') ||
                        str_contains($aiCategoryLower, 'paper') ||
                        str_contains($aiCategoryLower, 'cardboard') ||
                        str_contains($aiCategoryLower, 'glass') ||
                        str_contains($aiCategoryLower, 'metal') ||
                        str_contains($aiCategoryLower, 'clothes') ||
                        str_contains($aiCategoryLower, 'shoes')
                    ) {
                        $aiSuggestedCategory = 'Recycling';
                    } elseif (
                        str_contains($aiCategoryLower, 'battery') ||
                        str_contains($aiCategoryLower, 'hazard') ||
                        str_contains($aiCategoryLower, 'chemical')
                    ) {
                        $aiSuggestedCategory = 'Hazardous Waste';
                    } else {
                        $aiSuggestedCategory = 'Other';
                    }
                    ?>

                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body">
                            
                            <!-- AI Header -->
                            <div class="d-flex align-items-center mb-3">
                                <div
                                    class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-2"
                                    style="width:42px;height:42px;"
                                >
                                    <i class="bi bi-robot text-success fs-5"></i>
                                </div>

                                <div>
                                    <h6 class="fw-bold mb-2">AI Waste Classification</h6>
                                    <small class="text-muted">
                                        Automatically detected from uploaded image
                                    </small>
                                </div>
                            </div>

                            <!-- Detection + Confidence -->
                            <div class="row g-3">

                            <div class="row g-3">

                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded">
                                        <small class="text-muted d-block mb-1">
                                            Detected Waste
                                        </small>

                                        <span class="fw-bold fs-5">
                                            <?= sanitize($aiCategory) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded">
                                        <small class="text-muted d-block mb-1">
                                            AI Confidence
                                        </small>

                                        <span class="fw-bold fs-5 text-success">
                                            <?= number_format((float)$aiConfidence, 2) ?>%
                                        </span>
                                    </div>
                                </div>

                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    Suggested Waste Category
                                </small>

                                <div class="mt-1">
                                    <span class="badge bg-success">
                                        <i class="bi bi-recycle me-1"></i>
                                        <?= sanitize($aiSuggestedCategory) ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline -->
        <h5 id="updates" class="fw-bold mb-3">Status Timeline</h5>
        <div class="timeline mb-4">
            <div class="timeline-item">
                <div class="timeline-dot bg-secondary"></div>
                <div class="timeline-content">
                    <strong>Complaint Submitted</strong>
                    <div class="text-muted small"><?= date('M d, Y H:i', strtotime($complaint['created_at'])) ?></div>
                </div>
            </div>
            <?php foreach ($updates as $u): ?>

                <div class="border-start border-3 ps-3 mb-4">

                    <!-- Updated by -->

                    <div class="fw-bold">

                        <i class="bi bi-clock-history me-1"></i>

                        <?= sanitize(
                            $u['updated_by_name'] ?? 'Admin'
                        ) ?>

                    </div>


                    <!-- Date -->

                    <div class="small text-muted mb-3">

                        <?= date(
                            'M d, Y H:i',
                            strtotime($u['created_at'])
                        ) ?>

                    </div>


                    <!-- Status Change -->

                    <?php
                    $statusChanged =
                        ($u['old_status'] ?? '') !==
                        ($u['new_status'] ?? '');
                    ?>

                    <?php if ($statusChanged): ?>

                        <div class="mb-3">

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


                    <!-- Priority Change -->

                    <?php
                    $priorityChanged =
                        isset($u['old_priority'], $u['new_priority']) &&
                        $u['old_priority'] !== $u['new_priority'];
                    ?>

                    <?php if ($priorityChanged): ?>

                        <div class="mb-3">

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


                    <!-- Admin Note -->

                    <?php if (!empty($u['note'])): ?>

                        <div>

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

        <?php if ($complaint['status'] === 'resolved'): ?>

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-star me-2"></i>
                    Your Feedback
                </div>

                <div class="card-body">

                    <?php if ($feedbackMessage): ?>

                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?= sanitize($feedbackMessage) ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($feedbackError): ?>

                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?= sanitize($feedbackError) ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($feedback): ?>

                        <div class="text-center">

                            <p class="fw-semibold mb-2">
                                Thank you for your feedback!
                            </p>

                            <div class="mb-3">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <i class="bi bi-star<?=
                                        $i <= (int)$feedback['rating']
                                            ? '-fill'
                                            : ''
                                    ?> text-warning fs-4"></i>

                                <?php endfor; ?>

                            </div>

                            <p class="text-muted mb-0">
                                You rated this complaint
                                <strong>
                                    <?= (int)$feedback['rating'] ?>/5
                                </strong>
                            </p>


                            <?php if (!empty($feedback['comment'])): ?>

                                <div class="alert alert-light border mt-3 mb-0 text-start">

                                    <strong>Comment:</strong>

                                    <div class="mt-1">
                                        <?= nl2br(
                                            sanitize($feedback['comment'])
                                        ) ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>


                    <?php else: ?>

                        <p class="text-muted">
                            Your complaint has been resolved.
                            Please rate the service you received.
                        </p>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= sanitize($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="submit_feedback"
                            >

                            <div class="mb-3">

                                <label
                                    for="rating"
                                    class="form-label fw-semibold"
                                >
                                    Rating
                                </label>

                                <select
                                    name="rating"
                                    id="rating"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select your rating
                                    </option>

                                    <option value="5">
                                        ⭐⭐⭐⭐⭐ Excellent
                                    </option>

                                    <option value="4">
                                        ⭐⭐⭐⭐ Very Good
                                    </option>

                                    <option value="3">
                                        ⭐⭐⭐ Good
                                    </option>

                                    <option value="2">
                                        ⭐⭐ Poor
                                    </option>

                                    <option value="1">
                                        ⭐ Very Poor
                                    </option>

                                </select>

                            </div>


                            <div class="mb-3">

                                <label
                                    for="feedback_comment"
                                    class="form-label fw-semibold"
                                >
                                    Comment
                                </label>

                                <textarea
                                    name="feedback_comment"
                                    id="feedback_comment"
                                    class="form-control"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="Tell us about your experience..."
                                ></textarea>

                                <div class="form-text">
                                    Maximum 1000 characters.
                                </div>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                <i class="bi bi-send me-1"></i>
                                Submit Feedback
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Complaint Details</h6>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2"><span class="text-muted">ID:</span> #<?= $complaint['id'] ?></li>
                    <li class="mb-2"><span class="text-muted">Reported by:</span> <?= sanitize($complaint['user_name']) ?></li>
                    <li class="mb-2"><span class="text-muted">Submitted:</span> <?= date('M d, Y', strtotime($complaint['created_at'])) ?></li>
                    <?php if ($complaint['resolved_at']): ?>
                        <li class="mb-2"><span class="text-muted">Resolved:</span> <?= date('M d, Y', strtotime($complaint['resolved_at'])) ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if ($complaint['latitude'] && $complaint['longitude']): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <iframe
                    src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $complaint['longitude']-0.01 ?>,<?= $complaint['latitude']-0.01 ?>,<?= $complaint['longitude']+0.01 ?>,<?= $complaint['latitude']+0.01 ?>&layer=mapnik&marker=<?= $complaint['latitude'] ?>,<?= $complaint['longitude'] ?>"
                    width="100%" height="200" style="border-radius:0.5rem;border:0;"></iframe>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
