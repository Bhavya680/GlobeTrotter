<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

function calculateTripDuration($start, $end) {
    $datetime1 = new DateTime($start);
    $datetime2 = new DateTime($end);
    $interval = $datetime1->diff($datetime2);
    return $interval->days + 1; // Inclusive of start day
}

function getTripStatus($start_date, $end_date) {
    $today = date('Y-m-d');
    if ($today < $start_date) {
        return 'upcoming';
    } elseif ($today > $end_date) {
        return 'completed';
    } else {
        return 'ongoing';
    }
}

function uploadFile($file, $destinationFolder) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false; // File too large
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_FILE_TYPES)) {
        return false; // Invalid file type
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    
    // Ensure directory exists
    $fullDestPath = UPLOAD_PATH . $destinationFolder;
    if (!is_dir($fullDestPath)) {
        mkdir($fullDestPath, 0755, true);
    }
    
    $targetPath = $fullDestPath . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    return false;
}

function paginate($total, $per_page, $current_page) {
    $total_pages = ceil($total / $per_page);
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => ($current_page - 1) * $per_page
    ];
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}
?>
