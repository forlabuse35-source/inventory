<?php
/**
 * Authentication & Session Management
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login(string $username, string $password): array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username AND status = "active" LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];

    return ['success' => true, 'message' => 'Login successful.'];
}

function logout(): void
{
    session_unset();
    session_destroy();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function isStaff(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'staff';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
        header('Location: index.php');
        exit;
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ];
}

function getAdminCount(): int
{
    $db   = getDB();
    $stmt = $db->query('SELECT COUNT(*) FROM users WHERE role = "admin"');
    return (int) $stmt->fetchColumn();
}

function canCreateAdmin(): bool
{
    return getAdminCount() < MAX_ADMIN_ACCOUNTS;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string
{
    $key = 'flash_' . $type;
    $msg = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $msg;
}
