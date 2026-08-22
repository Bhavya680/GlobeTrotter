<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = DB::getInstance();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle simulated DELETE via POST
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// Helper to verify trip ownership
function verifyTripOwnership($pdo, $trip_id, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
    $stmt->execute([$trip_id, $user_id]);
    return $stmt->fetch() !== false;
}

if ($method === 'GET') {
    $trip_id = $_GET['trip_id'] ?? null;
    if (!$trip_id || !verifyTripOwnership($pdo, $trip_id, $user_id)) {
        jsonResponse(['success' => false, 'message' => 'Invalid trip ID'], 403);
    }

    $stmt = $pdo->prepare("
        SELECT ts.*, c.name as city_name, c.country as city_country, c.image_url 
        FROM trip_stops ts
        JOIN cities c ON ts.city_id = c.id
        WHERE ts.trip_id = ? 
        ORDER BY ts.order_index ASC, ts.arrival_date ASC
    ");
    $stmt->execute([$trip_id]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
} 
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $trip_id = $input['trip_id'] ?? null;
    $city_id = $input['city_id'] ?? null;
    $arrival = !empty($input['arrival_date']) ? $input['arrival_date'] : null;
    $departure = !empty($input['departure_date']) ? $input['departure_date'] : null;
    $notes = sanitize($input['notes'] ?? '');

    if (!$trip_id || !$city_id || !verifyTripOwnership($pdo, $trip_id, $user_id)) {
        jsonResponse(['success' => false, 'message' => 'Invalid trip or city data'], 400);
    }

    // Get max order index
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(order_index), -1) FROM trip_stops WHERE trip_id = ?");
    $stmt->execute([$trip_id]);
    $next_order = $stmt->fetchColumn() + 1;

    $stmt = $pdo->prepare("INSERT INTO trip_stops (trip_id, city_id, arrival_date, departure_date, order_index, notes) VALUES (?, ?, ?, ?, ?, ?) RETURNING id");
    if ($stmt->execute([$trip_id, $city_id, $arrival, $departure, $next_order, $notes])) {
        jsonResponse(['success' => true, 'message' => 'Stop added', 'stop_id' => $stmt->fetchColumn()]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }
}
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $stop_id = $input['id'] ?? null;
    
    // Verify ownership of the stop via trip
    $stmt = $pdo->prepare("
        SELECT ts.id 
        FROM trip_stops ts 
        JOIN trips t ON ts.trip_id = t.id 
        WHERE ts.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$stop_id, $user_id]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Stop not found or access denied'], 404);
    }

    $stmt = $pdo->prepare("DELETE FROM trip_stops WHERE id = ?");
    $stmt->execute([$stop_id]);
    jsonResponse(['success' => true, 'message' => 'Stop removed']);
} else {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
