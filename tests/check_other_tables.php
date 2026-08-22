<?php
require 'includes/db.php';

echo "CITIES:\n";
$stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='cities'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nACTIVITIES:\n";
$stmt2 = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='activities'");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

echo "\nBUDGET_ITEMS:\n";
$stmt3 = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='trip_budget'");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));

