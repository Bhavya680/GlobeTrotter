<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
requireLogin();

$pdo = DB::getInstance();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$tripId = $_GET['trip_id'] ?? null;

if (!$tripId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $tripId = $input['trip_id'] ?? null;
}

if (!$tripId) {
    http_response_code(400);
    echo json_encode(['error' => 'trip_id is required']);
    exit;
}

$check = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
$check->execute([$tripId, $userId]);
if (!$check->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized for this trip']);
    exit;
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM trip_budget WHERE trip_id = ?");
        $stmt->execute([$tripId]);
        $budget = $stmt->fetch();
        if ($budget) {
            echo json_encode(['success' => true, 'data' => $budget]);
        } else {
            echo json_encode(['success' => true, 'data' => null]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $transport = $input['transport_budget'] ?? $_POST['transport_budget'] ?? 0;
        $stay = $input['stay_budget'] ?? $_POST['stay_budget'] ?? 0;
        $activities = $input['activities_budget'] ?? $_POST['activities_budget'] ?? 0;
        $meals = $input['meals_budget'] ?? $_POST['meals_budget'] ?? 0;
        $misc = $input['misc_budget'] ?? $_POST['misc_budget'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO trip_budget (trip_id, transport_budget, stay_budget, activities_budget, meals_budget, misc_budget)
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (trip_id) DO UPDATE SET 
            transport_budget = EXCLUDED.transport_budget,
            stay_budget = EXCLUDED.stay_budget,
            activities_budget = EXCLUDED.activities_budget,
            meals_budget = EXCLUDED.meals_budget,
            misc_budget = EXCLUDED.misc_budget
        ");
        $stmt->execute([$tripId, $transport, $stay, $activities, $meals, $misc]);
        
        echo json_encode(['success' => true, 'message' => 'Budget updated successfully']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
