<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        exit('Invalid CSRF token.');
    }

    $data = [
        'name'     => trim($_POST['name'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'phone'    => trim($_POST['phone'] ?? ''),
        'address'  => trim($_POST['address'] ?? ''),
    ];

    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        $error = "Name, email, and password are required.";
    } elseif (strlen($data['password']) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($data['password'] !== ($_POST['confirm_password'] ?? '')) {
        $error = "Passwords do not match.";
    } else {
        $result = registerUser($data);
        if ($result === true) {
            $success = "Account created! You can now log in.";
        } else {
            $error = $result;
        }
    }
}
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h3 class="fw-bold text-center mb-1"><i class="bi bi-person-plus text-success me-2"></i>Create Account</h3>
                <p class="text-muted text-center mb-4">Join the Waste Management System</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= sanitize($success) ?> <a href="login.php">Login now</a></div>
                <?php endif; ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= sanitize(getCsrfToken()) ?>"
                    >

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= sanitize($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-semibold">Create Account</button>
                </form>

                <hr>
                <p class="text-center mb-0">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
