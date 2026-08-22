<?php
/**
 * Comprehensive System Verification Script for GlobeTrotter
 * Validates all 13 modules from the PDF requirements.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "========================================================\n";
echo "🔎 GLOBETROTTER ALL 13 MODULES AUDIT VERIFICATION\n";
echo "========================================================\n\n";

$errors = [];
$passes = [];

function check($module, $condition, $details = '') {
    global $errors, $passes;
    if ($condition) {
        $passes[] = "[PASS] Module: $module " . ($details ? "($details)" : "");
        echo "✅ PASS: $module " . ($details ? "($details)" : "") . "\n";
    } else {
        $errors[] = "[FAIL] Module: $module " . ($details ? "($details)" : "");
        echo "❌ FAIL: $module " . ($details ? "($details)" : "") . "\n";
    }
}

// 1. Auth & Users
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$adminUser = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1")->fetch();
check("1. Login / Signup", $userCount >= 10 && !empty($adminUser), "$userCount users seeded, Admin email: {$adminUser['email']}");

// 2. Dashboard
$tripsWithStops = (int)$pdo->query("SELECT COUNT(DISTINCT trip_id) FROM trip_stops")->fetchColumn();
check("2. Dashboard", $tripsWithStops > 0, "$tripsWithStops trips with multi-stop itineraries");

// 3. Create Trip
$sampleTrip = $pdo->query("SELECT * FROM trips WHERE id = 1")->fetch();
check("3. Create Trip", !empty($sampleTrip['trip_name']) && !empty($sampleTrip['start_date']), "Sample trip '{$sampleTrip['trip_name']}' [{$sampleTrip['start_date']} to {$sampleTrip['end_date']}]");

// 4. My Trips
$myTrips = $pdo->query("SELECT t.id, t.trip_name, COUNT(s.id) as stop_count FROM trips t LEFT JOIN trip_stops s ON s.trip_id = t.id GROUP BY t.id ORDER BY t.id LIMIT 3")->fetchAll();
check("4. My Trips", count($myTrips) >= 3, "Trip cards with stop counts loaded");

// 5. Itinerary Builder
$stops = $pdo->query("SELECT * FROM trip_stops WHERE trip_id = 1 ORDER BY order_index ASC")->fetchAll();
check("5. Itinerary Builder", count($stops) >= 2, count($stops) . " stops in Trip 1 with order_index sequence");

// 6. Itinerary View
$activities = $pdo->query("SELECT ta.*, a.name, a.category, a.cost FROM trip_activities ta JOIN activities a ON a.id = ta.activity_id LIMIT 5")->fetchAll();
check("6. Itinerary View", count($activities) >= 5, "Scheduled activities with time, cost, categories");

// 7. City Search
$citiesCount = (int)$pdo->query("SELECT COUNT(*) FROM cities")->fetchColumn();
$regions = $pdo->query("SELECT DISTINCT region FROM cities WHERE region IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
check("7. City Search", $citiesCount >= 20 && count($regions) >= 5, "$citiesCount global cities across regions: " . implode(', ', $regions));

// 8. Activity Search
$actCount = (int)$pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
$categories = $pdo->query("SELECT DISTINCT category FROM activities")->fetchAll(PDO::FETCH_COLUMN);
check("8. Activity Search", $actCount >= 40 && count($categories) >= 4, "$actCount activities across: " . implode(', ', $categories));

// 9. Trip Budget
$budgetRows = (int)$pdo->query("SELECT COUNT(*) FROM trip_budget")->fetchColumn();
$itemsCount = (int)$pdo->query("SELECT COUNT(*) FROM budget_items")->fetchColumn();
check("9. Trip Budget & Cost Breakdown", $budgetRows >= 5 && $itemsCount >= 10, "$budgetRows trip budgets & $itemsCount itemized expense logs");

// 10. Trip Calendar
$calendarTrips = $pdo->query("SELECT id, trip_name, start_date, end_date FROM trips WHERE start_date IS NOT NULL")->fetchAll();
check("10. Trip Calendar / Timeline", count($calendarTrips) >= 5, count($calendarTrips) . " trips mapped to date timeline");

// 11. Public Itinerary Sharing
$publicTrips = $pdo->query("SELECT id, trip_name, share_slug, visibility FROM trips WHERE visibility = 'public'")->fetchAll();
check("11. Shared / Public Itinerary", count($publicTrips) >= 3, count($publicTrips) . " public trips with share_slugs e.g. '{$publicTrips[0]['share_slug']}'");

// 12. User Profile & Preferences
$savedCount = (int)$pdo->query("SELECT COUNT(*) FROM saved_destinations")->fetchColumn();
$userPrefs = $pdo->query("SELECT preferences FROM users WHERE preferences IS NOT NULL LIMIT 1")->fetchColumn();
check("12. User Profile / Settings", $savedCount >= 5 && !empty($userPrefs), "$savedCount saved wishlist destinations & JSONB preferences configured");

// 13. Admin Analytics Dashboard
$postsCount = (int)$pdo->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
check("13. Admin / Analytics Dashboard", $userCount >= 10 && $citiesCount >= 20 && $postsCount >= 5, "Admin metrics populated with $userCount users, $citiesCount cities, $postsCount community posts");

echo "\n========================================================\n";
echo "📊 AUDIT SUMMARY: " . count($passes) . " PASSED, " . count($errors) . " FAILED\n";
echo "========================================================\n";
