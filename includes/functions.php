<?php
function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function json_success($data = null, int $status = 200): void {
    json_response(['success' => true, 'data' => $data], $status);
}

function json_error(string $message, int $status = 400): void {
    json_response(['success' => false, 'error' => $message], $status);
}

function get_request_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST;
}

function missing_fields(array $data, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }
    return $missing;
}

function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_date(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function clean_str($value): string {
    return trim(strip_tags((string) $value));
}

function generate_unique_slug(PDO $pdo, string $table = 'trips', string $column = 'share_slug', int $length = 8): string {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxIndex = strlen($alphabet) - 1;

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $slug = '';
        for ($i = 0; $i < $length; $i++) {
            $slug .= $alphabet[random_int(0, $maxIndex)];
        }

        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
    }

    return bin2hex(random_bytes(12));
}

function handle_image_upload(string $fieldName, string $subDir): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . ')');
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('Image exceeds the 5MB size limit');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        throw new RuntimeException('Only JPEG, PNG, or WebP images are allowed');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'bin',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = UPLOAD_DIR . '/' . $subDir;

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        throw new RuntimeException('Could not create upload directory');
    }

    $destPath = $destDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Could not save uploaded file');
    }

    return UPLOAD_URL_BASE . '/' . $subDir . '/' . $filename;
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getTripStatus(string $startDate, string $endDate): string {
    $today = date('Y-m-d');
    if ($endDate < $today) return 'completed';
    if ($startDate <= $today && $endDate >= $today) return 'ongoing';
    return 'upcoming';
}

function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function generate_csrf_token(): string {
    return generateCsrfToken();
}

function validateCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}
