<?php
require_once __DIR__ . '/../includes/auth.php';

require_login_page('/login.php');
if (!is_admin_user()) {
    http_response_code(403);
    die('403 Forbidden: admin access required.');
}

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalTrips = (int) $pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn();
$totalPublicTrips = (int) $pdo->query("SELECT COUNT(*) FROM trips WHERE visibility = 'public'")->fetchColumn();
$totalStops = (int) $pdo->query('SELECT COUNT(*) FROM trip_stops')->fetchColumn();

$topCitiesStmt = $pdo->query('
    SELECT c.name, c.country, COUNT(s.id) AS times_used
    FROM trip_stops s
    JOIN cities c ON c.id = s.city_id
    GROUP BY c.id
    ORDER BY times_used DESC
    LIMIT 10
');
$topCities = $topCitiesStmt->fetchAll();

$topActivitiesStmt = $pdo->query('
    SELECT a.name, a.category, COUNT(sa.id) AS times_scheduled
    FROM trip_activities sa
    JOIN activities a ON a.id = sa.activity_id
    GROUP BY a.id
    ORDER BY times_scheduled DESC
    LIMIT 10
');
$topActivities = $topActivitiesStmt->fetchAll();

$signupTrendStmt = $pdo->query("
    SELECT d::date AS day, COALESCE(u.signups, 0) AS signups
    FROM generate_series(CURRENT_DATE - INTERVAL '29 days', CURRENT_DATE, INTERVAL '1 day') d
    LEFT JOIN (
        SELECT created_at::date AS day, COUNT(*) AS signups
        FROM users
        GROUP BY created_at::date
    ) u ON u.day = d::date
    ORDER BY d
");
$signupTrend = $signupTrendStmt->fetchAll();

$recentUsersStmt = $pdo->query('
    SELECT id, first_name AS name, email, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 20
');
$recentUsers = $recentUsersStmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<!-- ADMIN UI HTML GOES HERE -->
