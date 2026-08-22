<?php
require_once __DIR__ . '/../includes/db.php';
$stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'trip_activities'");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in trip_activities: " . implode(', ', $columns) . "\n";
