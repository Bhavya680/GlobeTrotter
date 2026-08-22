<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$pdo = DB::getInstance();
$city_id = $_GET['city_id'] ?? null;
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';

if (!$city_id) {
    echo json_encode(['success' => false, 'message' => 'city_id is required']);
    exit;
}

try {
    $sql = "SELECT * FROM activities WHERE city_id = ?";
    $params = [$city_id];

    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    if ($search !== '') {
        $sql .= " AND name ILIKE ?";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
