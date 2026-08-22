<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get($pdo, $userId);
        break;
    case 'PUT':
    case 'POST':
        handle_update($pdo, $userId);
        break;
    case 'DELETE':
        handle_delete($pdo, $userId);
        break;
    default:
        json_error('Method not allowed', 405);
}

function handle_get(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('
        SELECT id, name, email, profile_photo, language_pref, is_admin, created_at
        FROM users WHERE id = ?
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_error('User not found', 404);
    }

    json_success($user);
}

function handle_update(PDO $pdo, int $userId): void {
    $body = !empty($_FILES) ? $_POST : get_request_body();

    $fields = [];
    $params = [];

    if (isset($body['name']) && trim($body['name']) !== '') {
        $fields[] = 'name = ?';
        $params[] = clean_str($body['name']);
    }

    if (isset($body['language_pref']) && trim($body['language_pref']) !== '') {
        $fields[] = 'language_pref = ?';
        $params[] = clean_str($body['language_pref']);
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

    if (isset($body['new_password']) && $body['new_password'] !== '') {
        if (strlen($body['new_password']) < 8) {
            json_error('New password must be at least 8 characters');
        }
        $current = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $current->execute([$userId]);
        $row = $current->fetch();
        if (!$row || !password_verify((string) ($body['current_password'] ?? ''), $row['password_hash'])) {
            json_error('Current password is incorrect', 403);
        }
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($body['new_password'], PASSWORD_DEFAULT);
    }

    try {
        if (!empty($_FILES['profile_photo'])) {
            $photoUrl = handle_image_upload('profile_photo', 'profiles');
            if ($photoUrl !== null) {
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

function handle_delete(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    logout_user();
    json_success(['deleted' => true]);
}
