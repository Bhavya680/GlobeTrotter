<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$pdo = DB::getInstance();
$query = $_GET['q'] ?? '';

try {
    if ($query) {
        $stmt = $pdo->prepare("SELECT * FROM cities WHERE name ILIKE ? OR country ILIKE ? ORDER BY popularity_score DESC");
        $stmt->execute(["%$query%", "%$query%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM cities ORDER BY popularity_score DESC LIMIT 50");
    }
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
