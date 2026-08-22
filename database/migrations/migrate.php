<?php
require __DIR__ . '/includes/db.php';
try {
    $pdo->exec("ALTER TABLE stops ADD COLUMN IF NOT EXISTS transport_note TEXT, ADD COLUMN IF NOT EXISTS accommodation VARCHAR(255), ADD COLUMN IF NOT EXISTS accommodation_cost NUMERIC(10,2), ADD COLUMN IF NOT EXISTS stop_notes TEXT;");
    echo "Migration applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
