<?php
require 'includes/auth.php';
$_SESSION['user_id'] = 1;
$_GET['trip_id'] = 1;
try {
    ob_start();
    require 'itinerary-view.php';
    ob_end_clean();
    echo "SUCCESS: itinerary-view loaded without PHP fatals!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
