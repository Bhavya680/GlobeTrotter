<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$cityId = isset($_GET['city_id']) ? (int) $_GET['city_id'] : null;
$category = clean_str($_GET['category'] ?? '');
$q = clean_str($_GET['q'] ?? '');
$maxCost = isset($_GET['max_cost']) ? (float) $_GET['max_cost'] : null;
$maxDuration = isset($_GET['max_duration']) ? (float) $_GET['max_duration'] : null;
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
if ($maxCost !== null) {
    $where[] = 'a.cost <= ?';
    $params[] = $maxCost;
}
if ($maxDuration !== null) {
    $where[] = 'a.duration_hours <= ?';
    $params[] = $maxDuration;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activities a {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "
    SELECT a.id, a.name, a.description, a.category, a.cost, a.duration_hours,
           a.image_url, a.city_id, c.name AS city_name, c.country AS city_country
    FROM activities a
    JOIN cities c ON c.id = a.city_id
    {$whereSql}
    ORDER BY a.name ASC
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
