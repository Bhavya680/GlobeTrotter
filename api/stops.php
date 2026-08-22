<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
requireLogin();

$pdo = DB::getInstance();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? $_POST['trip_id'] ?? null;
        $cityId = $input['city_id'] ?? $_POST['city_id'] ?? null;
        $arrivalDate = $input['arrival_date'] ?? $_POST['arrival_date'] ?? null;
        $departureDate = $input['departure_date'] ?? $_POST['departure_date'] ?? null;
        
        if (!$tripId || !$cityId) {
            http_response_code(400);
            echo json_encode(['error' => 'trip_id and city_id are required']);
            exit;
        }
        
        $check = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $check->execute([$tripId, $userId]);
        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO trip_stops (trip_id, city_id, arrival_date, departure_date) VALUES (?, ?, ?, ?) RETURNING id");
        $stmt->execute([$tripId, $cityId, $arrivalDate, $departureDate]);
        
        echo json_encode(['success' => true, 'id' => $stmt->fetchColumn()]);
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stopId = $input['id'] ?? $_GET['id'] ?? null;
        
        if ($stopId) {
            $stmt = $pdo->prepare("DELETE FROM trip_stops WHERE id = ? AND trip_id IN (SELECT id FROM trips WHERE user_id = ?)");
            $stmt->execute([$stopId, $userId]);
            echo json_encode(['success' => true, 'message' => 'Stop deleted']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Stop ID required']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
