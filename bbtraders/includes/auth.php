<?php
/**
 * B&B TRADERS BD - Authentication Helpers
 */

function current_user() {
    static $user = null;
    if ($user !== null) return $user ?: null;
    if (empty($_SESSION['user_id'])) { $user = false; return null; }

    global $pdo;
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, address, city, postal_code, avatar, role, status
                            FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') { $user = false; return null; }
    $user = $row;
    return $user;
}

function is_logged_in() {
    return current_user() !== null;
}

function is_admin() {
    $user = current_user();
    return $user && in_array($user['role'], ['admin', 'super_admin'], true);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

function require_admin() {
    if (!is_logged_in() || !is_admin()) {
        redirect('login.php');
    }
}

function login_user($userId) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user() {
    $_SESSION = [];
    session_regenerate_id(true);
}
