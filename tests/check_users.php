<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='users'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
