<?php
/**
 * Frontend-Backend Integration Test Suite
 * Simulates user and admin sessions, calling API endpoints and checking output.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "========================================================\n";
echo "🧪 GLOBETROTTER FRONTEND-BACKEND INTEGRATION TESTS\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertJsonSuccess($name, $result) {
    global $passCount, $failCount;
    if (is_array($result) && (isset($result['success']) && $result['success'] === true || isset($result['data']))) {
        $passCount++;
        echo "✅ [SUCCESS] $name\n";
    } else {
        $failCount++;
        echo "❌ [FAILED] $name: " . json_encode($result) . "\n";
    }
}

// 1. Test Cities API
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['search' => 'Tokyo'];
ob_start();
require __DIR__ . '/../api/cities.php';
$citiesOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Cities API (Search Tokyo)", $citiesOut);

// 2. Test Activities API
$_GET = ['city_id' => 1];
ob_start();
require __DIR__ . '/../api/activities.php';
$actOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Activities API (City 1)", $actOut);

// 3. Test Trips API with Logged-in User
$_SESSION['user_id'] = 1; // Admin user
$_GET = [];
ob_start();
require __DIR__ . '/../api/trips.php';
$tripsOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Trips API (User 1)", $tripsOut);

// 4. Test Budget API for Trip 1
$_GET = ['trip_id' => 1];
ob_start();
require __DIR__ . '/../api/budget.php';
$budgetOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Budget API (Trip 1)", $budgetOut);

// 5. Test Community API
$_GET = [];
ob_start();
require __DIR__ . '/../api/community.php';
$commOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Community Posts Feed", $commOut);

// 6. Test Profile API
$_GET = [];
ob_start();
require __DIR__ . '/../api/profile.php';
$profOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Profile API", $profOut);

// 7. Test Admin Analytics API
$_GET = ['action' => 'analytics'];
ob_start();
require __DIR__ . '/../api/admin.php';
$adminOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Admin Analytics API", $adminOut);

// 8. Test Trip Cloning API (POST /api/trips.php?action=copy)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['action' => 'copy'];
$_POST = ['trip_id' => 1];
ob_start();
require __DIR__ . '/../api/trips.php';
$cloneOut = json_decode(ob_get_clean(), true);
assertJsonSuccess("Trip Copy/Cloning API", $cloneOut);

echo "\n========================================================\n";
echo "📊 INTEGRATION RESULTS: $passCount PASSED, $failCount FAILED\n";
echo "========================================================\n";
