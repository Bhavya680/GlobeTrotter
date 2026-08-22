<?php
require 'includes/auth.php';

// Bypass login
$_SESSION['user_id'] = 1;
$userId = 1;

try {
    ob_start();
    require 'create-trip.php';
    ob_end_clean();
    echo "SUCCESS: create-trip loaded without PHP fatals!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
