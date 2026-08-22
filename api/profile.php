<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$pdo = DB::getInstance();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = getCurrentUser();
    if ($user) {
        $stmt = $pdo->prepare("SELECT phone, city, country, additional_info FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $extra = $stmt->fetch();
        if ($extra) {
            $user = array_merge($user, $extra);
        }
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $firstName = $input['first_name'] ?? $_POST['first_name'] ?? '';
    $lastName = $input['last_name'] ?? $_POST['last_name'] ?? '';
    $phone = $input['phone'] ?? $_POST['phone'] ?? '';
    $city = $input['city'] ?? $_POST['city'] ?? '';
    $country = $input['country'] ?? $_POST['country'] ?? '';
    $additionalInfo = $input['additional_info'] ?? $_POST['additional_info'] ?? '';
    
    if (empty($firstName) || empty($lastName)) {
        http_response_code(400);
        echo json_encode(['error' => 'First and last name are required.']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, city = ?, country = ?, additional_info = ? WHERE id = ?");
    if ($stmt->execute([$firstName, $lastName, $phone, $city, $country, $additionalInfo, $userId])) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>
