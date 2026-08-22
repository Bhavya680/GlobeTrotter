<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];
$action = clean_str($_GET['action'] ?? '');

if ($action === 'activities') {
    route_stop_activities($pdo, $userId, $method);
} elseif ($action === 'reorder') {
    if ($method !== 'POST') json_error('Method not allowed', 405);
    reorder_stops($pdo, $userId);
} elseif ($action === 'with_activities') {
    if ($method !== 'GET') json_error('Method not allowed', 405);
    $tripId = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : null;
    $tripId ?: json_error('trip_id is required', 400);
    list_stops_with_activities($pdo, $userId, $tripId);
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
        SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
               s.transport_note, s.accommodation, s.budget_for_stop AS accommodation_cost, s.notes AS stop_notes,
               c.name AS city_name, c.country AS city_country
        FROM trip_stops s
        JOIN cities c ON c.id = s.city_id
        WHERE s.trip_id = ?
        ORDER BY s.order_index ASC, s.arrival_date ASC
    ');
    $stmt->execute([$tripId]);
    json_success($stmt->fetchAll());
}

function list_stops_with_activities(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }

    $stopsStmt = $pdo->prepare('
        SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
               s.transport_note, s.accommodation, s.budget_for_stop AS accommodation_cost, s.notes AS stop_notes,
               c.name AS city_name, c.country AS city_country
        FROM trip_stops s
        JOIN cities c ON c.id = s.city_id
        WHERE s.trip_id = ?
        ORDER BY s.order_index ASC, s.arrival_date ASC
    ');
    $stopsStmt->execute([$tripId]);
    $stops = $stopsStmt->fetchAll();

    if (!$stops) {
        json_success([]);
        return;
    }

    $stopIds = array_column($stops, 'id');
    $placeholders = implode(',', array_fill(0, count($stopIds), '?'));

    $actStmt = $pdo->prepare("
        SELECT sa.id, sa.trip_stop_id AS stop_id, sa.activity_id, sa.scheduled_date, sa.scheduled_time,
               sa.custom_cost AS cost_override, sa.notes,
               a.name, a.category, a.duration_hours,
               COALESCE(sa.custom_cost, a.cost) AS effective_cost
        FROM trip_activities sa
        JOIN activities a ON a.id = sa.activity_id
        WHERE sa.trip_stop_id IN ({$placeholders})
        ORDER BY sa.scheduled_date ASC, sa.scheduled_time ASC NULLS LAST
    ");
    $actStmt->execute($stopIds);
    $allActivities = $actStmt->fetchAll();

    $actsByStop = [];
    foreach ($allActivities as $act) {
        $actsByStop[$act['stop_id']][] = $act;
    }

    foreach ($stops as &$stop) {
        $stop['activities'] = $actsByStop[$stop['id']] ?? [];
    }

    json_success($stops);
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

    $cityId    = (int) $body['city_id'];
    $startDate = clean_str($body['start_date']);
    $endDate   = clean_str($body['end_date']);

    if (!is_valid_date($startDate) || !is_valid_date($endDate)) {
        json_error('Dates must be in YYYY-MM-DD format');
    }
    if ($endDate < $startDate) {
        json_error('End date cannot be before start date');
    }

    $tripStmt = $pdo->prepare('SELECT start_date, end_date FROM trips WHERE id = ?');
    $tripStmt->execute([$tripId]);
    $trip = $tripStmt->fetch();
    if ($startDate < $trip['start_date'] || $endDate > $trip['end_date']) {
        json_error('Stop dates must be within trip range (' . $trip['start_date'] . ' – ' . $trip['end_date'] . ')');
    }

    $cityCheck = $pdo->prepare('SELECT 1 FROM cities WHERE id = ?');
    $cityCheck->execute([$cityId]);
    if (!$cityCheck->fetch()) json_error('City not found', 404);

    $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(order_index), -1) + 1 FROM trip_stops WHERE trip_id = ?');
    $orderStmt->execute([$tripId]);
    $nextOrder = (int) $orderStmt->fetchColumn();

    $sortOrder = isset($body['sort_order']) ? (int) $body['sort_order'] : $nextOrder;

    $stmt = $pdo->prepare('
        INSERT INTO trip_stops (trip_id, city_id, arrival_date, departure_date, order_index)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([$tripId, $cityId, $startDate, $endDate, $sortOrder]);
    $newId = (int) $stmt->fetchColumn();

    $fetchStmt = $pdo->prepare('
        SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
               s.transport_note, s.accommodation, s.budget_for_stop AS accommodation_cost, s.notes AS stop_notes,
               c.name AS city_name, c.country AS city_country
        FROM trip_stops s JOIN cities c ON c.id = s.city_id
        WHERE s.id = ?
    ');
    $fetchStmt->execute([$newId]);
    json_success($fetchStmt->fetch(), 201);
}

function update_stop(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) json_error('Stop not found', 404);

    $body   = get_request_body();
    $fields = [];
    $params = [];

    if (isset($body['start_date'])) {
        $v = clean_str($body['start_date']);
        if (!is_valid_date($v)) json_error('Invalid start_date');
        $fields[] = 'arrival_date = ?';
        $params[] = $v;
    }
    if (isset($body['end_date'])) {
        $v = clean_str($body['end_date']);
        if (!is_valid_date($v)) json_error('Invalid end_date');
        $fields[] = 'departure_date = ?';
        $params[] = $v;
    }
    if (isset($body['sort_order'])) {
        $fields[] = 'order_index = ?';
        $params[] = (int) $body['sort_order'];
    }
    if (isset($body['city_id'])) {
        $fields[] = 'city_id = ?';
        $params[] = (int) $body['city_id'];
    }
    if (array_key_exists('transport_note', $body)) {
        $fields[] = 'transport_note = ?';
        $params[] = clean_str($body['transport_note']);
    }
    if (array_key_exists('accommodation', $body)) {
        $fields[] = 'accommodation = ?';
        $params[] = clean_str($body['accommodation']);
    }
    if (array_key_exists('accommodation_cost', $body)) {
        $v = $body['accommodation_cost'];
        $fields[] = 'budget_for_stop = ?';
        $params[] = ($v === '' || $v === null) ? null : (float) $v;
    }
    if (array_key_exists('stop_notes', $body)) {
        $fields[] = 'notes = ?';
        $params[] = clean_str($body['stop_notes']);
    }

    if (!$fields) json_error('No valid fields to update');

    $params[] = $stopId;
    $sql = 'UPDATE trip_stops SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute($params);

    json_success(['updated' => true]);
}

function reorder_stops(PDO $pdo, int $userId): void {
    $body = get_request_body();
    if (!isset($body['stops']) || !is_array($body['stops'])) json_error('stops array is required');

    $pdo->beginTransaction();
    try {
        foreach ($body['stops'] as $item) {
            $stopId    = isset($item['id']) ? (int) $item['id'] : null;
            $sortOrder = isset($item['sort_order']) ? (int) $item['sort_order'] : null;
            if ($stopId === null || $sortOrder === null) continue;

            if (!user_owns_stop($pdo, $userId, $stopId)) {
                $pdo->rollBack();
                json_error('Stop not found or access denied', 403);
            }

            $pdo->prepare('UPDATE trip_stops SET order_index = ? WHERE id = ?')->execute([$sortOrder, $stopId]);
        }
        $pdo->commit();
        json_success(['reordered' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_error('Reorder failed: ' . $e->getMessage(), 500);
    }
}

function delete_stop(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) json_error('Stop not found', 404);
    $pdo->prepare('DELETE FROM trip_stops WHERE id = ?')->execute([$stopId]);
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
    if (!user_owns_stop($pdo, $userId, $stopId)) json_error('Stop not found', 404);
    $stmt = $pdo->prepare('
        SELECT sa.id, sa.activity_id, sa.scheduled_date, sa.scheduled_time,
               sa.custom_cost AS cost_override, sa.notes,
               a.name, a.category, a.duration_hours,
               COALESCE(sa.custom_cost, a.cost) AS effective_cost
        FROM trip_activities sa
        JOIN activities a ON a.id = sa.activity_id
        WHERE sa.trip_stop_id = ?
        ORDER BY sa.scheduled_date ASC, sa.scheduled_time ASC NULLS LAST
    ');
    $stmt->execute([$stopId]);
    json_success($stmt->fetchAll());
}

function add_stop_activity(PDO $pdo, int $userId, int $stopId): void {
    if (!user_owns_stop($pdo, $userId, $stopId)) json_error('Stop not found', 404);

    $body = get_request_body();

    if (isset($body['activity_ids']) && is_array($body['activity_ids'])) {
        $scheduledDate = clean_str($body['scheduled_date'] ?? '');
        if (!$scheduledDate || !is_valid_date($scheduledDate)) json_error('scheduled_date must be YYYY-MM-DD');

        $inserted = [];
        foreach ($body['activity_ids'] as $actId) {
            $actId = (int) $actId;
            $actCheck = $pdo->prepare('SELECT 1 FROM activities WHERE id = ?');
            $actCheck->execute([$actId]);
            if (!$actCheck->fetch()) continue;

            $stmt = $pdo->prepare('
                INSERT INTO trip_activities (trip_stop_id, activity_id, scheduled_date)
                VALUES (?, ?, ?)
                RETURNING id
            ');
            $stmt->execute([$stopId, $actId, $scheduledDate]);
            $inserted[] = (int) $stmt->fetchColumn();
        }

        if ($inserted) {
            $placeholders = implode(',', array_fill(0, count($inserted), '?'));
            $detailStmt = $pdo->prepare("
                SELECT sa.id, sa.trip_stop_id AS stop_id, sa.activity_id, sa.scheduled_date, sa.scheduled_time,
                       sa.custom_cost AS cost_override, sa.notes,
                       a.name, a.category, a.duration_hours,
                       COALESCE(sa.custom_cost, a.cost) AS effective_cost
                FROM trip_activities sa
                JOIN activities a ON a.id = sa.activity_id
                WHERE sa.id IN ({$placeholders})
                ORDER BY sa.scheduled_date ASC
            ");
            $detailStmt->execute($inserted);
            json_success($detailStmt->fetchAll(), 201);
        }
        json_success([], 201);
        return;
    }

    $missing = missing_fields($body, ['activity_id', 'scheduled_date']);
    if ($missing) json_error('Missing required field(s): ' . implode(', ', $missing));

    if (!is_valid_date(clean_str($body['scheduled_date']))) json_error('scheduled_date must be YYYY-MM-DD');

    $activityCheck = $pdo->prepare('SELECT 1 FROM activities WHERE id = ?');
    $activityCheck->execute([(int) $body['activity_id']]);
    if (!$activityCheck->fetch()) json_error('Activity not found', 404);

    $stmt = $pdo->prepare('
        INSERT INTO trip_activities (trip_stop_id, activity_id, scheduled_date, scheduled_time, custom_cost, notes)
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
        SELECT sa.id FROM trip_activities sa
        JOIN trip_stops s ON s.id = sa.trip_stop_id
        JOIN trips t ON t.id = s.trip_id
        WHERE sa.id = ? AND t.user_id = ?
    ');
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) json_error('Not found', 404);
    $pdo->prepare('DELETE FROM trip_activities WHERE id = ?')->execute([$id]);
    json_success(['deleted' => true]);
}
