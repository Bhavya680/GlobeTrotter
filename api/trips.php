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

function get_list(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('
        SELECT
            t.id,
            COALESCE(t.trip_name, \'\') AS name,
            t.trip_name,
            t.start_date, t.end_date, t.description, t.cover_photo, t.visibility, t.share_slug,
            CASE
                WHEN t.end_date < CURRENT_DATE THEN \'completed\'
                WHEN t.start_date <= CURRENT_DATE AND t.end_date >= CURRENT_DATE THEN \'ongoing\'
                ELSE \'upcoming\'
            END AS status,
            COUNT(DISTINCT s.id) AS stop_count,
            COUNT(DISTINCT s.city_id) AS destination_count
        FROM trips t
        LEFT JOIN trip_stops s ON s.trip_id = t.id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.start_date DESC
    ');
    $stmt->execute([$userId]);
    json_success($stmt->fetchAll());
}

function get_one(PDO $pdo, int $userId, int $tripId): void
{
    $stmt = $pdo->prepare('
        SELECT t.id, t.user_id, COALESCE(t.trip_name, \'\') AS name, t.trip_name,
               t.start_date, t.end_date, t.description, t.cover_photo, t.visibility, t.share_slug,
               CASE
                   WHEN t.end_date < CURRENT_DATE THEN \'completed\'
                   WHEN t.start_date <= CURRENT_DATE AND t.end_date >= CURRENT_DATE THEN \'ongoing\'
                   ELSE \'upcoming\'
               END AS status
        FROM trips t
        WHERE t.id = ? AND t.user_id = ?
    ');
    $stmt->execute([$tripId, $userId]);
    $trip = $stmt->fetch();

    if (!$trip) {
        json_error('Trip not found', 404);
    }

    $stopsStmt = $pdo->prepare('
        SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
               s.transport_note, s.accommodation, s.accommodation_cost, s.budget_for_stop, s.notes AS stop_notes,
               c.name AS city_name, c.country AS city_country, c.image_url AS city_image
        FROM trip_stops s
        JOIN cities c ON c.id = s.city_id
        WHERE s.trip_id = ?
        ORDER BY s.order_index ASC, s.arrival_date ASC
    ');
    $stopsStmt->execute([$tripId]);
    $trip['stops'] = $stopsStmt->fetchAll();

    json_success($trip);
}

function create_trip(PDO $pdo, int $userId): void
{
    $body = !empty($_FILES) ? $_POST : get_request_body();

    $name = clean_str($body['name'] ?? $body['trip_name'] ?? '');
    if ($name === '') {
        json_error('Missing required field: name');
    }

    $startDate = clean_str($body['start_date'] ?? '');
    $endDate = clean_str($body['end_date'] ?? '');
    $description = clean_str($body['description'] ?? '');
    $visibility = in_array($body['visibility'] ?? '', ['public', 'private'], true) ? $body['visibility'] : 'private';

    if (!is_valid_date($startDate) || !is_valid_date($endDate)) {
        json_error('Dates must be in YYYY-MM-DD format');
    }
    if ($endDate < $startDate) {
        json_error('End date cannot be before start date');
    }

    $today = date('Y-m-d');
    $status = 'upcoming';
    if ($endDate < $today) {
        $status = 'completed';
    } elseif ($startDate <= $today && $endDate >= $today) {
        $status = 'ongoing';
    }

    $coverPhoto = null;
    try {
        if (!empty($_FILES['cover_photo']['name'])) {
            $coverPhoto = handle_image_upload('cover_photo', 'covers');
        }
    } catch (RuntimeException $e) {
        json_error($e->getMessage());
    }

    $shareSlug = ($visibility === 'public') ? generate_unique_slug($pdo) : null;

    $stmt = $pdo->prepare('
        INSERT INTO trips (user_id, trip_name, start_date, end_date, description, cover_photo, status, visibility, share_slug)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([$userId, $name, $startDate, $endDate, $description, $coverPhoto, $status, $visibility, $shareSlug]);
    $newId = (int) $stmt->fetchColumn();

    get_one($pdo, $userId, $newId);
}

function update_trip(PDO $pdo, int $userId, int $tripId): void
{
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }

    $body = !empty($_FILES) ? $_POST : get_request_body();
    $fields = [];
    $params = [];

    $name = isset($body['name']) ? clean_str($body['name']) : (isset($body['trip_name']) ? clean_str($body['trip_name']) : null);
    if ($name !== null) {
        $fields[] = 'trip_name = ?';
        $params[] = $name;
    }

    if (isset($body['description'])) {
        $fields[] = 'description = ?';
        $params[] = clean_str($body['description']);
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

    if (isset($body['visibility']) || isset($body['is_public'])) {
        $isPublic = isset($body['visibility']) ? ($body['visibility'] === 'public') : filter_var($body['is_public'], FILTER_VALIDATE_BOOLEAN);
        $fields[] = 'visibility = ?';
        $params[] = $isPublic ? 'public' : 'private';

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
        if (!empty($_FILES['cover_photo']['name'])) {
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

function delete_trip(PDO $pdo, int $userId, int $tripId): void
{
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }
    $stmt = $pdo->prepare('DELETE FROM trips WHERE id = ? AND user_id = ?');
    $stmt->execute([$tripId, $userId]);
    json_success(['deleted' => true]);
}
