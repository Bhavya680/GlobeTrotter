<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE trips ADD COLUMN IF NOT EXISTS share_slug VARCHAR(40) UNIQUE;");
    echo "Migration applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
