<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = DB::getInstance();
$userId = $_SESSION['user_id'];

// Fetch user's upcoming trips
$upcomingTripsStmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? AND status = 'upcoming' ORDER BY start_date ASC LIMIT 5");
$upcomingTripsStmt->execute([$userId]);
$upcomingTrips = $upcomingTripsStmt->fetchAll();

// Fetch popular cities
$popularCitiesStmt = $pdo->query("SELECT * FROM cities ORDER BY popularity_score DESC LIMIT 6");
$popularCities = $popularCitiesStmt->fetchAll();

// Fetch recent community posts
$postsStmt = $pdo->query("SELECT cp.*, u.first_name, u.last_name FROM community_posts cp JOIN users u ON cp.user_id = u.id ORDER BY cp.created_at DESC LIMIT 5");
$recentPosts = $postsStmt->fetchAll();

// Data is now ready for the UI team:
// $upcomingTrips, $popularCities, $recentPosts
?>
<!-- UI HTML GOES HERE -->
