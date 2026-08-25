<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();

$csrfToken = getCsrfToken();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        $error = 'Invalid CSRF token.';
    } else {

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!loginUser($email, $password)) {
        $error = "Invalid email or password.";
    } else {
        header('Location: ' . APP_URL . '/pages/dashboard.php');
        exit;
    }
}
}
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h3 class="fw-bold text-center mb-1"><i class="bi bi-person-lock text-success me-2"></i>Login</h3>
                <p class="text-muted text-center mb-4">Access your waste management account</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com"
                               value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="passwordField" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-semibold">Login</button>
                </form>

                <hr>
                <p class="text-center mb-0">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const field = document.getElementById('passwordField');
    const icon = this.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
