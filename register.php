<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $firstName = $input['first_name'] ?? $_POST['first_name'] ?? '';
    $lastName = $input['last_name'] ?? $_POST['last_name'] ?? '';
    $email = $input['email'] ?? $_POST['email'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required.']);
        exit;
    }
    
    $pdo = DB::getInstance();
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered.']);
        exit;
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$firstName, $lastName, $email, $hash])) {
        // Auto login
        login($email, $password);
        echo json_encode(['success' => true, 'message' => 'Registration successful.', 'redirect' => 'dashboard.php']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
}
?>
