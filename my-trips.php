<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = DB::getInstance();
$userId = $_SESSION['user_id'];

// Fetch all trips for user
$tripsStmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY start_date DESC");
$tripsStmt->execute([$userId]);
$myTrips = $tripsStmt->fetchAll();

// Group trips by status for UI convenience
$tripsByStatus = [
    'upcoming' => [],
    'ongoing' => [],
    'completed' => []
];

foreach ($myTrips as $trip) {
    $tripsByStatus[$trip['status']][] = $trip;
}

// Data is now ready for the UI team:
// $tripsByStatus['upcoming'], $tripsByStatus['ongoing'], $tripsByStatus['completed']
?>
<!-- UI HTML GOES HERE -->
