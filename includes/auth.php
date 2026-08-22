<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        redirect('dashboard.php');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, profile_photo FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function login($email, $password) {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT id, first_name, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        return true;
    }
    return false;
}

function logout() {
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}
?>
