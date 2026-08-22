<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
requireLogin();

$pdo = DB::getInstance();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $tripId = $_GET['id'] ?? null;
        if ($tripId) {
            $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
            $stmt->execute([$tripId, $userId]);
            $trip = $stmt->fetch();
            if ($trip) {
                $stopStmt = $pdo->prepare("SELECT ts.*, c.name as city_name, c.country FROM trip_stops ts JOIN cities c ON ts.city_id = c.id WHERE ts.trip_id = ? ORDER BY ts.order_index");
                $stopStmt->execute([$tripId]);
                $trip['stops'] = $stopStmt->fetchAll();
                echo json_encode(['success' => true, 'data' => $trip]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Trip not found']);
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY start_date DESC");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripName = $input['trip_name'] ?? $_POST['trip_name'] ?? '';
        $description = $input['description'] ?? $_POST['description'] ?? null;
        $startDate = $input['start_date'] ?? $_POST['start_date'] ?? null;
        $endDate = $input['end_date'] ?? $_POST['end_date'] ?? null;
        $visibility = $input['visibility'] ?? $_POST['visibility'] ?? 'private';
        
        if (empty($tripName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Trip name is required']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO trips (user_id, trip_name, description, start_date, end_date, visibility) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
        $stmt->execute([$userId, $tripName, $description, $startDate, $endDate, $visibility]);
        $newId = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'message' => 'Trip created', 'id' => $newId]);
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['id'] ?? $_GET['id'] ?? null;
        if ($tripId) {
            $stmt = $pdo->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?");
            $stmt->execute([$tripId, $userId]);
            echo json_encode(['success' => true, 'message' => 'Trip deleted']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Trip ID required']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
