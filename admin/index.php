<?php
require_once __DIR__ . '/../includes/auth.php';

require_login_page('/login.php');
if (!is_admin_user()) {
    http_response_code(403);
    die('403 Forbidden: admin access required.');
}

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalTrips = (int) $pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn();
$totalPublicTrips = (int) $pdo->query("SELECT COUNT(*) FROM trips WHERE visibility = 'public'")->fetchColumn();
$totalStops = (int) $pdo->query('SELECT COUNT(*) FROM trip_stops')->fetchColumn();

$topCitiesStmt = $pdo->query('
    SELECT c.name, c.country, COUNT(s.id) AS times_used
    FROM trip_stops s
    JOIN cities c ON c.id = s.city_id
    GROUP BY c.id, c.name, c.country
    ORDER BY times_used DESC
    LIMIT 10
');
$topCities = $topCitiesStmt->fetchAll();

$topActivitiesStmt = $pdo->query('
    SELECT a.name, a.category, COUNT(sa.id) AS times_scheduled
    FROM trip_activities sa
    JOIN activities a ON a.id = sa.activity_id
    GROUP BY a.id, a.name, a.category
    ORDER BY times_scheduled DESC
    LIMIT 10
');
$topActivities = $topActivitiesStmt->fetchAll();

$recentUsersStmt = $pdo->query('
    SELECT id, first_name, last_name, email, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 20
');
$recentUsers = $recentUsersStmt->fetchAll();

$pageTitle = 'Admin Dashboard — GlobeTrotter';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h2 fw-bold mb-0"><i class="fa-solid fa-shield-halved text-danger me-2"></i>GlobeTrotter Admin Analytics</h1>
            <p class="text-muted mb-0 mt-1">Platform overview, user management, and popular travel destinations</p>
        </div>
        <span class="badge bg-danger fs-6 px-3 py-2">Administrator Panel</span>
    </div>

    <!-- Overview Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-white-50 text-uppercase fw-bold">Total Users</span>
                        <h2 class="fw-bold mb-0 mt-1"><?= $totalUsers ?></h2>
                    </div>
                    <i class="fa-solid fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-white-50 text-uppercase fw-bold">Total Trips</span>
                        <h2 class="fw-bold mb-0 mt-1"><?= $totalTrips ?></h2>
                    </div>
                    <i class="fa-solid fa-suitcase fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-dark p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-dark-50 text-uppercase fw-bold">Public Trips</span>
                        <h2 class="fw-bold mb-0 mt-1"><?= $totalPublicTrips ?></h2>
                    </div>
                    <i class="fa-solid fa-globe fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-dark-50 text-uppercase fw-bold">Total Destinations</span>
                        <h2 class="fw-bold mb-0 mt-1"><?= $totalStops ?></h2>
                    </div>
                    <i class="fa-solid fa-location-dot fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tables -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-bold">
                    <i class="fa-solid fa-fire me-2 text-danger"></i>Most Popular Destinations
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>City</th>
                                <th>Country</th>
                                <th class="text-end">Times Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCities)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No data available yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($topCities as $tc): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($tc['name']) ?></strong></td>
                                        <td><span class="text-muted small"><?= htmlspecialchars($tc['country']) ?></span></td>
                                        <td class="text-end fw-bold text-primary"><?= $tc['times_used'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-bold">
                    <i class="fa-solid fa-ticket me-2 text-success"></i>Top Scheduled Activities
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Activity</th>
                                <th>Category</th>
                                <th class="text-end">Times Scheduled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topActivities)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No data available yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($topActivities as $ta): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($ta['name']) ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ta['category']) ?></span></td>
                                        <td class="text-end fw-bold text-success"><?= $ta['times_scheduled'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-users-gear me-2 text-primary"></i>Recent Registered Users</span>
            <span class="badge bg-light text-dark border"><?= count($recentUsers) ?> shown</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td><strong><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></strong></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                                        <?= ucfirst($u['role'] ?: 'user') ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
