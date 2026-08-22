<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = DB::getInstance();

// Fetch total stats
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_trips'] = $pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
$stats['total_cities'] = $pdo->query("SELECT COUNT(*) FROM cities")->fetchColumn();

// Fetch recent users
$recentUsersStmt = $pdo->query("SELECT id, first_name, last_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 10");
$recentUsers = $recentUsersStmt->fetchAll();

// Fetch most popular cities
$popularCitiesStmt = $pdo->query("SELECT name, popularity_score FROM cities ORDER BY popularity_score DESC LIMIT 10");
$adminPopularCities = $popularCitiesStmt->fetchAll();

// Data is now ready for the UI team:
// $stats, $recentUsers, $adminPopularCities
?>
<!-- ADMIN UI HTML GOES HERE -->
