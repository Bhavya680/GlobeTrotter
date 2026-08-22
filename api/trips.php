<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];
$tripId = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        $tripId ? get_one($pdo, $userId, $tripId) : get_list($pdo, $userId);
        break;
    case 'POST':
        create_trip($pdo, $userId);
        break;
    case 'PUT':
        $tripId ? update_trip($pdo, $userId, $tripId) : json_error('Trip id required', 400);
        break;
    case 'DELETE':
        $tripId ? delete_trip($pdo, $userId, $tripId) : json_error('Trip id required', 400);
        break;
    default:
        json_error('Method not allowed', 405);
}

function get_list(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('
        SELECT
            t.id, t.name, t.start_date, t.end_date, t.description, t.cover_photo,
            t.is_public, t.share_slug,
            COUNT(DISTINCT s.id) AS stop_count,
            COUNT(DISTINCT s.city_id) AS destination_count
        FROM trips t
        LEFT JOIN stops s ON s.trip_id = t.id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.start_date DESC
    ');
    $stmt->execute([$userId]);
    json_success($stmt->fetchAll());
}

function get_one(PDO $pdo, int $userId, int $tripId): void {
    $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ?');
    $stmt->execute([$tripId, $userId]);
    $trip = $stmt->fetch();

    if (!$trip) {
        json_error('Trip not found', 404);
    }

    $stopsStmt = $pdo->prepare('
        SELECT s.id, s.city_id, s.start_date, s.end_date, s.sort_order,
               c.name AS city_name, c.country AS city_country, c.image_url AS city_image
        FROM stops s
        JOIN cities c ON c.id = s.city_id
        WHERE s.trip_id = ?
        ORDER BY s.sort_order ASC, s.start_date ASC
    ');
    $stopsStmt->execute([$tripId]);
    $trip['stops'] = $stopsStmt->fetchAll();

    json_success($trip);
}

function create_trip(PDO $pdo, int $userId): void {
    $body = !empty($_FILES) ? $_POST : get_request_body();

    $missing = missing_fields($body, ['name', 'start_date', 'end_date']);
    if ($missing) {
        json_error('Missing required field(s): ' . implode(', ', $missing));
    }

    $name = clean_str($body['name']);
    $startDate = clean_str($body['start_date']);
    $endDate = clean_str($body['end_date']);
    $description = clean_str($body['description'] ?? '');

    if (!is_valid_date($startDate) || !is_valid_date($endDate)) {
        json_error('Dates must be in YYYY-MM-DD format');
    }
    if ($endDate < $startDate) {
        json_error('End date cannot be before start date');
    }

    $coverPhoto = null;
    try {
        $coverPhoto = handle_image_upload('cover_photo', 'covers');
    } catch (RuntimeException $e) {
        json_error($e->getMessage());
    }

    $stmt = $pdo->prepare('
        INSERT INTO trips (user_id, name, start_date, end_date, description, cover_photo)
        VALUES (?, ?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([$userId, $name, $startDate, $endDate, $description, $coverPhoto]);
    $newId = (int) $stmt->fetchColumn();

    get_one($pdo, $userId, $newId);
}

function update_trip(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }

    $body = !empty($_FILES) ? $_POST : get_request_body();
    $fields = [];
    $params = [];

    foreach (['name', 'description'] as $key) {
        if (isset($body[$key])) {
            $fields[] = "{$key} = ?";
            $params[] = clean_str($body[$key]);
        }
    }

    $startDate = isset($body['start_date']) ? clean_str($body['start_date']) : null;
    $endDate = isset($body['end_date']) ? clean_str($body['end_date']) : null;

    if ($startDate !== null) {
        if (!is_valid_date($startDate)) json_error('Invalid start_date');
        $fields[] = 'start_date = ?';
        $params[] = $startDate;
    }
    if ($endDate !== null) {
        if (!is_valid_date($endDate)) json_error('Invalid end_date');
        $fields[] = 'end_date = ?';
        $params[] = $endDate;
    }
    if (($startDate !== null) xor ($endDate !== null)) {
        $existing = $pdo->prepare('SELECT start_date, end_date FROM trips WHERE id = ?');
        $existing->execute([$tripId]);
        $row = $existing->fetch();
        $effectiveStart = $startDate ?? $row['start_date'];
        $effectiveEnd = $endDate ?? $row['end_date'];
        if ($effectiveEnd < $effectiveStart) {
            json_error('End date cannot be before start date');
        }
    }

    if (isset($body['is_public'])) {
        $isPublic = filter_var($body['is_public'], FILTER_VALIDATE_BOOLEAN);
        $fields[] = 'is_public = ?';
        $params[] = $isPublic;

        if ($isPublic) {
            $current = $pdo->prepare('SELECT share_slug FROM trips WHERE id = ?');
            $current->execute([$tripId]);
            if (!$current->fetchColumn()) {
                $fields[] = 'share_slug = ?';
                $params[] = generate_unique_slug($pdo);
            }
        }
    }

    try {
        if (!empty($_FILES['cover_photo'])) {
            $coverPhoto = handle_image_upload('cover_photo', 'covers');
            if ($coverPhoto !== null) {
                $fields[] = 'cover_photo = ?';
                $params[] = $coverPhoto;
            }
        }
    } catch (RuntimeException $e) {
        json_error($e->getMessage());
    }

    if (!$fields) {
        json_error('No valid fields to update');
    }

    $params[] = $tripId;
    $params[] = $userId;
    $sql = 'UPDATE trips SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?';
    $pdo->prepare($sql)->execute($params);

    get_one($pdo, $userId, $tripId);
}

function delete_trip(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }
    $stmt = $pdo->prepare('DELETE FROM trips WHERE id = ? AND user_id = ?');
    $stmt->execute([$tripId, $userId]);
    json_success(['deleted' => true]);
}
