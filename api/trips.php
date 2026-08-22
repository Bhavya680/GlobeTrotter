<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = DB::getInstance();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle simulated PUT/DELETE via POST
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

if ($method === 'GET') {
    $trip_id = $_GET['id'] ?? null;
    if ($trip_id) {
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $user_id]);
        $trip = $stmt->fetch();
        if ($trip) {
            jsonResponse(['success' => true, 'data' => $trip]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Trip not found'], 404);
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY start_date ASC");
        $stmt->execute([$user_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} 
elseif ($method === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $trip_name = sanitize($_POST['trip_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $visibility = $_POST['visibility'] ?? 'private';

    if (empty($trip_name)) {
        jsonResponse(['success' => false, 'message' => 'Trip name is required']);
    }

    $cover_photo = null;
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $cover_photo = uploadFile($_FILES['cover_photo'], 'trips');
    }

    $stmt = $pdo->prepare("INSERT INTO trips (user_id, trip_name, description, start_date, end_date, visibility, cover_photo) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id");
    if ($stmt->execute([$user_id, $trip_name, $description, $start_date, $end_date, $visibility, $cover_photo])) {
        $new_id = $stmt->fetchColumn();
        jsonResponse(['success' => true, 'message' => 'Trip created', 'trip_id' => $new_id]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Database error'], 500);
    }
}
elseif ($method === 'PUT') {
    // Read raw data if not using simulated PUT
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $trip_id = $input['id'] ?? null;
    $trip_name = sanitize($input['trip_name'] ?? '');
    $description = sanitize($input['description'] ?? '');
    $start_date = !empty($input['start_date']) ? $input['start_date'] : null;
    $end_date = !empty($input['end_date']) ? $input['end_date'] : null;
    $status = $input['status'] ?? 'upcoming';
    $visibility = $input['visibility'] ?? 'private';

    if (!$trip_id || empty($trip_name)) {
        jsonResponse(['success' => false, 'message' => 'Trip ID and name are required']);
    }

    $stmt = $pdo->prepare("UPDATE trips SET trip_name=?, description=?, start_date=?, end_date=?, status=?, visibility=? WHERE id=? AND user_id=?");
    $stmt->execute([$trip_name, $description, $start_date, $end_date, $status, $visibility, $trip_id, $user_id]);
    jsonResponse(['success' => true, 'message' => 'Trip updated']);
}
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $trip_id = $input['id'] ?? null;
    if (!$trip_id) {
        jsonResponse(['success' => false, 'message' => 'Trip ID required']);
    }

    $stmt = $pdo->prepare("DELETE FROM trips WHERE id=? AND user_id=?");
    $stmt->execute([$trip_id, $user_id]);
    jsonResponse(['success' => true, 'message' => 'Trip deleted']);
} else {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
