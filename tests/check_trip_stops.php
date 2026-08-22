<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='trip_stops'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
