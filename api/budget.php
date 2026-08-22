<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET request to fetch budget vs actual
if ($method === 'GET') {
    if (!isset($_GET['trip_id'])) {
        echo json_encode(['error' => 'Missing trip_id']);
        http_response_code(400);
        exit;
    }

    $tripId = (int)$_GET['trip_id'];

    // Verify ownership
    $stmt = $pdo->prepare("SELECT user_id, start_date, end_date, visibility FROM trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        echo json_encode(['error' => 'Trip not found']);
        http_response_code(404);
        exit;
    }

    if ($trip['user_id'] !== $userId && $trip['visibility'] !== 'public') {
        echo json_encode(['error' => 'Unauthorized']);
        http_response_code(403);
        exit;
    }

    // 1. Fetch Budget
    $stmt = $pdo->prepare("SELECT transport_budget, stay_budget, activities_budget, meals_budget, misc_budget, (transport_budget + stay_budget + activities_budget + meals_budget + misc_budget) AS total_budget FROM trip_budget WHERE trip_id = ?");
    $stmt->execute([$tripId]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        $budget = [
            'transport_budget' => 0,
            'stay_budget' => 0,
            'activities_budget' => 0,
            'meals_budget' => 0,
            'misc_budget' => 0,
            'total_budget' => 0
        ];
    }

    // 2. Fetch Actuals from activities (Activities category) and trip_stops (Stay category)
    $stmt = $pdo->prepare("
        SELECT a.category, SUM(COALESCE(sa.custom_cost, a.cost)) as actual_cost
        FROM trip_activities sa
        JOIN activities a ON sa.activity_id = a.id
        JOIN trip_stops s ON sa.trip_stop_id = s.id
        WHERE s.trip_id = ?
        GROUP BY a.category
    ");
    $stmt->execute([$tripId]);
    $activityActualsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $actuals = [
        'transport' => 0,
        'stay' => 0,
        'activities' => 0,
        'meals' => 0,
        'misc' => 0
    ];

    foreach ($activityActualsRaw as $row) {
        $cat = $row['category'];
        $cost = (float)$row['actual_cost'];
        if ($cat === 'food') {
            $actuals['meals'] += $cost;
        } elseif (in_array($cat, ['sightseeing', 'adventure', 'culture', 'relaxation'])) {
            $actuals['activities'] += $cost;
        } else {
            $actuals['misc'] += $cost; // 'other'
        }
    }

    // Add accommodation cost from trip_stops to stay
    $stmt = $pdo->prepare("SELECT SUM(budget_for_stop) as total_stay FROM trip_stops WHERE trip_id = ?");
    $stmt->execute([$tripId]);
    $stayCost = (float)$stmt->fetchColumn();
    $actuals['stay'] += $stayCost;

    // 3. Add manual budget_items (expenses) to actuals
    $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM budget_items WHERE trip_id = ? GROUP BY category");
    $stmt->execute([$tripId]);
    $manualExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($manualExpenses as $me) {
        $cat = $me['category'];
        $cost = (float)$me['total'];
        if ($cat === 'transport') {
            $actuals['transport'] += $cost;
        } elseif ($cat === 'stay') {
            $actuals['stay'] += $cost;
        } elseif ($cat === 'meals') {
            $actuals['meals'] += $cost;
        } else {
            $actuals['misc'] += $cost; // 'other'
        }
    }

    echo json_encode([
        'budget' => [
            'transport' => (float)$budget['transport_budget'],
            'stay' => (float)$budget['stay_budget'],
            'activities' => (float)$budget['activities_budget'],
            'meals' => (float)$budget['meals_budget'],
            'misc' => (float)$budget['misc_budget'],
            'total' => (float)$budget['total_budget']
        ],
        'actuals' => $actuals
    ]);
    exit;
}

// Handle POST request to save budget
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['trip_id'])) {
        echo json_encode(['error' => 'Invalid data']);
        http_response_code(400);
        exit;
    }

    $tripId = (int)$data['trip_id'];

    // Verify ownership
    $stmt = $pdo->prepare("SELECT user_id FROM trips WHERE id = ?");
    $stmt->execute([$tripId]);
    if ($stmt->fetchColumn() !== $userId) {
        echo json_encode(['error' => 'Unauthorized']);
        http_response_code(403);
        exit;
    }

    $transport = isset($data['transport']) ? (float)$data['transport'] : 0;
    $stay = isset($data['stay']) ? (float)$data['stay'] : 0;
    $activities = isset($data['activities']) ? (float)$data['activities'] : 0;
    $meals = isset($data['meals']) ? (float)$data['meals'] : 0;
    $misc = isset($data['misc']) ? (float)$data['misc'] : 0;

    $stmt = $pdo->prepare("
        INSERT INTO trip_budget (trip_id, transport_budget, stay_budget, activities_budget, meals_budget, misc_budget)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (trip_id) DO UPDATE SET 
            transport_budget = EXCLUDED.transport_budget,
            stay_budget = EXCLUDED.stay_budget,
            activities_budget = EXCLUDED.activities_budget,
            meals_budget = EXCLUDED.meals_budget,
            misc_budget = EXCLUDED.misc_budget,
            updated_at = CURRENT_TIMESTAMP
    ");

    try {
        $stmt->execute([$tripId, $transport, $stay, $activities, $meals, $misc]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error']);
        http_response_code(500);
    }
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
http_response_code(405);
exit;
