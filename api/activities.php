<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$pdo = DB::getInstance();
$cityId = $_GET['city_id'] ?? null;
$category = $_GET['category'] ?? null;

try {
    $sql = "SELECT * FROM activities WHERE 1=1";
    $params = [];
    
    if ($cityId) {
        $sql .= " AND city_id = ?";
        $params[] = $cityId;
    }
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
