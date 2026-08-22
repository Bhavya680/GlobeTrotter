<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN preferences JSON DEFAULT '{}'");
    echo "Preferences column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
