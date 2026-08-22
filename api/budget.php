<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $tripId = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : null;
        $tripId ?: json_error('trip_id is required', 400);
        get_breakdown($pdo, $userId, $tripId);
        break;
    case 'POST':
        $tripId = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : null;
        $tripId ?: json_error('trip_id is required', 400);
        add_budget_item($pdo, $userId, $tripId);
        break;
    case 'DELETE':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $id ?: json_error('id is required', 400);
        delete_budget_item($pdo, $userId, $id);
        break;
    default:
        json_error('Method not allowed', 405);
}

function get_breakdown(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }

    $trip = $pdo->prepare('SELECT start_date, end_date FROM trips WHERE id = ?');
    $trip->execute([$tripId]);
    $tripRow = $trip->fetch();

    $itemsStmt = $pdo->prepare('
        SELECT category, COALESCE(SUM(amount), 0) AS total
        FROM budget_items
        WHERE trip_id = ?
        GROUP BY category
    ');
    $itemsStmt->execute([$tripId]);
    $byCategory = ['transport' => 0, 'stay' => 0, 'meals' => 0, 'other' => 0];
    foreach ($itemsStmt->fetchAll() as $row) {
        $byCategory[$row['category']] = (float) $row['total'];
    }

    $activityStmt = $pdo->prepare('
        SELECT COALESCE(SUM(COALESCE(sa.custom_cost, a.cost)), 0) AS total
        FROM trip_activities sa
        JOIN activities a ON a.id = sa.activity_id
        JOIN trip_stops s ON s.id = sa.trip_stop_id
        WHERE s.trip_id = ?
    ');
    $activityStmt->execute([$tripId]);
    $byCategory['activities'] = (float) $activityStmt->fetchColumn();

    $total = array_sum($byCategory);

    $perDayStmt = $pdo->prepare('
        SELECT day, SUM(amount) AS total FROM (
            SELECT sa.scheduled_date AS day, COALESCE(sa.custom_cost, a.cost) AS amount
            FROM trip_activities sa
            JOIN activities a ON a.id = sa.activity_id
            JOIN trip_stops s ON s.id = sa.trip_stop_id
            WHERE s.trip_id = ?
            UNION ALL
            SELECT spent_on AS day, amount
            FROM budget_items
            WHERE trip_id = ? AND spent_on IS NOT NULL
        ) combined
        GROUP BY day
        ORDER BY day
    ');
    $perDayStmt->execute([$tripId, $tripId]);
    $perDay = $perDayStmt->fetchAll();

    $tripDays = max(1, (strtotime($tripRow['end_date']) - strtotime($tripRow['start_date'])) / 86400 + 1);
    $averagePerDay = round($total / $tripDays, 2);

    $overBudgetDays = array_values(array_filter($perDay, fn($d) => (float) $d['total'] > $averagePerDay * 1.25));

    json_success([
        'by_category' => $byCategory,
        'total' => round($total, 2),
        'trip_days' => (int) $tripDays,
        'average_per_day' => $averagePerDay,
        'per_day' => $perDay,
        'over_budget_days' => $overBudgetDays,
    ]);
}

function add_budget_item(PDO $pdo, int $userId, int $tripId): void {
    if (!user_owns_trip($pdo, $userId, $tripId)) {
        json_error('Trip not found', 404);
    }

    $body = get_request_body();
    $missing = missing_fields($body, ['category', 'amount']);
    if ($missing) {
        json_error('Missing required field(s): ' . implode(', ', $missing));
    }

    $category = clean_str($body['category']);
    if (!in_array($category, ['transport', 'stay', 'meals', 'other'], true)) {
        json_error('category must be one of: transport, stay, meals, other');
    }

    $amount = (float) $body['amount'];
    if ($amount < 0) {
        json_error('amount cannot be negative');
    }

    $stopId = null;
    if (!empty($body['stop_id'])) {
        $stopId = (int) $body['stop_id'];
        if (!user_owns_stop($pdo, $userId, $stopId)) {
            json_error('Stop not found', 404);
        }
    }

    $spentOn = null;
    if (!empty($body['spent_on'])) {
        $spentOn = clean_str($body['spent_on']);
        if (!is_valid_date($spentOn)) {
            json_error('spent_on must be YYYY-MM-DD');
        }
    }

    $stmt = $pdo->prepare('
        INSERT INTO budget_items (trip_id, stop_id, category, description, amount, spent_on)
        VALUES (?, ?, ?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([
        $tripId,
        $stopId,
        $category,
        isset($body['description']) ? clean_str($body['description']) : null,
        $amount,
        $spentOn,
    ]);

    json_success(['id' => (int) $stmt->fetchColumn()], 201);
}

function delete_budget_item(PDO $pdo, int $userId, int $id): void {
    $stmt = $pdo->prepare('
        SELECT b.id FROM budget_items b
        JOIN trips t ON t.id = b.trip_id
        WHERE b.id = ? AND t.user_id = ?
    ');
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        json_error('Not found', 404);
    }
    $pdo->prepare('DELETE FROM budget_items WHERE id = ?')->execute([$id]);
    json_success(['deleted' => true]);
}
