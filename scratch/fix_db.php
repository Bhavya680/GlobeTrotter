<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec('ALTER TABLE trip_activities ADD COLUMN created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()');
    echo "Successfully added created_at column to trip_activities.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
