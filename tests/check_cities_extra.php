<?php
require 'includes/db.php';

echo "=== CITIES (id, name, image_url) ===\n";
$s = $pdo->query("SELECT id, name, country, image_url FROM cities ORDER BY id");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
