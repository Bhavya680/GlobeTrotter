<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function is_logged_in(): bool {
    return current_user_id() !== null;
}

function login_user(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array {
    $userId = current_user_id();
    if ($userId === null) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, name, email, profile_photo, language_pref, is_admin, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function is_admin_user(): bool {
    $user = current_user();
    return $user !== null && (bool) $user['is_admin'];
}

function require_login(): int {
    $userId = current_user_id();
    if ($userId === null) {
        json_error('Authentication required', 401);
    }
    return $userId;
}

function require_admin(): void {
    require_login();
    if (!is_admin_user()) {
        json_error('Admin access required', 403);
    }
}

function require_login_page(string $redirectTo = '/login.php'): int {
    $userId = current_user_id();
    if ($userId === null) {
        header('Location: ' . $redirectTo);
        exit;
    }
    return $userId;
}

function user_owns_trip(PDO $pdo, int $userId, int $tripId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM trips WHERE id = ? AND user_id = ?');
    $stmt->execute([$tripId, $userId]);
    return (bool) $stmt->fetch();
}

function user_owns_stop(PDO $pdo, int $userId, int $stopId): bool {
    $stmt = $pdo->prepare('
        SELECT 1 FROM stops s
        JOIN trips t ON t.id = s.trip_id
        WHERE s.id = ? AND t.user_id = ?
    ');
    $stmt->execute([$stopId, $userId]);
    return (bool) $stmt->fetch();
}
