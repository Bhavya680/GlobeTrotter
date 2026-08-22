<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];
$action = clean_str($_GET['action'] ?? '');

switch ($method) {
    case 'GET':
        if ($action === 'saved_destinations') {
            get_saved_destinations($pdo, $userId);
        } else {
            handle_get($pdo, $userId);
        }
        break;
    case 'PUT':
    case 'POST':
        if ($action === 'toggle_saved') {
            toggle_saved_destination($pdo, $userId);
        } elseif ($action === 'change_password') {
            handle_change_password($pdo, $userId);
        } else {
            handle_update($pdo, $userId);
        }
        break;
    case 'DELETE':
        handle_delete($pdo, $userId);
        break;
    default:
        json_error('Method not allowed', 405);
}

function handle_get(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('
        SELECT id, first_name, last_name, email, phone, city, country, profile_photo, additional_info, language_pref, preferences, role, created_at
        FROM users WHERE id = ?
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_error('User not found', 404);
    }

    $savedStmt = $pdo->prepare('
        SELECT c.id, c.name, c.country, c.image_url, c.cost_index
        FROM saved_destinations sd
        JOIN cities c ON c.id = sd.city_id
        WHERE sd.user_id = ?
        ORDER BY sd.saved_at DESC
    ');
    $savedStmt->execute([$userId]);
    $user['saved_destinations'] = $savedStmt->fetchAll();

    json_success($user);
}

function handle_update(PDO $pdo, int $userId): void {
    $body = !empty($_FILES) ? $_POST : get_request_body();

    $fields = [];
    $params = [];

    foreach (['first_name', 'last_name', 'phone', 'city', 'country', 'additional_info', 'language_pref'] as $key) {
        if (isset($body[$key])) {
            $fields[] = "{$key} = ?";
            $params[] = clean_str($body[$key]);
        }
    }

    if (isset($body['preferences'])) {
        $fields[] = 'preferences = ?';
        $params[] = is_string($body['preferences']) ? $body['preferences'] : json_encode($body['preferences']);
    }

    if (isset($body['email']) && trim($body['email']) !== '') {
        $newEmail = strtolower(clean_str($body['email']));
        if (!is_valid_email($newEmail)) {
            json_error('Invalid email address');
        }
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$newEmail, $userId]);
        if ($check->fetch()) {
            json_error('That email is already in use by another account');
        }
        $fields[] = 'email = ?';
        $params[] = $newEmail;
    }

    try {
        if (!empty($_FILES['profile_photo']['name'])) {
            $photoUrl = handle_image_upload('profile_photo', 'profiles');
            if ($photoUrl !== null) {
                $oldStmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $oldStmt->execute([$userId]);
                $oldPhoto = $oldStmt->fetchColumn();
                if ($oldPhoto && !str_starts_with($oldPhoto, 'http')) {
                    $oldPath = __DIR__ . '/../assets/uploads/profiles/' . basename($oldPhoto);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $fields[] = 'profile_photo = ?';
                $params[] = $photoUrl;
            }
        }
    } catch (RuntimeException $e) {
        json_error($e->getMessage());
    }

    if (!$fields) {
        json_error('No valid fields to update');
    }

    $params[] = $userId;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    handle_get($pdo, $userId);
}

function handle_change_password(PDO $pdo, int $userId): void {
    $body = get_request_body();
    
    $current = $body['current_password'] ?? '';
    $new = $body['new_password'] ?? '';
    $confirm = $body['confirm_password'] ?? '';
    
    if (strlen($new) < 8) {
        json_error('New password must be at least 8 characters');
    }
    if ($new !== $confirm) {
        json_error('New passwords do not match');
    }
    
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || !password_verify((string)$current, $row['password_hash'])) {
        json_error('Current password is incorrect', 403);
    }
    
    $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $updateStmt->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
    
    json_success(['message' => 'Password changed successfully']);
}

function get_saved_destinations(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.country, c.image_url, c.cost_index
        FROM saved_destinations sd
        JOIN cities c ON c.id = sd.city_id
        WHERE sd.user_id = ?
        ORDER BY sd.saved_at DESC
    ');
    $stmt->execute([$userId]);
    json_success($stmt->fetchAll());
}

function toggle_saved_destination(PDO $pdo, int $userId): void {
    $body = get_request_body();
    $cityId = (int)($body['city_id'] ?? $_GET['city_id'] ?? 0);
    if (!$cityId) json_error('city_id is required');

    $check = $pdo->prepare('SELECT id FROM saved_destinations WHERE user_id = ? AND city_id = ?');
    $check->execute([$userId, $cityId]);
    $saved = $check->fetch();

    if ($saved) {
        $pdo->prepare('DELETE FROM saved_destinations WHERE id = ?')->execute([$saved['id']]);
        json_success(['saved' => false]);
    } else {
        $pdo->prepare('INSERT INTO saved_destinations (user_id, city_id) VALUES (?, ?)')->execute([$userId, $cityId]);
        json_success(['saved' => true]);
    }
}

function handle_delete(PDO $pdo, int $userId): void {
    $body = get_request_body();
    $email = clean_str($body['email'] ?? '');
    
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $userEmail = $stmt->fetchColumn();
    
    if (strtolower($email) !== strtolower((string)$userEmail)) {
        json_error('Email confirmation does not match');
    }
    
    $delStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $delStmt->execute([$userId]);
    logout_user();
    json_success(['deleted' => true]);
}
