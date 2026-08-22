<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($id) {
    $stmt = $pdo->prepare("SELECT id, name, country, region, cost_index, popularity_score, description, image_url FROM cities WHERE id = ?");
    $stmt->execute([$id]);
    $city = $stmt->fetch();
    if (!$city) {
        json_error('City not found', 404);
    }
    
    // Fetch activities for this city
    $actStmt = $pdo->prepare("SELECT id, name, description, category, cost, duration_hours, image_url FROM activities WHERE city_id = ? ORDER BY name ASC");
    $actStmt->execute([$id]);
    $city['activities'] = $actStmt->fetchAll();
    
    json_success($city);
}

$q = clean_str($_GET['q'] ?? $_GET['search'] ?? '');
$country = clean_str($_GET['country'] ?? '');
$region = clean_str($_GET['region'] ?? '');
$costMin = isset($_GET['cost_min']) && $_GET['cost_min'] !== '' ? (float) $_GET['cost_min'] : null;
$costMax = isset($_GET['cost_max']) && $_GET['cost_max'] !== '' ? (float) $_GET['cost_max'] : null;
$sort = clean_str($_GET['sort'] ?? 'popularity');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? $_GET['limit'] ?? 20)));
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(name ILIKE ? OR country ILIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
if ($country !== '') {
    $where[] = 'country = ?';
    $params[] = $country;
}
if ($region !== '') {
    $where[] = 'region = ?';
    $params[] = $region;
}
if ($costMin !== null) {
    $where[] = 'cost_index >= ?';
    $params[] = $costMin;
}
if ($costMax !== null) {
    $where[] = 'cost_index <= ?';
    $params[] = $costMax;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sortColumn = match ($sort) {
    'name'       => 'name ASC',
    'cost_low'   => 'cost_index ASC',
    'cost_high'  => 'cost_index DESC',
    'cost_index' => 'cost_index ASC',
    default      => 'popularity_score DESC',
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM cities {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "
    SELECT id, name, country, region, cost_index, popularity_score AS popularity, description, image_url
    FROM cities
    {$whereSql}
    ORDER BY {$sortColumn}
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([...$params, $perPage, $offset]);
$cities = $stmt->fetchAll();

json_success([
    'cities' => $cities,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int) ceil($total / $perPage),
    ],
]);
