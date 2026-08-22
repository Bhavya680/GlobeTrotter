<?php
require_once __DIR__ . '/../includes/auth.php';

// Enforce admin login
require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$action = clean_str($_GET['action'] ?? '');
$currentAdminId = current_user_id();

switch ($action) {
    case 'get_user':
        if ($method !== 'GET') json_error('Method not allowed', 405);
        $userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$userId) json_error('user id is required', 400);
        handle_get_user($pdo, $userId);
        break;

    case 'toggle_role':
        if ($method !== 'POST') json_error('Method not allowed', 405);
        handle_toggle_role($pdo, $currentAdminId);
        break;

    case 'delete_user':
        if ($method !== 'POST' && $method !== 'DELETE') json_error('Method not allowed', 405);
        handle_delete_user($pdo, $currentAdminId);
        break;

    default:
        json_error('Invalid action', 400);
}

function handle_get_user(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('
        SELECT id, first_name, last_name, email, phone, city, country, profile_photo,
               additional_info, role, language_pref, created_at, updated_at
        FROM users
        WHERE id = ?
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_error('User not found', 404);
    }

    // Fetch user's trips with stop counts
    $tripsStmt = $pdo->prepare('
        SELECT t.id, t.trip_name, t.start_date, t.end_date, t.status, t.visibility, t.created_at,
               COUNT(s.id) AS stops_count
        FROM trips t
        LEFT JOIN trip_stops s ON s.trip_id = t.id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ');
    $tripsStmt->execute([$userId]);
    $trips = $tripsStmt->fetchAll();

    // Fetch total activities planned by user across all trips
    $actStmt = $pdo->prepare('
        SELECT COUNT(ta.id) AS total_activities
        FROM trip_activities ta
        JOIN trip_stops s ON s.id = ta.trip_stop_id
        JOIN trips t ON t.id = s.trip_id
        WHERE t.user_id = ?
    ');
    $actStmt->execute([$userId]);
    $totalActivities = (int) $actStmt->fetchColumn();

    json_success([
        'user' => $user,
        'trips' => $trips,
        'stats' => [
            'total_trips' => count($trips),
            'total_activities' => $totalActivities
        ]
    ]);
}

function handle_toggle_role(PDO $pdo, int $currentAdminId): void {
    $body = get_request_body();
    $userId = isset($body['user_id']) ? (int) $body['user_id'] : 0;

    if (!$userId) {
        json_error('User ID is required', 400);
    }

    if ($userId === $currentAdminId) {
        json_error('You cannot change your own admin role.', 403);
    }

    $stmt = $pdo->prepare('SELECT id, first_name, last_name, role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        json_error('User not found', 404);
    }

    $newRole = ($targetUser['role'] === 'admin') ? 'user' : 'admin';

    $updateStmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $updateStmt->execute([$newRole, $userId]);

    json_success([
        'user_id' => $userId,
        'new_role' => $newRole,
        'message' => "Role successfully changed to " . ucfirst($newRole) . " for " . htmlspecialchars($targetUser['first_name'] . ' ' . $targetUser['last_name'])
    ]);
}

function handle_delete_user(PDO $pdo, int $currentAdminId): void {
    $body = get_request_body();
    $userId = isset($body['user_id']) ? (int) $body['user_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

    if (!$userId) {
        json_error('User ID is required', 400);
    }

    if ($userId === $currentAdminId) {
        json_error('You cannot delete your own admin account.', 403);
    }

    $stmt = $pdo->prepare('SELECT id, first_name, last_name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        json_error('User not found', 404);
    }

    // Cascade delete user
    $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $deleteStmt->execute([$userId]);

    json_success([
        'user_id' => $userId,
        'deleted' => true,
        'message' => "User " . htmlspecialchars($targetUser['first_name'] . ' ' . $targetUser['last_name']) . " and all associated data have been permanently deleted."
    ]);
}
