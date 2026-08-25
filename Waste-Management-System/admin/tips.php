<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = getDB();

$csrfToken = getCsrfToken();

$success = '';
$error = '';

$uploadDir = __DIR__ . '/../uploads/tips/';
$uploadUrl = APP_URL . '/uploads/tips/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/*
|--------------------------------------------------------------------------
| Helper: Upload Image
|--------------------------------------------------------------------------
*/

function uploadTipImage(array $file, string $uploadDir): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed.');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Image must be smaller than 5 MB.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        throw new Exception('Only JPG, PNG and WEBP images are allowed.');
    }

    $extension = $allowedTypes[$mimeType];

    $filename = 'tip_' . bin2hex(random_bytes(12)) . '.' . $extension;

    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Unable to save uploaded image.');
    }

    return $filename;
}


/*
|--------------------------------------------------------------------------
| Delete Tip
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tip'])) {

    if (!verifyCsrfToken()) {
        exit('Invalid CSRF token.');
    }

    $tipId = (int)($_POST['tip_id'] ?? 0);

    if ($tipId > 0) {

        $stmt = $db->prepare(
            "SELECT image_path FROM recycling_tips WHERE id = ?"
        );

        $stmt->execute([$tipId]);

        $tip = $stmt->fetch();

        if ($tip) {

            if (!empty($tip['image_path'])) {

                $imagePath = $uploadDir . basename($tip['image_path']);

                if (is_file($imagePath)) {
                    unlink($imagePath);
                }
            }

            $stmt = $db->prepare(
                "DELETE FROM recycling_tips WHERE id = ?"
            );

            $stmt->execute([$tipId]);

            $success = 'Recycling tip deleted successfully.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Add Tip
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tip'])) {

    if (!verifyCsrfToken()) {
        exit('Invalid CSRF token.');
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;

    if ($title === '' || $content === '') {

        $error = 'Title and content are required.';

    } else {

        try {

            $imagePath = null;

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $imagePath = uploadTipImage(
                    $_FILES['image'],
                    $uploadDir
                );
            }

            $stmt = $db->prepare(
                "INSERT INTO recycling_tips
                (title, content, category, image_path, published)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $title,
                $content,
                $category !== '' ? $category : null,
                $imagePath,
                $published
            ]);

            $success = 'Recycling tip added successfully.';

        } catch (Exception $e) {

            $error = $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Update Tip
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tip'])) {

    if (!verifyCsrfToken()) {
        exit('Invalid CSRF token.');
    }

    $tipId = (int)($_POST['tip_id'] ?? 0);

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;

    if ($tipId <= 0) {

        $error = 'Invalid tip ID.';

    } elseif ($title === '' || $content === '') {

        $error = 'Title and content are required.';

    } else {

        try {

            $stmt = $db->prepare(
                "SELECT image_path
                 FROM recycling_tips
                 WHERE id = ?"
            );

            $stmt->execute([$tipId]);

            $existingTip = $stmt->fetch();

            if (!$existingTip) {

                $error = 'Recycling tip not found.';

            } else {

                $imagePath = $existingTip['image_path'];

                /*
                 * Replace image if a new image was uploaded
                 */

                if (
                    isset($_FILES['image']) &&
                    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    $newImage = uploadTipImage(
                        $_FILES['image'],
                        $uploadDir
                    );

                    if (!empty($imagePath)) {

                        $oldImage = $uploadDir . basename($imagePath);

                        if (is_file($oldImage)) {
                            unlink($oldImage);
                        }
                    }

                    $imagePath = $newImage;
                }


                /*
                 * Remove image
                 */

                if (isset($_POST['remove_image'])) {

                    if (!empty($imagePath)) {

                        $oldImage = $uploadDir . basename($imagePath);

                        if (is_file($oldImage)) {
                            unlink($oldImage);
                        }
                    }

                    $imagePath = null;
                }


                $stmt = $db->prepare(
                    "UPDATE recycling_tips
                     SET title = ?,
                         content = ?,
                         category = ?,
                         image_path = ?,
                         published = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $title,
                    $content,
                    $category !== '' ? $category : null,
                    $imagePath,
                    $published,
                    $tipId
                ]);

                $success = 'Recycling tip updated successfully.';
            }

        } catch (Exception $e) {

            $error = $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Tip for Editing
|--------------------------------------------------------------------------
*/

$editTip = null;

if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    if ($editId > 0) {

        $stmt = $db->prepare(
            "SELECT *
             FROM recycling_tips
             WHERE id = ?"
        );

        $stmt->execute([$editId]);

        $editTip = $stmt->fetch();
    }
}


/*
|--------------------------------------------------------------------------
| Get All Tips
|--------------------------------------------------------------------------
*/

$stmt = $db->query(
    "SELECT *
     FROM recycling_tips
     ORDER BY created_at DESC"
);

$tips = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-5">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                <i class="bi bi-lightbulb"></i>
                Manage Recycling Tips
            </h2>

            <p class="text-muted mb-0">
                Add, edit, publish and manage recycling education.
            </p>

        </div>

        <a
            href="<?= APP_URL ?>/admin/index.php"
            class="btn btn-outline-secondary"
        >
            ← Admin Dashboard
        </a>

    </div>


    <!-- Success -->

    <?php if ($success): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <?= sanitize($success) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- Error -->

    <?php if ($error): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <?= sanitize($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <div class="row">

        <!-- ADD / EDIT -->

        <div class="col-lg-5 mb-4">

            <div class="card shadow-sm">

                <div class="card-header">

                    <h5 class="mb-0">

                        <?php if ($editTip): ?>

                            <i class="bi bi-pencil"></i>
                            Edit Recycling Tip

                        <?php else: ?>

                            <i class="bi bi-plus-circle"></i>
                            Add Recycling Tip

                        <?php endif; ?>

                    </h5>

                </div>


                <div class="card-body">

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >
                        
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= sanitize($csrfToken) ?>"
                        >

                        <?php if ($editTip): ?>

                            <input
                                type="hidden"
                                name="tip_id"
                                value="<?= (int)$editTip['id'] ?>"
                            >

                        <?php endif; ?>


                        <!-- Title -->

                        <div class="mb-3">

                            <label class="form-label">
                                Tip Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                placeholder="Example: Separate Wet and Dry Waste"
                                value="<?= sanitize($editTip['title'] ?? '') ?>"
                                required
                            >

                        </div>


                        <!-- Category -->

                        <div class="mb-3">

                            <label class="form-label">
                                Category
                            </label>

                            <select
                                name="category"
                                class="form-select"
                            >

                                <option value="">
                                    Select Category
                                </option>

                                <?php

                                $categories = [
                                    'Plastic',
                                    'Paper',
                                    'Glass',
                                    'Organic',
                                    'Electronics',
                                    'General'
                                ];

                                ?>

                                <?php foreach ($categories as $category): ?>

                                    <option
                                        value="<?= sanitize($category) ?>"
                                        <?= (($editTip['category'] ?? '') === $category)
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        <?= sanitize($category) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- Content -->

                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="content"
                                class="form-control"
                                rows="6"
                                placeholder="Write the recycling tip..."
                                required
                            ><?= sanitize($editTip['content'] ?? '') ?></textarea>

                        </div>


                        <!-- Image -->

                        <div class="mb-3">

                            <label class="form-label">
                                Recycling Image
                            </label>

                            <input
                                type="file"
                                name="image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <div class="form-text">
                                JPG, PNG or WEBP. Maximum 5 MB.
                            </div>

                        </div>


                        <!-- Existing Image -->

                        <?php if (
                            $editTip &&
                            !empty($editTip['image_path'])
                        ): ?>

                            <div class="mb-3">

                                <p class="mb-2">
                                    Current Image:
                                </p>

                                <img
                                    src="<?= $uploadUrl . rawurlencode($editTip['image_path']) ?>"
                                    alt="Current tip image"
                                    class="img-fluid rounded"
                                    style="max-height:180px;"
                                >

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        name="remove_image"
                                        class="form-check-input"
                                        id="removeImage"
                                    >

                                    <label
                                        class="form-check-label"
                                        for="removeImage"
                                    >
                                        Remove current image
                                    </label>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- Published -->

                        <div class="form-check form-switch mb-4">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="published"
                                id="published"
                                <?= (!$editTip || !empty($editTip['published']))
                                    ? 'checked'
                                    : '' ?>
                            >

                            <label
                                class="form-check-label"
                                for="published"
                            >
                                Published
                            </label>

                            <div class="form-text">
                                Published tips are visible to residents.
                            </div>

                        </div>


                        <!-- Buttons -->

                        <?php if ($editTip): ?>

                            <button
                                type="submit"
                                name="update_tip"
                                class="btn btn-success"
                            >
                                <i class="bi bi-save"></i>
                                Update Tip
                            </button>

                            <a
                                href="<?= APP_URL ?>/admin/tips.php"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                        <?php else: ?>

                            <button
                                type="submit"
                                name="add_tip"
                                class="btn btn-success"
                            >
                                <i class="bi bi-plus-circle"></i>
                                Add Tip
                            </button>

                        <?php endif; ?>

                    </form>

                </div>

            </div>

        </div>


        <!-- TIPS LIST -->

        <div class="col-lg-7">

            <div class="card shadow-sm">

                <div class="card-header">

                    <h5 class="mb-0">

                        <i class="bi bi-list"></i>
                        Existing Recycling Tips

                    </h5>

                </div>


                <div class="card-body p-0">

                    <?php if (!$tips): ?>

                        <div class="p-4 text-center text-muted">

                            <i class="bi bi-lightbulb fs-1"></i>

                            <p class="mt-2 mb-0">
                                No recycling tips found.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0">

                                <thead class="table-light">

                                    <tr>

                                        <th>
                                            Image
                                        </th>

                                        <th>
                                            Tip
                                        </th>

                                        <th>
                                            Category
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Actions
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($tips as $tip): ?>

                                        <tr>

                                            <!-- Image -->

                                            <td>

                                                <?php if (!empty($tip['image_path'])): ?>

                                                    <img
                                                        src="<?= $uploadUrl . rawurlencode($tip['image_path']) ?>"
                                                        alt=""
                                                        width="70"
                                                        height="55"
                                                        class="rounded"
                                                        style="object-fit:cover;"
                                                    >

                                                <?php else: ?>

                                                    <div
                                                        class="bg-success bg-opacity-10 rounded d-flex align-items-center justify-content-center"
                                                        style="width:70px;height:55px;"
                                                    >

                                                        <i class="bi bi-recycle text-success"></i>

                                                    </div>

                                                <?php endif; ?>

                                            </td>


                                            <!-- Title -->

                                            <td>

                                                <strong>
                                                    <?= sanitize($tip['title']) ?>
                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    <?= sanitize(
                                                        mb_substr(
                                                            $tip['content'],
                                                            0,
                                                            70
                                                        )
                                                    ) ?>

                                                    <?php if (
                                                        mb_strlen($tip['content']) > 70
                                                    ): ?>
                                                        ...
                                                    <?php endif; ?>

                                                </small>

                                            </td>


                                            <!-- Category -->

                                            <td>

                                                <?php if (!empty($tip['category'])): ?>

                                                    <span class="badge bg-success">

                                                        <?= sanitize($tip['category']) ?>

                                                    </span>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        General
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- Status -->

                                            <td>

                                                <?php if ((int)$tip['published'] === 1): ?>

                                                    <span class="badge bg-success">
                                                        Published
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-secondary">
                                                        Draft
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- Actions -->

                                            <td>

                                                <div class="d-flex gap-1">

                                                    <!-- Edit -->

                                                    <a
                                                        href="<?= APP_URL ?>/admin/tips.php?edit=<?= (int)$tip['id'] ?>"
                                                        class="btn btn-sm btn-outline-primary"
                                                        title="Edit"
                                                    >

                                                        <i class="bi bi-pencil"></i>

                                                    </a>


                                                    <!-- Delete -->

                                                    <form
                                                        method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete this tip?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= sanitize($csrfToken) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="tip_id"
                                                            value="<?= (int)$tip['id'] ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            name="delete_tip"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Delete"
                                                        >

                                                            <i class="bi bi-trash"></i>

                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>