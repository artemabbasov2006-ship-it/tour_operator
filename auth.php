<?php
// auth.php — сессия и проверки ролей

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role($roles): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    $roles = (array)$roles;
    $user = current_user();
    $role = $user['role_code'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        echo '<h1>403 Доступ запрещён</h1>';
        exit;
    }
}
