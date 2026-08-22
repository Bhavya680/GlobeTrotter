<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$pdo   = DB::getInstance();
// Support both ?q= (legacy) and ?search= (autocomplete)
$query = $_GET['search'] ?? $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 50), 50);

try {
    if ($query !== '') {
        $stmt = $pdo->prepare(
            "SELECT id, name, country, region, popularity_score
             FROM cities
             WHERE name ILIKE ? OR country ILIKE ?
             ORDER BY popularity_score DESC
             LIMIT ?"
        );
        $stmt->execute(["%$query%", "%$query%", $limit]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, name, country, region, popularity_score
             FROM cities
             ORDER BY popularity_score DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
    }
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
