<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Get current user info for navbar
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$userInitial  = $currentUser ? strtoupper(substr($currentUser['first_name'], 0, 1)) : '?';
$userFullName = $currentUser ? htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) : '';
$userRole     = $currentUser ? htmlspecialchars($currentUser['role']) : '';
$userPhoto    = ($currentUser && !empty($currentUser['profile_photo']))
    ? SITE_URL . '/assets/uploads/profiles/' . htmlspecialchars($currentUser['profile_photo'])
    : null;

$pageTitle = isset($pageTitle) ? $pageTitle : SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="GlobeTrotter – Plan your dream trips, explore destinations, and track your adventures around the world.">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts (already imported in style.css but preconnect for perf) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Dashboard CSS (contains global navbar styling) -->
    <?php if (!empty($loadDashboardCSS) || !empty($loadTripsCSS)): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
    <?php endif; ?>
    <!-- Trips CSS (create-trip & my-trips) -->
    <?php if (isset($loadTripsCSS) && $loadTripsCSS): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/trips.css">
    <?php endif; ?>
</head>
<body>

<?php
// Display flash messages
$flash = getFlash();
if ($flash):
?>
<div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:9999;min-width:320px;">
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<?php if (isLoggedIn()): ?>
<!-- ===================== FULL NAVBAR (logged-in) ===================== -->
<nav class="gt-navbar navbar navbar-expand-lg" id="mainNavbar" role="navigation" aria-label="Main navigation">
    <div class="container-fluid px-3">

        <!-- Brand -->
        <a class="navbar-brand" href="<?= SITE_URL ?>/dashboard.php">
            <i class="fa-solid fa-globe brand-icon"></i>
            GlobeTrotter
        </a>

        <!-- Mobile toggler -->
        <button class="navbar-toggler ms-auto me-2" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible content -->
        <div class="collapse navbar-collapse" id="navbarCollapse">

            <!-- Center: Search Bar -->
            <div class="navbar-search-wrap mx-auto">
                <form action="<?= SITE_URL ?>/city-search.php" method="GET" id="navSearchForm" role="search">
                    <input type="text"
                           class="search-input"
                           id="navSearchInput"
                           name="q"
                           placeholder="Search trips and cities…"
                           autocomplete="off"
                           aria-label="Search trips and cities">
                    <button type="submit" class="search-btn" aria-label="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
                <!-- Autocomplete dropdown -->
                <div class="search-autocomplete" id="navAutocomplete" role="listbox" aria-label="Search suggestions"></div>
            </div>

            <!-- Right: Controls + Avatar -->
            <div class="nav-controls ms-lg-auto mt-2 mt-lg-0">

                <!-- Group By -->
                <select class="nav-select" id="navGroupBy" aria-label="Group By" title="Group By">
                    <option value="">Group By</option>
                    <option value="region">By Region</option>
                    <option value="date">By Date</option>
                    <option value="status">By Status</option>
                </select>

                <!-- Filter -->
                <button class="nav-filter-btn" id="navFilterBtn"
                        data-bs-toggle="modal" data-bs-target="#filterModal"
                        aria-label="Filter trips">
                    <i class="fa-solid fa-sliders"></i>
                    Filter
                </button>

                <!-- Sort By -->
                <select class="nav-select" id="navSortBy" aria-label="Sort By" title="Sort By">
                    <option value="newest">Sort: Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="az">A–Z</option>
                    <option value="budget">Budget ↑</option>
                </select>

                <!-- User Avatar + Dropdown -->
                <div class="nav-user-wrap ms-1">
                    <div class="nav-avatar" id="navAvatarBtn" role="button"
                         aria-expanded="false" aria-haspopup="true"
                         title="Account menu">
                        <?php if ($userPhoto): ?>
                            <img src="<?= $userPhoto ?>" alt="<?= $userFullName ?>">
                        <?php else: ?>
                            <?= $userInitial ?>
                        <?php endif; ?>
                    </div>
                    <div class="nav-user-dropdown" id="navUserDropdown" role="menu">
                        <div class="dropdown-header-info">
                            <div class="dh-name"><?= $userFullName ?></div>
                            <div class="dh-role"><?= $userRole ?></div>
                        </div>
                        <a href="<?= SITE_URL ?>/profile.php" role="menuitem">
                            <i class="fa-solid fa-user"></i> Profile
                        </a>
                        <a href="<?= SITE_URL ?>/my-trips.php" role="menuitem">
                            <i class="fa-solid fa-suitcase"></i> My Trips
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="<?= SITE_URL ?>/admin/index.php" role="menuitem">
                            <i class="fa-solid fa-shield-halved"></i> Admin Panel
                        </a>
                        <?php endif; ?>
                        <div class="dd-divider"></div>
                        <a href="<?= SITE_URL ?>/logout.php" class="logout-link" role="menuitem">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>

            </div><!-- /nav-controls -->
        </div><!-- /navbar-collapse -->
    </div><!-- /container-fluid -->
</nav>
<?php endif; ?>
