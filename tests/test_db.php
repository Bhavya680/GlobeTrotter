<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM community_posts ORDER BY id DESC LIMIT 1");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
