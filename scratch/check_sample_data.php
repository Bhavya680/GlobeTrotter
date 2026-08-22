<?php
require 'includes/db.php';

echo "=== TRIPS ===\n";
$s = $pdo->query("SELECT id, user_id, trip_name, start_date, end_date, visibility, status, cover_photo, share_slug FROM trips ORDER BY id");
print_r($s->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TRIP_BUDGET ===\n";
$s = $pdo->query("SELECT * FROM trip_budget ORDER BY trip_id");
print_r($s->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TRIP_STOPS (count per trip) ===\n";
$s = $pdo->query("SELECT trip_id, COUNT(*) AS stops FROM trip_stops GROUP BY trip_id ORDER BY trip_id");
print_r($s->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TRIP_ACTIVITIES (count) ===\n";
$s = $pdo->query("SELECT COUNT(*) AS total FROM trip_activities");
print_r($s->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== USERS ===\n";
$s = $pdo->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY id");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
