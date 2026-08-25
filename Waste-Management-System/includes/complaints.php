<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notifications.php';

function getComplaints(array $filters = []): array {
    $db = getDB();
    $sql = "SELECT c.*, u.name AS user_name, u.email AS user_email
            FROM complaints c
            JOIN users u ON c.user_id = u.id
            WHERE 1=1";
    $params = [];

    if (!empty($filters['user_id'])) {
        $sql .= " AND c.user_id = ?";
        $params[] = $filters['user_id'];
    }
    if (!empty($filters['status'])) {
        $sql .= " AND c.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['category'])) {
        $sql .= " AND c.category = ?";
        $params[] = $filters['category'];
    }

    $sql .= " ORDER BY c.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getComplaintById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT c.*, u.name AS user_name, u.email AS user_email
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function submitComplaint(array $data, ?array $file = null): int {
    $db = getDB();
    $imagePath = null;

    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Image upload failed.');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Image must be smaller than 5 MB.');
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        $mimeType = mime_content_type($file['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            throw new Exception(
                'Only JPG, PNG, GIF and WEBP images are allowed.'
            );
        }

        // Verify that the uploaded file is actually an image.
        if (@getimagesize($file['tmp_name']) === false) {
            throw new Exception('Uploaded file is not a valid image.');
        }

        $extension = $allowedTypes[$mimeType];

        $filename = 'complaint_' . bin2hex(random_bytes(12)) . '.' . $extension;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $destination = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Unable to save uploaded image.');
        }

       $imagePath = $filename;
    }

    // AI classification result
    $aiCategory = $data['ai_category'] ?? null;
    $aiConfidence = $data['ai_confidence'] ?? null;

    $stmt = $db->prepare(
        "INSERT INTO complaints (
            user_id,
            title,
            description,
            category,
            location,
            latitude,
            longitude,
            image_path,
            ai_category,
            ai_confidence,
            priority
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $data['user_id'],
        $data['title'],
        $data['description'],
        $data['category'],
        $data['location'],
        $data['latitude'] ?? null,
        $data['longitude'] ?? null,
        $imagePath,
        $aiCategory,
        $aiConfidence,
        $data['priority'] ?? 'medium',
    ]);

    return (int) $db->lastInsertId();
}

function updateComplaintStatus(
    int $id,
    string $status,
    int $adminId,
    string $note = '',
    ?string $priority = null
): void {

    $db = getDB();

    $complaint = getComplaintById($id);

    if (!$complaint) {
        return;
    }

    // Keep existing priority if no new priority was provided
    $newPriority = $priority ?? $complaint['priority'];

    // Check what actually changed
    $statusChanged = $complaint['status'] !== $status;
    $priorityChanged = $complaint['priority'] !== $newPriority;
    $noteAdded = trim($note) !== '';

    // Nothing changed → do not create an update/notification
    if (!$statusChanged && !$priorityChanged && !$noteAdded) {
        return;
    }

    // Update complaint
    $resolvedAt = null;

    if ($status === 'resolved') {
        $resolvedAt = $complaint['resolved_at']
            ?? date('Y-m-d H:i:s');
    }

    $stmt = $db->prepare(
        "UPDATE complaints
         SET status = ?,
             priority = ?,
             admin_notes = ?,
             resolved_at = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $status,
        $newPriority,
        $note,
        $resolvedAt,
        $id
    ]);

    // Save activity log only when something changed
    $stmt = $db->prepare(
        "INSERT INTO complaint_updates (
            complaint_id,
            updated_by,
            old_status,
            new_status,
            old_priority,
            new_priority,
            note
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $id,
        $adminId,
        $complaint['status'],
        $status,
        $complaint['priority'],
        $newPriority,
        $note
    ]);

    /*
     * Create resident notification only when
     * status, priority or note actually changed.
     */
    if (!empty($complaint['user_id'])) {

        $parts = [];

        if ($statusChanged) {
            $statusLabel = ucfirst(
                str_replace('_', ' ', $status)
            );

            $parts[] = "Your complaint status is now {$statusLabel}.";
        }

        if ($priorityChanged) {
            $priorityLabel = ucfirst($newPriority);

            $parts[] = "Priority has been changed to {$priorityLabel}.";
        }

        if ($noteAdded) {
            $parts[] = "Admin note: " . trim($note);
        }

        if (!empty($parts)) {

            $notificationTitle =
                "Complaint #{$id} Updated";

            $notificationMessage =
                implode(' ', $parts);

            createNotification(
                (int) $complaint['user_id'],
                $id,
                $notificationTitle,
                $notificationMessage,
                'complaint_update'
            );
        }
    }
}

function getComplaintUpdates(int $complaintId): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT cu.*, u.name AS updated_by_name
         FROM complaint_updates cu
         JOIN users u ON cu.updated_by = u.id
         WHERE cu.complaint_id = ?
         ORDER BY cu.created_at ASC"
    );
    $stmt->execute([$complaintId]);
    return $stmt->fetchAll();
}

function getStats(): array {
    $db = getDB();
    $stats = [];
    foreach (['pending', 'in_progress', 'resolved', 'rejected'] as $status) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM complaints WHERE status = ?");
        $stmt->execute([$status]);
        $stats[$status] = (int) $stmt->fetchColumn();
    }
    $stats['total'] = array_sum($stats);
    return $stats;
}

function getComplaintStatistics(): array
{
    $db = getDB();

    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'rejected' => 0,
        'high_priority' => 0,
        'urgent_priority' => 0,
    ];

    // Total complaints
    $stmt = $db->query(
        "SELECT COUNT(*) FROM complaints"
    );

    $stats['total'] = (int) $stmt->fetchColumn();

    // Status counts
    $stmt = $db->query(
        "SELECT status, COUNT(*) AS total
         FROM complaints
         GROUP BY status"
    );

    foreach ($stmt->fetchAll() as $row) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = (int) $row['total'];
        }
    }

    // High priority
    $stmt = $db->query(
        "SELECT COUNT(*)
         FROM complaints
         WHERE priority = 'high'"
    );

    $stats['high_priority'] = (int) $stmt->fetchColumn();

    // Urgent priority
    $stmt = $db->query(
        "SELECT COUNT(*)
         FROM complaints
         WHERE priority = 'urgent'"
    );

    $stats['urgent_priority'] = (int) $stmt->fetchColumn();

    return $stats;
}

function getComplaintCategoryStatistics(): array
{
    $db = getDB();

    $stmt = $db->query(
        "SELECT category, COUNT(*) AS total
         FROM complaints
         GROUP BY category
         ORDER BY total DESC"
    );

    return $stmt->fetchAll();
}

function getComplaintForUser(int $complaintId, int $userId): ?array
{
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT
            c.*,
            u.name AS user_name,
            u.email AS user_email
         FROM complaints c
         INNER JOIN users u ON u.id = c.user_id
         WHERE c.id = ?
         AND c.user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $complaintId,
        $userId
    ]);

    return $stmt->fetch() ?: null;
}

function getComplaintFeedback(
    int $complaintId,
    int $userId
): ?array {
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT *
         FROM complaint_feedback
         WHERE complaint_id = ?
         AND user_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $complaintId,
        $userId
    ]);

    return $stmt->fetch() ?: null;
}


function submitComplaintFeedback(
    int $complaintId,
    int $userId,
    int $rating,
    string $comment = ''
): bool {

    if ($rating < 1 || $rating > 5) {
        return false;
    }

    $db = getDB();

    $stmt = $db->prepare(
        "INSERT INTO complaint_feedback
        (
            complaint_id,
            user_id,
            rating,
            comment
        )
        VALUES (?, ?, ?, ?)"
    );

    return $stmt->execute([
        $complaintId,
        $userId,
        $rating,
        trim($comment)
    ]);
}