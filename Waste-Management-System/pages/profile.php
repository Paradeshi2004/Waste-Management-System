<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$user = currentUser();

$csrfToken = getCsrfToken();

$success = '';
$error = '';

// Get complete user information
$stmt = $db->prepare("SELECT id, name, email, phone, address, role FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    die('User profile not found.');
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    if (!verifyCsrfToken()) {
        $error = 'Invalid CSRF token.';
    } else {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        // Check if another user already has this email
        $stmt = $db->prepare(
            "SELECT id FROM users WHERE email = ? AND id != ?"
        );
        $stmt->execute([$email, $user['id']]);

        if ($stmt->fetch()) {
            $error = 'This email is already being used by another account.';
        } else {

            $stmt = $db->prepare(
                "UPDATE users
                 SET name = ?, email = ?, phone = ?, address = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $name,
                $email,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                $user['id']
            ]);

            // Update session information
            startSession();

            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;

            $success = 'Profile updated successfully!';

            // Reload profile data
            $stmt = $db->prepare(
                "SELECT id, name, email, phone, address, role
                 FROM users
                 WHERE id = ?"
            );
            $stmt->execute([$user['id']]);
            $profile = $stmt->fetch();
        }
    }
}
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    if (!verifyCsrfToken()) {
        $error = 'Invalid CSRF token.';
    } else {

        $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Please fill in all password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {

        // Get current password hash
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($currentPassword, $account['password'])) {
            $error = 'Current password is incorrect.';
        } else {

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = $db->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );

            $stmt->execute([
                $newHash,
                $user['id']
            ]);

            $success = 'Password changed successfully!';
        }
    }
}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-person-circle"></i>
                    Admin Profile
                </h2>

                <p class="text-muted">
                    Manage your administrator account information.
                </p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= sanitize($success) ?>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= sanitize($error) ?>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Information -->

            <div class="card shadow-sm mb-4">

                <div class="card-header">
                    <h5 class="mb-0">
                        Profile Information
                    </h5>
                </div>

                <div class="card-body">

                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= sanitize($csrfToken) ?>"
                        >

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    value="<?= sanitize($profile['name'] ?? '') ?>"
                                    required
                                >

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    value="<?= sanitize($profile['email'] ?? '') ?>"
                                    required
                                >

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Phone
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                    value="<?= sanitize($profile['phone'] ?? '') ?>"
                                >

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Account Role
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= sanitize(ucfirst($profile['role'] ?? 'Admin')) ?>"
                                    readonly
                                >

                            </div>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Address
                            </label>

                            <textarea
                                name="address"
                                class="form-control"
                                rows="3"
                            ><?= sanitize($profile['address'] ?? '') ?></textarea>

                        </div>


                        <button
                            type="submit"
                            name="update_profile"
                            class="btn btn-success"
                        >
                            <i class="bi bi-save"></i>
                            Update Profile
                        </button>

                    </form>

                </div>

            </div>


            <!-- Change Password -->

            <div class="card shadow-sm">

                <div class="card-header">

                    <h5 class="mb-0">
                        Change Password
                    </h5>

                </div>

                <div class="card-body">

                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= sanitize($csrfToken) ?>"
                        >

                        <div class="mb-3">

                            <label class="form-label">
                                Current Password
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <input
                                type="password"
                                name="new_password"
                                class="form-control"
                                minlength="6"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                minlength="6"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            name="change_password"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-key"></i>
                            Change Password
                        </button>

                    </form>

                </div>

            </div>


            <!-- Back -->

            <div class="mt-4">

                <a
                    href="<?= APP_URL ?>/admin/index.php"
                    class="btn btn-outline-secondary"
                >
                    ← Back to Admin Dashboard
                </a>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>