<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($id) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.name, a.description, a.category, a.cost, a.duration_hours,
               a.image_url, a.city_id, c.name AS city_name, c.country AS city_country
        FROM activities a
        JOIN cities c ON c.id = a.city_id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    if (!$activity) {
        json_error('Activity not found', 404);
    }
    json_success($activity);
}

$cityId = isset($_GET['city_id']) && $_GET['city_id'] !== '' ? (int) $_GET['city_id'] : null;
$category = clean_str($_GET['category'] ?? '');
$q = clean_str($_GET['q'] ?? $_GET['search'] ?? '');
$costMin = isset($_GET['cost_min']) && $_GET['cost_min'] !== '' ? (float) $_GET['cost_min'] : null;
$costMax = isset($_GET['cost_max']) && $_GET['cost_max'] !== '' ? (float) $_GET['cost_max'] : (isset($_GET['max_cost']) && $_GET['max_cost'] !== '' ? (float) $_GET['max_cost'] : null);
$durationMin = isset($_GET['duration_min']) && $_GET['duration_min'] !== '' ? (float) $_GET['duration_min'] : null;
$durationMax = isset($_GET['duration_max']) && $_GET['duration_max'] !== '' ? (float) $_GET['duration_max'] : (isset($_GET['max_duration']) && $_GET['max_duration'] !== '' ? (float) $_GET['max_duration'] : null);
$sort = clean_str($_GET['sort'] ?? 'name');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;

$validCategories = ['sightseeing', 'food', 'adventure', 'culture', 'relaxation', 'other'];

$where = [];
$params = [];

if ($cityId) {
    $where[] = 'a.city_id = ?';
    $params[] = $cityId;
}
if ($category !== '' && in_array($category, $validCategories, true)) {
    $where[] = 'a.category = ?';
    $params[] = $category;
}
if ($q !== '') {
    $where[] = '(a.name ILIKE ? OR a.description ILIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
if ($costMin !== null) {
    $where[] = 'a.cost >= ?';
    $params[] = $costMin;
}
if ($costMax !== null) {
    $where[] = 'a.cost <= ?';
    $params[] = $costMax;
}
if ($durationMin !== null) {
    $where[] = 'a.duration_hours >= ?';
    $params[] = $durationMin;
}
if ($durationMax !== null) {
    $where[] = 'a.duration_hours <= ?';
    $params[] = $durationMax;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sortColumn = match ($sort) {
    'cost'       => 'a.cost ASC',
    'duration'   => 'a.duration_hours ASC',
    'name'       => 'a.name ASC',
    default      => 'a.name ASC',
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activities a {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "
    SELECT a.id, a.name, a.description, a.category, a.cost, a.duration_hours,
           a.image_url, a.city_id, c.name AS city_name, c.country AS city_country
    FROM activities a
    JOIN cities c ON c.id = a.city_id
    {$whereSql}
    ORDER BY {$sortColumn}
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([...$params, $perPage, $offset]);
$activities = $stmt->fetchAll();

json_success([
    'activities' => $activities,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / $perPage),
    ],
]);
