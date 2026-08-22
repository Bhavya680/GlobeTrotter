<?php
require_once __DIR__ . '/includes/auth.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN preferences JSON DEFAULT '{}'");
    echo "Done";
} catch (Exception $e) {
    echo $e->getMessage();
}
