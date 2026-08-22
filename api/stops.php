<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];
$action = clean_str($_GET['action'] ?? '');

if ($action === 'activities') {
    route_stop_activities($pdo, $userId, $method);
} else {
    route_stops($pdo, $userId, $method);
}

function route_stops(PDO $pdo, int $userId, string $method): void {
    $stopId = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $tripId = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : null;

    switch ($method) {
        case 'GET':
            $tripId ?: json_error('trip_id is required', 400);
            list_stops($pdo, $userId, $tripId);
            break;
        case 'POST':
            $tripId ?: json_error('trip_id is required', 400);
            add_stop($pdo, $userId, $tripId);
            break;
        case 'PUT':
            $stopId ?: json_error('Stop id required', 400);
            update_stop($pdo, $userId, $stopId);
            break;
        case 'DELETE':
            $stopId ?: json_error('Stop id required', 400);
            delete_stop($pdo, $userId, $stopId);
            break;
        default:
            json_error('Method not allowed', 405);
    }
}

function list_stops(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }
    $stmt = $pdo->prepare('
        SELECT s.id, s.city_id, s.start_date, s.end_date, s.sort_order,
               c.name AS city_name, c.country AS city_country
        FROM stops s
        JOIN cities c ON c.id = s.city_id
        WHERE s.trip_id = ?
        ORDER BY s.sort_order ASC, s.start_date ASC
    ');
    $stmt->execute([$tripId]);
    json_success($stmt->fetchAll());
}

function add_stop(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }

    $body = get_request_body();
    $missing = missing_fields($body, ['city_id', 'start_date', 'end_date']);
    if ($missing) {
        json_error('Missing required field(s): ' . implode(', ', $missing));
    }

    $cityId = (int) $body['city_id'];
    $startDate = clean_str($body['start_date']);
    $endDate = clean_str($body['end_date']);

    if (!is_valid_date($startDate) || !is_valid_date($endDate)) {
        json_error('Dates must be in YYYY-MM-DD format');
    }
    if ($endDate < $startDate) {
        json_error('End date cannot be before start date');
    }

    $cityCheck = $pdo->prepare('SELECT 1 FROM cities WHERE id = ?');
    $cityCheck->execute([$cityId]);
    if (!$cityCheck->fetch()) {
        json_error('City not found', 404);
    }

    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM stops WHERE trip_id = ?');
    $orderStmt->execute([$tripId]);
    $nextOrder = (int) $orderStmt->fetchColumn();

    $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : $nextOrder;

    $stmt = $pdo->prepare('
        INSERT INTO stops (trip_id, city_id, start_date, end_date, sort_order)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([$tripId, $cityId, $startDate, $endDate, $sortOrder]);
    $newId = (int) $stmt->fetchColumn();

    json_success(['id' => $newId], 201);
}

function update_stop(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) {
        json_error('Stop not found', 404);
    }

    $body = get_request_body();
    $fields = [];
    $params = [];

    if (isset($body['start_date'])) {
        $v = clean_str($body['start_date']);
        if (!is_valid_date($v)) json_error('Invalid start_date');
        $fields[] = 'start_date = ?';
        $params[] = $v;
    }
    if (isset($body['end_date'])) {
        $v = clean_str($body['end_date']);
        if (!is_valid_date($v)) json_error('Invalid end_date');
        $fields[] = 'end_date = ?';
        $params[] = $v;
    }
    if (isset($body['sort_order'])) {
        $fields[] = 'sort_order = ?';
        $params[] = (int) $body['sort_order'];
    }
    if (isset($body['city_id'])) {
        $fields[] = 'city_id = ?';
        $params[] = (int) $body['city_id'];
    }

    if (!$fields) {
        json_error('No valid fields to update');
    }

    $params[] = $stopId;
    $sql = 'UPDATE stops SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute($params);

    json_success(['updated' => true]);
}

function delete_stop(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) {
        json_error('Stop not found', 404);
    }
    $pdo->prepare('DELETE FROM stops WHERE id = ?')->execute([$stopId]);
    json_success(['deleted' => true]);
}

function route_stop_activities(PDO $pdo, int $userId, string $method): void {
    switch ($method) {
        case 'GET':
            $stopId = isset($_GET['stop_id']) ? (int) $_GET['stop_id'] : null;
            $stopId ?: json_error('stop_id is required', 400);
            list_stop_activities($pdo, $userId, $stopId);
            break;
        case 'POST':
            $stopId = isset($_GET['stop_id']) ? (int) $_GET['stop_id'] : null;
            $stopId ?: json_error('stop_id is required', 400);
            add_stop_activity($pdo, $userId, $stopId);
            break;
        case 'DELETE':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $id ?: json_error('id is required', 400);
            delete_stop_activity($pdo, $userId, $id);
            break;
        default:
            json_error('Method not allowed', 405);
    }
}

function list_stop_activities(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) {
        json_error('Stop not found', 404);
    }
    $stmt = $pdo->prepare('
        SELECT sa.id, sa.activity_id, sa.scheduled_date, sa.scheduled_time,
               sa.cost_override, sa.notes,
               a.name, a.category, a.duration_hours,
               COALESCE(sa.cost_override, a.cost) AS effective_cost
        FROM stop_activities sa
        JOIN activities a ON a.id = sa.activity_id
        WHERE sa.stop_id = ?
        ORDER BY sa.scheduled_date ASC, sa.scheduled_time ASC NULLS LAST
    ');
    $stmt->execute([$stopId]);
    json_success($stmt->fetchAll());
}

function add_stop_activity(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) {
        json_error('Stop not found', 404);
    }

    $body = get_request_body();
    $missing = missing_fields($body, ['activity_id', 'scheduled_date']);
    if ($missing) {
        json_error('Missing required field(s): ' . implode(', ', $missing));
    }

    if (!is_valid_date(clean_str($body['scheduled_date']))) {
        json_error('scheduled_date must be YYYY-MM-DD');
    }

    $activityCheck = $pdo->prepare('SELECT 1 FROM activities WHERE id = ?');
    $activityCheck->execute([(int) $body['activity_id']]);
    if (!$activityCheck->fetch()) {
        json_error('Activity not found', 404);
    }

    $stmt = $pdo->prepare('
        INSERT INTO stop_activities (stop_id, activity_id, scheduled_date, scheduled_time, cost_override, notes)
        VALUES (?, ?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([
        $stopId,
        (int) $body['activity_id'],
        clean_str($body['scheduled_date']),
        $body['scheduled_time'] ?? null,
        isset($body['cost_override']) ? (float) $body['cost_override'] : null,
        isset($body['notes']) ? clean_str($body['notes']) : null,
    ]);

    json_success(['id' => (int) $stmt->fetchColumn()], 201);
}

function delete_stop_activity(PDO $pdo, int $userId, int $id): void {
    $stmt = $pdo->prepare('
        SELECT sa.id FROM stop_activities sa
        JOIN stops s ON s.id = sa.stop_id
        JOIN trips t ON t.id = s.trip_id
        WHERE sa.id = ? AND t.user_id = ?
    ');
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        json_error('Not found', 404);
    }
    $pdo->prepare('DELETE FROM stop_activities WHERE id = ?')->execute([$id]);
    json_success(['deleted' => true]);
}
