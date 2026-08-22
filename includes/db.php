<?php
require_once __DIR__ . '/../config.php';

// Start session with secure cookie settings before any DB or output
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,  // set true when serving over HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    if (!empty(DATABASE_URL)) {
        // Parse complete cloud connection URL: postgresql://user:password@host:port/dbname?sslmode=require
        $dbParts = parse_url(DATABASE_URL);
        $dbHost = $dbParts['host'] ?? '127.0.0.1';
        $dbPort = $dbParts['port'] ?? 5432;
        $dbUser = isset($dbParts['user']) ? urldecode($dbParts['user']) : 'postgres';
        $dbPass = isset($dbParts['pass']) ? urldecode($dbParts['pass']) : '';
        $dbName = isset($dbParts['path']) ? ltrim($dbParts['path'], '/') : 'globetrotter';

        $sslMode = 'prefer';
        if (isset($dbParts['query'])) {
            parse_str($dbParts['query'], $queryParams);
            if (!empty($queryParams['sslmode'])) {
                $sslMode = $queryParams['sslmode'];
            }
        }
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $dbHost, $dbPort, $dbName, $sslMode);
    } else {
        $sslMode = defined('DB_SSLMODE') ? DB_SSLMODE : 'prefer';
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', DB_HOST, DB_PORT, DB_NAME, $sslMode);
        $dbUser = DB_USER;
        $dbPass = DB_PASS;
    }

    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('[GlobeTrotter] DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'error'   => APP_DEBUG ? $e->getMessage() : 'Database connection failed',
    ]));
}
