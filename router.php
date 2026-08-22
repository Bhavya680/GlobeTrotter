<?php
/**
 * PHP Built-in Web Server Router for GlobeTrotter
 * Usage: php -S 127.0.0.1:8080 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static assets directly if they exist
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Forward all other requests to index.php
require_once __DIR__ . '/index.php';
