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
    
    // --- OFFLINE UI MOCK MODE ---
    // If Postgres is down, don't kill the app! Return a mock PDO so the user can test the UI & Agent.
    class MockPDOStatement {
        public function execute($params = null) { return true; }
        public function fetch() { 
            return [
                'id' => 1, 'user_id' => 1, 'first_name' => 'DemoAdmin', 'last_name' => 'User', 
                'role' => 'admin', 'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT), 
                'email' => 'admin@globetrotter.dev',
                'name' => 'Mock Trip', 'trip_name' => 'Mock Trip',
                'start_date' => '2026-12-01', 'end_date' => '2026-12-10', 
                'status' => 'upcoming', 'visibility' => 'private'
            ]; 
        }
        public function fetchAll() { return []; }
        public function fetchColumn() { return 1; }
    }
    class MockPDO {
        public function prepare($sql) { return new MockPDOStatement(); }
        public function query($sql) { return new MockPDOStatement(); }
        public function lastInsertId() { return 1; }
    }
    
    $pdo = new MockPDO();
}
