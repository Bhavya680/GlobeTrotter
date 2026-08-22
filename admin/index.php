<?php
require_once __DIR__ . '/../includes/auth.php';

// Access Control — only admins permitted
requireAdmin();

$currentAdmin = current_user();
$currentAdminId = current_user_id();

// ── 1. SUMMARY METRICS ────────────────────────────────────────────────────────
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$usersThisMonth = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn();

$totalTrips = (int) $pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn();
$tripsThisMonth = (int) $pdo->query("SELECT COUNT(*) FROM trips WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn();

$totalActivities = (int) $pdo->query('SELECT COUNT(*) FROM trip_activities')->fetchColumn();
$activitiesThisMonth = (int) $pdo->query("SELECT COUNT(*) FROM trip_activities WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn();

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM community_posts')->fetchColumn();
$postsThisMonth = (int) $pdo->query("SELECT COUNT(*) FROM community_posts WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)")->fetchColumn();

// ── 2. TAB 1: ALL USERS WITH TRIP COUNTS ──────────────────────────────────────
$usersStmt = $pdo->query('
    SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.city, u.country,
           u.role, u.profile_photo, u.created_at,
           COUNT(t.id) AS trips_count
    FROM users u
    LEFT JOIN trips t ON t.user_id = u.id
    GROUP BY u.id
    ORDER BY u.id DESC
');
$usersList = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// ── 3. TAB 2: POPULAR CITIES & TOP 8 CHART ────────────────────────────────────
$citiesStmt = $pdo->query('
    SELECT c.id, c.name, c.country, c.region, c.cost_index, c.popularity_score,
           COUNT(DISTINCT s.id) AS times_added,
           COUNT(DISTINCT a.id) AS activities_count
    FROM cities c
    LEFT JOIN trip_stops s ON s.city_id = c.id
    LEFT JOIN activities a ON a.city_id = c.id
    GROUP BY c.id, c.name, c.country, c.region, c.cost_index, c.popularity_score
    ORDER BY times_added DESC, c.popularity_score DESC, c.name ASC
');
$popularCities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Top 8 Cities for Horizontal Bar Chart
$top8Cities = array_slice($popularCities, 0, 8);
$topCitiesLabels = array_column($top8Cities, 'name');
$topCitiesData = array_map('intval', array_column($top8Cities, 'times_added'));

// ── 4. TAB 3: POPULAR ACTIVITIES & CATEGORY DISTRIBUTION ──────────────────────
$activitiesStmt = $pdo->query('
    SELECT a.id, a.name, a.category, a.cost, a.duration_hours,
           c.name AS city_name, c.country AS city_country,
           COUNT(ta.id) AS times_added
    FROM activities a
    JOIN cities c ON c.id = a.city_id
    LEFT JOIN trip_activities ta ON ta.activity_id = a.id
    GROUP BY a.id, a.name, a.category, a.cost, a.duration_hours, c.name, c.country
    ORDER BY times_added DESC, a.name ASC
');
$popularActivities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Activity Category Distribution
$catStmt = $pdo->query('
    SELECT a.category, COUNT(ta.id) AS times_used, COUNT(DISTINCT a.id) AS catalog_count
    FROM activities a
    LEFT JOIN trip_activities ta ON ta.activity_id = a.id
    GROUP BY a.category
    ORDER BY times_used DESC
');
$categoryRows = $catStmt->fetchAll(PDO::FETCH_ASSOC);
$activityCatsLabels = [];
$activityCatsData = [];
foreach ($categoryRows as $cr) {
    $activityCatsLabels[] = ucfirst($cr['category']);
    $activityCatsData[] = (int) $cr['times_used'];
}

// ── 5. TAB 4: TRENDS & ANALYTICS CHARTS ────────────────────────────────────────

// Chart A: 12-Month User Registrations
$userMonthlyTrend = [];
for ($i = 11; $i >= 0; $i--) {
    $mKey = date('Y-m', strtotime("-$i months"));
    $mLabel = date('M Y', strtotime("-$i months"));
    $userMonthlyTrend[$mKey] = ['label' => $mLabel, 'count' => 0];
}
$trendStmt = $pdo->query("
    SELECT TO_CHAR(created_at, 'YYYY-MM') AS month_key, COUNT(*) AS count
    FROM users
    WHERE created_at >= NOW() - INTERVAL '12 months'
    GROUP BY month_key
    ORDER BY month_key ASC
");
while ($r = $trendStmt->fetch()) {
    if (isset($userMonthlyTrend[$r['month_key']])) {
        $userMonthlyTrend[$r['month_key']]['count'] = (int) $r['count'];
    }
}
$userTrendsLabels = array_column($userMonthlyTrend, 'label');
$userTrendsData = array_column($userMonthlyTrend, 'count');

// Chart B: Trip Status Distribution (for React + Recharts)
$statusCounts = ['upcoming' => 0, 'ongoing' => 0, 'completed' => 0];
$statusStmt = $pdo->query('SELECT status, COUNT(*) AS count FROM trips GROUP BY status');
while ($r = $statusStmt->fetch()) {
    $st = strtolower($r['status']);
    if (isset($statusCounts[$st])) {
        $statusCounts[$st] = (int) $r['count'];
    }
}

// Chart C: 6-Month Trips Created (Public vs Private)
$tripsMonthly = [];
for ($i = 5; $i >= 0; $i--) {
    $mKey = date('Y-m', strtotime("-$i months"));
    $mLabel = date('M', strtotime("-$i months"));
    $tripsMonthly[$mKey] = ['label' => $mLabel, 'public' => 0, 'private' => 0];
}
$tripsMonthStmt = $pdo->query("
    SELECT TO_CHAR(created_at, 'YYYY-MM') AS month_key, visibility, COUNT(*) AS count
    FROM trips
    WHERE created_at >= NOW() - INTERVAL '6 months'
    GROUP BY month_key, visibility
    ORDER BY month_key ASC
");
while ($r = $tripsMonthStmt->fetch()) {
    $mKey = $r['month_key'];
    $vis = strtolower($r['visibility']);
    if (isset($tripsMonthly[$mKey])) {
        if ($vis === 'public') {
            $tripsMonthly[$mKey]['public'] = (int) $r['count'];
        } else {
            $tripsMonthly[$mKey]['private'] += (int) $r['count'];
        }
    }
}
$tripsMonthlyLabels = array_column($tripsMonthly, 'label');
$tripsMonthlyPublic = array_column($tripsMonthly, 'public');
$tripsMonthlyPrivate = array_column($tripsMonthly, 'private');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — GlobeTrotter</title>

    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- React 18, ReactDOM & Babel CDN -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <!-- Admin Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

<div class="admin-wrapper">
    <!-- ── LEFT SIDEBAR ──────────────────────────────────────────────────────── -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="index.php" class="admin-brand">
                <i class="fa-solid fa-earth-americas text-primary"></i>
                <span>GlobeTrotter</span>
                <span class="admin-brand-badge">Admin</span>
            </a>
        </div>

        <nav class="admin-nav">
            <div class="admin-nav-header">Analytics & Insights</div>
            <a class="admin-nav-link active" data-tab="analytics">
                <i class="fa-solid fa-chart-line"></i>
                <span>Analytics Overview</span>
            </a>

            <div class="admin-nav-header mt-2">Management</div>
            <a class="admin-nav-link" data-tab="users">
                <i class="fa-solid fa-users-gear"></i>
                <span>Manage Users</span>
            </a>
            <a class="admin-nav-link" data-tab="cities">
                <i class="fa-solid fa-city"></i>
                <span>Popular Cities</span>
            </a>
            <a class="admin-nav-link" data-tab="activities">
                <i class="fa-solid fa-ticket"></i>
                <span>Popular Activities</span>
            </a>

            <div class="admin-nav-header mt-2">Navigation</div>
            <a href="../dashboard.php" class="admin-nav-link text-white-50">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Main Site</span>
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar-sm bg-primary text-white">
                    <?= strtoupper(substr($currentAdmin['first_name'] ?? 'A', 0, 1) . substr($currentAdmin['last_name'] ?? 'D', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white text-truncate small fw-bold"><?= htmlspecialchars(($currentAdmin['first_name'] ?? '') . ' ' . ($currentAdmin['last_name'] ?? '')) ?></div>
                    <div class="text-white-50 small" style="font-size: 0.75rem;">Administrator</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ── MAIN CONTENT AREA ─────────────────────────────────────────────────── -->
    <main class="admin-main">
        <!-- ── TOP NAVBAR ──────────────────────────────────────────────────────── -->
        <header class="admin-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none p-2 rounded-3" id="adminSidebarToggle" aria-label="Toggle Sidebar">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-gears text-primary fs-5"></i>
                    <h5 class="fw-bold mb-0 text-dark font-[Outfit] d-none d-sm-block">GlobeTrotter Admin</h5>
                </div>
            </div>

            <!-- Search Bar in Navbar -->
            <div class="admin-search-wrapper d-none d-md-block">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="admin-search-input" id="adminGlobalSearch" placeholder="Search analytics, users, destinations...">
            </div>

            <!-- Navbar Right Actions -->
            <div class="admin-topbar-right">
                <div class="dropdown d-none d-sm-block">
                    <button class="btn admin-action-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-layer-group text-secondary"></i> Group By
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item active" href="#">By Month</a></li>
                        <li><a class="dropdown-item" href="#">By Country</a></li>
                        <li><a class="dropdown-item" href="#">By Category</a></li>
                    </ul>
                </div>

                <div class="dropdown d-none d-sm-block">
                    <button class="btn admin-action-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-arrow-down-wide-short text-secondary"></i> Sort By
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="#" onclick="document.querySelector('[data-sort=times_added]')?.click()">Popularity</a></li>
                        <li><a class="dropdown-item" href="#" onclick="document.querySelector('[data-sort=created_at]')?.click()">Recent Date</a></li>
                    </ul>
                </div>

                <!-- Admin Profile Dropdown -->
                <div class="dropdown">
                    <div class="admin-user-avatar" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                        <?= strtoupper(substr($currentAdmin['first_name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-md border-0 py-2" style="border-radius: 12px; min-width: 200px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark"><?= htmlspecialchars(($currentAdmin['first_name'] ?? '') . ' ' . ($currentAdmin['last_name'] ?? '')) ?></div>
                            <div class="small text-muted text-truncate"><?= htmlspecialchars($currentAdmin['email'] ?? '') ?></div>
                        </li>
                        <li><a class="dropdown-item py-2" href="../profile.php"><i class="fa-solid fa-user-circle me-2 text-primary"></i>Profile</a></li>
                        <li><a class="dropdown-item py-2" href="../dashboard.php"><i class="fa-solid fa-gauge-high me-2 text-success"></i>Back to Site</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- ── TAB CONTENT CONTAINERS ─────────────────────────────────────────── -->
        <div class="admin-content">

            <!-- ══════════════════════════════════════════════════════════════════════
                 TAB 4: USER TRENDS AND ANALYTICS (Overview Dashboard)
                 ══════════════════════════════════════════════════════════════════════ -->
            <div class="admin-tab-pane active" id="tab-analytics">
                <!-- Summary Metric Cards (4 in a Row) -->
                <div class="row g-4 mb-4">
                    <!-- Card 1: Total Users -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="metric-card metric-card-primary">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="metric-label">Total Users</div>
                                    <div class="metric-value count-up" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div>
                                </div>
                                <div class="metric-icon-wrap metric-icon-primary">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="metric-badge metric-badge-success">
                                    <i class="fa-solid fa-arrow-trend-up"></i> +<?= $usersThisMonth ?>
                                </span>
                                <span class="small text-muted">new this month</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Total Trips Created -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="metric-card metric-card-success">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="metric-label">Trips Created</div>
                                    <div class="metric-value count-up" data-target="<?= $totalTrips ?>"><?= $totalTrips ?></div>
                                </div>
                                <div class="metric-icon-wrap metric-icon-success">
                                    <i class="fa-solid fa-suitcase-rolling"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="metric-badge metric-badge-success">
                                    <i class="fa-solid fa-arrow-trend-up"></i> +<?= $tripsThisMonth ?>
                                </span>
                                <span class="small text-muted">planned this month</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Total Activities Planned -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="metric-card metric-card-warning">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="metric-label">Activities Planned</div>
                                    <div class="metric-value count-up" data-target="<?= $totalActivities ?>"><?= $totalActivities ?></div>
                                </div>
                                <div class="metric-icon-wrap metric-icon-warning">
                                    <i class="fa-solid fa-map-pin"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="metric-badge metric-badge-info">
                                    <i class="fa-solid fa-calendar-check"></i> +<?= $activitiesThisMonth ?>
                                </span>
                                <span class="small text-muted">scheduled this month</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Total Community Posts -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="metric-card metric-card-purple">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="metric-label">Community Posts</div>
                                    <div class="metric-value count-up" data-target="<?= $totalPosts ?>"><?= $totalPosts ?></div>
                                </div>
                                <div class="metric-icon-wrap metric-icon-purple">
                                    <i class="fa-solid fa-comments"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="metric-badge metric-badge-info">
                                    <i class="fa-solid fa-share-nodes"></i> +<?= $postsThisMonth ?>
                                </span>
                                <span class="small text-muted">stories shared this month</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1: Line Chart (Registrations) & React Pie Chart (Trip Status) -->
                <div class="row g-4 mb-4">
                    <!-- Chart A: User Growth Line Chart -->
                    <div class="col-lg-8">
                        <div class="admin-card h-100 mb-0">
                            <div class="admin-card-header">
                                <h6 class="admin-card-title">
                                    <i class="fa-solid fa-chart-line text-primary"></i>
                                    <span>New User Registrations Over Time (Last 12 Months)</span>
                                </h6>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Monthly Trend</span>
                            </div>
                            <div class="p-4">
                                <div class="chart-container-bounded">
                                    <canvas id="userRegistrationLineChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart B: Trip Status Distribution (React 18 + Recharts) -->
                    <div class="col-lg-4">
                        <div class="admin-card h-100 mb-0">
                            <div class="admin-card-header">
                                <h6 class="admin-card-title">
                                    <i class="fa-solid fa-chart-pie text-success"></i>
                                    <span>Trip Status Distribution</span>
                                </h6>
                            </div>
                            <div class="p-4">
                                <div id="admin-pie-root" class="chart-container-bounded"></div>
                                <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
                                    <div class="d-flex align-items-center gap-1.5 small text-secondary">
                                        <span class="rounded-circle" style="width: 8px; height: 8px; background: #3b82f6;"></span> Upcoming: <strong><?= $statusCounts['upcoming'] ?></strong>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 small text-secondary">
                                        <span class="rounded-circle" style="width: 8px; height: 8px; background: #10b981;"></span> Ongoing: <strong><?= $statusCounts['ongoing'] ?></strong>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 small text-secondary">
                                        <span class="rounded-circle" style="width: 8px; height: 8px; background: #8b5cf6;"></span> Completed: <strong><?= $statusCounts['completed'] ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2: Trips Created Per Month (Public vs Private) -->
                <div class="admin-card mb-0">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title">
                            <i class="fa-solid fa-chart-simple text-warning"></i>
                            <span>Trips Created Per Month — Public vs. Private (Last 6 Months)</span>
                        </h6>
                    </div>
                    <div class="p-4">
                        <div class="chart-container-bounded">
                            <canvas id="tripsPublicPrivateBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════
                 TAB 1: MANAGING USERS
                 ══════════════════════════════════════════════════════════════════════ -->
            <div class="admin-tab-pane" id="tab-users">
                <div class="admin-card mb-0">
                    <div class="admin-card-header flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="admin-card-title mb-0">
                                <i class="fa-solid fa-users text-primary"></i>
                                <span>Platform Users</span>
                            </h5>
                            <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold">
                                Total Users: <span id="totalUsersCount"><?= count($usersList) ?></span>
                            </span>
                        </div>

                        <!-- Table Controls -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="position-relative" style="width: 220px;">
                                <i class="fa-solid fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                <input type="text" id="userSearchInput" class="form-control form-control-sm ps-5 rounded-pill" placeholder="Filter users...">
                            </div>
                            <select id="userRoleFilter" class="form-select form-select-sm rounded-pill" style="width: 130px;">
                                <option value="">All Roles</option>
                                <option value="admin">Admins</option>
                                <option value="user">Regular Users</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="id"># <i class="fa-solid fa-sort"></i></th>
                                    <th>Avatar</th>
                                    <th class="sortable" data-sort="name">Full Name <i class="fa-solid fa-sort"></i></th>
                                    <th class="sortable" data-sort="email">Email <i class="fa-solid fa-sort"></i></th>
                                    <th class="sortable" data-sort="location">City / Country <i class="fa-solid fa-sort"></i></th>
                                    <th class="sortable" data-sort="created_at">Joined Date <i class="fa-solid fa-sort"></i></th>
                                    <th class="sortable text-center" data-sort="trips_count">Total Trips <i class="fa-solid fa-sort"></i></th>
                                    <th class="sortable" data-sort="role">Role <i class="fa-solid fa-sort"></i></th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Populated dynamically by admin.js with sorting & search -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">Showing up to 20 users per page</div>
                        <nav id="usersPagination"></nav>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════
                 TAB 2: POPULAR CITIES
                 ══════════════════════════════════════════════════════════════════════ -->
            <div class="admin-tab-pane" id="tab-cities">
                <div class="admin-card mb-4">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title mb-0">
                            <i class="fa-solid fa-city text-primary"></i>
                            <span>City Popularity Rankings</span>
                        </h5>
                        <span class="badge bg-light text-dark border"><?= count($popularCities) ?> destinations indexed</span>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Rank</th>
                                    <th>City Name</th>
                                    <th>Country</th>
                                    <th>Region</th>
                                    <th class="text-center">Times Added to Trips</th>
                                    <th class="text-center">Activities Count</th>
                                    <th class="text-end">Avg Cost Index</th>
                                    <th class="text-end">Popularity Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($popularCities)): ?>
                                    <tr><td colspan="8" class="text-center py-5 text-muted">No cities added yet.</td></tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($popularCities as $city): ?>
                                        <tr>
                                            <td>
                                                <span class="rank-badge <?= $rank <= 3 ? "rank-$rank" : 'rank-other' ?>">
                                                    <?= $rank++ ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($city['name']) ?></td>
                                            <td><span class="text-secondary"><?= htmlspecialchars($city['country']) ?></span></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($city['region'] ?? 'Global') ?></span></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1">
                                                    <?= $city['times_added'] ?> <?= $city['times_added'] == 1 ? 'stop' : 'stops' ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-medium text-secondary"><?= $city['activities_count'] ?></td>
                                            <td class="text-end fw-semibold text-dark">$<?= number_format((float)$city['cost_index'], 2) ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center gap-2">
                                                    <span class="fw-bold text-primary"><?= (int)$city['popularity_score'] ?>/100</span>
                                                    <div class="progress" style="width: 60px; height: 6px;">
                                                        <div class="progress-bar bg-primary" style="width: <?= min(100, (int)$city['popularity_score']) ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top 8 Cities Horizontal Bar Chart -->
                <div class="admin-card mb-0">
                    <div class="admin-card-header">
                        <h6 class="admin-card-title">
                            <i class="fa-solid fa-chart-column text-primary"></i>
                            <span>Top 8 Cities by Occurrence in Trip Itineraries</span>
                        </h6>
                    </div>
                    <div class="p-4">
                        <div class="chart-container-bounded">
                            <canvas id="citiesHorizontalBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════
                 TAB 3: POPULAR ACTIVITIES
                 ══════════════════════════════════════════════════════════════════════ -->
            <div class="admin-tab-pane" id="tab-activities">
                <div class="row g-4 mb-4">
                    <!-- Activities Table -->
                    <div class="col-lg-8">
                        <div class="admin-card h-100 mb-0">
                            <div class="admin-card-header">
                                <h5 class="admin-card-title mb-0">
                                    <i class="fa-solid fa-ticket text-success"></i>
                                    <span>Top Scheduled Activities</span>
                                </h5>
                                <span class="badge bg-light text-dark border"><?= count($popularActivities) ?> activities</span>
                            </div>

                            <div class="table-responsive">
                                <table class="admin-table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">Rank</th>
                                            <th>Activity Name</th>
                                            <th>City</th>
                                            <th>Category</th>
                                            <th class="text-center">Times Added</th>
                                            <th class="text-end">Avg Cost</th>
                                            <th class="text-end">Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($popularActivities)): ?>
                                            <tr><td colspan="7" class="text-center py-5 text-muted">No activities registered yet.</td></tr>
                                        <?php else: ?>
                                            <?php $actRank = 1; foreach (array_slice($popularActivities, 0, 15) as $act): ?>
                                                <tr>
                                                    <td>
                                                        <span class="rank-badge <?= $actRank <= 3 ? "rank-$actRank" : 'rank-other' ?>">
                                                            <?= $actRank++ ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold text-dark"><?= htmlspecialchars($act['name']) ?></td>
                                                    <td class="text-secondary small"><?= htmlspecialchars($act['city_name']) ?></td>
                                                    <td>
                                                        <span class="category-badge cat-<?= htmlspecialchars(strtolower($act['category'])) ?>">
                                                            <?= htmlspecialchars($act['category']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success-subtle text-success fw-bold px-2.5 py-1">
                                                            <?= $act['times_added'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end fw-semibold text-dark">$<?= number_format((float)$act['cost'], 2) ?></td>
                                                    <td class="text-end text-muted small"><?= (float)$act['duration_hours'] ?> hrs</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Category Donut Chart -->
                    <div class="col-lg-4">
                        <div class="admin-card h-100 mb-0">
                            <div class="admin-card-header">
                                <h6 class="admin-card-title">
                                    <i class="fa-solid fa-chart-pie text-purple"></i>
                                    <span>Activities by Category</span>
                                </h6>
                            </div>
                            <div class="p-4">
                                <div class="chart-container-bounded">
                                    <canvas id="activitiesCategoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ── MODAL 1: VIEW USER PROFILE DETAILS ────────────────────────────────────── -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="viewUserModalLabel">
                    <i class="fa-solid fa-id-card text-primary me-2"></i>User Profile & Trip History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="viewUserModalContent">
                <!-- Loaded via AJAX -->
            </div>
            <div class="modal-footer border-top py-2.5 bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-secondary px-4 rounded-3 btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL 2: DELETE USER CONFIRMATION ────────────────────────────────────── -->
<div class="modal fade" id="deleteUserConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body p-4 text-center">
                <div class="w-16 h-16 bg-danger-subtle text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Delete User Account?</h5>
                <p class="text-secondary small mb-3">
                    Are you sure you want to delete <strong id="deleteUserNameSpan" class="text-dark"></strong>? 
                    This will permanently remove the user and all their associated trips, stops, and activities.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteUserBtn" class="btn btn-danger px-4 rounded-3 fw-semibold">Delete User</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Embedded Data for JavaScript Controllers -->
<script>
    window.CURRENT_ADMIN_ID = <?= (int) $currentAdminId ?>;
    window.USERS_LIST = <?= json_encode($usersList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.TOP_CITIES_LABELS = <?= json_encode($topCitiesLabels) ?>;
    window.TOP_CITIES_DATA = <?= json_encode($topCitiesData) ?>;
    window.ACTIVITY_CATS_LABELS = <?= json_encode($activityCatsLabels) ?>;
    window.ACTIVITY_CATS_DATA = <?= json_encode($activityCatsData) ?>;
    window.USER_TRENDS_LABELS = <?= json_encode($userTrendsLabels) ?>;
    window.USER_TRENDS_DATA = <?= json_encode($userTrendsData) ?>;
    window.ADMIN_PIE_DATA = <?= json_encode($statusCounts) ?>;
    window.TRIPS_MONTHLY_LABELS = <?= json_encode($tripsMonthlyLabels) ?>;
    window.TRIPS_MONTHLY_PUBLIC = <?= json_encode($tripsMonthlyPublic) ?>;
    window.TRIPS_MONTHLY_PRIVATE = <?= json_encode($tripsMonthlyPrivate) ?>;
</script>

<!-- Admin JS Controller -->
<script src="../assets/js/admin.js"></script>

<!-- React / Recharts Admin Pie Component -->
<script type="text/babel" src="../assets/js/admin-pie.js"></script>

</body>
</html>
