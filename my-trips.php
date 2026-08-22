<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();

$stmt = $pdo->prepare('
    SELECT
        t.id, t.name, t.start_date, t.end_date, t.description, t.cover_photo,
        t.is_public, t.share_slug,
        COUNT(DISTINCT s.id) AS stop_count,
        COUNT(DISTINCT s.city_id) AS destination_count,
        (t.end_date < CURRENT_DATE) AS is_past
    FROM trips t
    LEFT JOIN stops s ON s.trip_id = t.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.start_date DESC
');
$stmt->execute([$userId]);
$trips = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<!-- UI HTML GOES HERE -->
