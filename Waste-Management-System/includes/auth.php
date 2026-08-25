<?php
require_once __DIR__ . '/config.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax'
        ]);

        session_start();
    }
}

function getCsrfToken(): string {
    startSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): bool {
    startSession();

    return !empty($_POST['csrf_token']) &&
        !empty($_SESSION['csrf_token']) &&
        hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        );
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();

    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/pages/dashboard.php');
        exit;
    }
}

function loginUser(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        startSession();

        // Prevent session fixation after authentication
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        return true;
    }
    return false;
}

function registerUser(array $data): bool|string {
    $db = getDB();
    // Check duplicate email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return "Email already registered.";
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $data['name'],
        $data['email'],
        $hash,
        $data['phone'] ?? null,
        $data['address'] ?? null,
    ]);
    return true;
}

function logoutUser(): void {
    startSession();

    // Clear all session data
    $_SESSION = [];

    // Expire the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy the server-side session
    session_destroy();

    header('Location: ' . APP_URL . '/pages/login.php');
    exit;
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    startSession();
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'],
    ];
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
