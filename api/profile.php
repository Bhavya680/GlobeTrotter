<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = DB::getInstance();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, city, country, profile_photo, additional_info, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    jsonResponse(['success' => true, 'data' => $user]);
} 
elseif ($method === 'POST') {
    // Check CSRF token for POST
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $action = $_GET['action'] ?? 'update';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (password_verify($current, $user['password_hash'])) {
            if (strlen($new) >= 8) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updateStmt->execute([$hash, $user_id]);
                jsonResponse(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                jsonResponse(['success' => false, 'message' => 'New password must be at least 8 characters']);
            }
        } else {
            jsonResponse(['success' => false, 'message' => 'Incorrect current password'], 401);
        }
    } 
    elseif ($action === 'update') {
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $country = sanitize($_POST['country'] ?? '');
        $additional_info = sanitize($_POST['additional_info'] ?? '');
        
        if (empty($first_name) || empty($last_name)) {
            jsonResponse(['success' => false, 'message' => 'First and last name are required']);
        }

        $profile_photo = null;
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $profile_photo = uploadFile($_FILES['profile_photo'], 'profiles');
        }

        if ($profile_photo) {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, city=?, country=?, additional_info=?, profile_photo=? WHERE id=?");
            $stmt->execute([$first_name, $last_name, $phone, $city, $country, $additional_info, $profile_photo, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, city=?, country=?, additional_info=? WHERE id=?");
            $stmt->execute([$first_name, $last_name, $phone, $city, $country, $additional_info, $user_id]);
        }

        jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
    }
} 
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION = [];
    session_destroy();
    
    jsonResponse(['success' => true, 'message' => 'Account deleted']);
} else {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
