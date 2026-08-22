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

/**
 * 🍉 Render Watermelon UI Themed Glassmorphic Pagination
 */
function render_watermelon_pagination(int $currentPage, int $totalPages, string $baseUrl = '?page=', array $options = []): string {
    if ($totalPages <= 1) return '';

    $range = $options['range'] ?? 2;
    $compact = $options['compact'] ?? false;
    $totalItems = $options['total_items'] ?? null;

    $urlFor = function(int $p) use ($baseUrl) {
        if (str_contains($baseUrl, '{page}')) {
            return str_replace('{page}', (string) $p, $baseUrl);
        }
        $sep = (str_contains($baseUrl, '?') ? '&' : '?');
        if (str_ends_with($baseUrl, '=') || str_ends_with($baseUrl, '?') || str_ends_with($baseUrl, '&')) {
            return $baseUrl . $p;
        }
        return $baseUrl . $sep . 'page=' . $p;
    };

    $html = '<nav aria-label="Page navigation" class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 my-4">';
    
    if ($totalItems !== null) {
        $html .= '<div class="wm-pagination-meta text-muted small">Showing page <span class="highlight">' . $currentPage . '</span> of <span class="highlight">' . $totalPages . '</span> (' . $totalItems . ' total destinations)</div>';
    } else {
        $html .= '<div></div>';
    }

    $html .= '<div class="' . ($compact ? 'wm-pagination-compact' : 'wm-pagination') . '">';

    // Previous Button
    if ($currentPage > 1) {
        $html .= '<a href="' . htmlspecialchars($urlFor($currentPage - 1)) . '" class="wm-page-item wm-page-nav" title="Previous Page"><i class="fa-solid fa-chevron-left fa-xs"></i> <span>Prev</span></a>';
    } else {
        $html .= '<button class="wm-page-item wm-page-nav" disabled><i class="fa-solid fa-chevron-left fa-xs"></i> <span>Prev</span></button>';
    }

    // Page Numbers with Smart Ellipsis
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);

    if ($start > 1) {
        $html .= '<a href="' . htmlspecialchars($urlFor(1)) . '" class="wm-page-item">1</a>';
        if ($start > 2) {
            $html .= '<span class="wm-page-ellipsis">&hellip;</span>';
        }
    }

    for ($p = $start; $p <= $end; $p++) {
        if ($p === $currentPage) {
            $html .= '<span class="wm-page-item active" aria-current="page">' . $p . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($urlFor($p)) . '" class="wm-page-item">' . $p . '</a>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="wm-page-ellipsis">&hellip;</span>';
        }
        $html .= '<a href="' . htmlspecialchars($urlFor($totalPages)) . '" class="wm-page-item">' . $totalPages . '</a>';
    }

    // Next Button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . htmlspecialchars($urlFor($currentPage + 1)) . '" class="wm-page-item wm-page-nav" title="Next Page"><span>Next</span> <i class="fa-solid fa-chevron-right fa-xs"></i></a>';
    } else {
        $html .= '<button class="wm-page-item wm-page-nav" disabled><span>Next</span> <i class="fa-solid fa-chevron-right fa-xs"></i></button>';
    }

    $html .= '</div>';
    $html .= '</nav>';

    return $html;
}

