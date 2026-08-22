<?php
// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'globetrotter');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Constants
define('SITE_NAME', 'GlobeTrotter');
define('SITE_URL', 'http://localhost/globetrotter'); // Adjust as needed
define('UPLOAD_PATH', __DIR__ . '/assets/uploads/');

// Upload settings
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
?>
