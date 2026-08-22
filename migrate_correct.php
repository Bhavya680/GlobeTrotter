<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE trip_stops ADD COLUMN IF NOT EXISTS transport_note TEXT, ADD COLUMN IF NOT EXISTS accommodation VARCHAR(255);");
    echo "Migration applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
