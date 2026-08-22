<?php
require 'includes/db.php';

echo "=== TRIP 1 ACTIVITIES (with cost) ===\n";
$s = $pdo->prepare("
    SELECT sa.id, sa.trip_stop_id, sa.scheduled_date, sa.scheduled_time, sa.custom_cost,
           a.name, a.category, a.cost, c.name AS city
    FROM trip_activities sa
    JOIN activities a ON a.id = sa.activity_id
    JOIN trip_stops s ON s.id = sa.trip_stop_id
    JOIN cities c ON c.id = s.city_id
    WHERE s.trip_id = ?
    ORDER BY sa.scheduled_date, sa.scheduled_time
");
$s->execute([1]);
print_r($s->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TRIP 1 STOPS ===\n";
$s = $pdo->prepare("SELECT s.*, c.name AS city_name, c.country FROM trip_stops s JOIN cities c ON c.id=s.city_id WHERE s.trip_id=? ORDER BY s.order_index");
$s->execute([1]);
print_r($s->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== budget_items table exists? ===\n";
try {
    $s = $pdo->query("SELECT COUNT(*) FROM budget_items");
    echo "budget_items rows: " . $s->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "budget_items table: " . $e->getMessage() . "\n";
}
