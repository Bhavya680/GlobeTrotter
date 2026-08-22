<?php
require 'includes/auth.php';

// Bypass login
$_SESSION['user_id'] = 1;
$userId = 1;

try {
    ob_start();
    require 'dashboard.php';
    ob_end_clean();
    echo "SUCCESS: Dashboard loaded without PHP fatals!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
