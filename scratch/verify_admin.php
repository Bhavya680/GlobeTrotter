<?php
require_once __DIR__ . '/../includes/db.php';

// Test: verify admin user exists and password is correct
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, password_hash FROM users WHERE email = 'admin@globetrotter.dev'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== Admin User Check ===\n";
if (!$user) {
    echo "❌ Admin user NOT found!\n";
} else {
    echo "✅ Admin user found: " . $user['first_name'] . " " . $user['last_name'] . " (Role: " . $user['role'] . ")\n";
    $ok = password_verify('Admin@123', $user['password_hash']);
    echo ($ok ? "✅" : "❌") . " Password 'Admin@123' verify: " . ($ok ? 'OK' : 'FAILED') . "\n";
}

echo "\n=== Aggregation Queries Test ===\n";
echo "Total users: " . $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
echo "Total trips: " . $pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn() . "\n";
echo "Total activities: " . $pdo->query('SELECT COUNT(*) FROM trip_activities')->fetchColumn() . "\n";
echo "Total posts: " . $pdo->query('SELECT COUNT(*) FROM community_posts')->fetchColumn() . "\n";

echo "\n=== Cities Popularity ===\n";
$rows = $pdo->query("SELECT c.name, COUNT(DISTINCT s.id) as times_added FROM cities c LEFT JOIN trip_stops s ON s.city_id = c.id GROUP BY c.id ORDER BY times_added DESC LIMIT 5")->fetchAll();
foreach ($rows as $r) echo " - {$r['name']}: {$r['times_added']} stops\n";

echo "\n=== Trip Status ===\n";
$rows = $pdo->query("SELECT status, COUNT(*) as count FROM trips GROUP BY status")->fetchAll();
foreach ($rows as $r) echo " - {$r['status']}: {$r['count']}\n";

echo "\nAll checks done!\n";
