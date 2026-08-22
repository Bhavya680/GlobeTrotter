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
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path fill='%232563eb' d='M352 256c0 22.2-1.2 43.6-3.3 64H163.3c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64h185.4c2.2 20.4 3.3 41.8 3.3 64zm28.8-64h76.8C439.1 144.1 392.6 104 334.8 89.2c22.6 28.5 39.4 64.4 46 102.8zm-19.2 64c0-22.3-3.6-43.8-10.3-64H160.7c-6.7 20.2-10.3 41.7-10.3 64s3.6 43.8 10.3 64h190.6c6.7-20.2 10.3-41.7 10.3-64zm-6.8 166.8c-6.6 38.4-23.4 74.3-46 102.8 57.8-14.8 104.3-54.9 122.8-102.8h-76.8zm-98.8 91.7c-17.5-27-29.5-62.5-34.4-101.7H290.4c-4.9 39.2-16.9 74.7-34.4 101.7zM256 0C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0z'/></svg>">
    <!-- CSRF Token Meta Tag -->
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Dashboard CSS (contains global navbar styling) -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/dashboard.css">
    <!-- Trips CSS (create-trip & my-trips) -->
    <?php if (isset($loadTripsCSS) && $loadTripsCSS): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/trips.css">
    <?php endif; ?>
    <!-- Per-page extra CSS -->
    <?php if (!empty($extraCSS)): ?>
    <?php foreach ((array)$extraCSS as $cssHref): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref) ?>">
    <?php endforeach; ?>
    <?php endif; ?>
    <!-- Per-page extra head content (scripts, meta, etc.) -->
    <?php if (!empty($extraHead)) echo $extraHead; ?>
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

<?php if (isLoggedIn() && empty($hideNavbar)): ?>
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

            <!-- Quick Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-2 d-none d-xl-flex align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1.5 text-secondary fw-semibold small rounded-2" href="<?= SITE_URL ?>/dashboard.php">
                        <i class="fa-solid fa-house me-1 text-primary"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1.5 text-secondary fw-semibold small rounded-2" href="<?= SITE_URL ?>/city-search.php">
                        <i class="fa-solid fa-compass me-1 text-success"></i> Explore
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1.5 text-secondary fw-semibold small rounded-2" href="<?= SITE_URL ?>/calendar-view.php">
                        <i class="fa-regular fa-calendar-days me-1 text-warning"></i> Calendar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1.5 text-secondary fw-semibold small rounded-2" href="<?= SITE_URL ?>/community.php">
                        <i class="fa-solid fa-comments me-1 text-purple"></i> Community
                    </a>
                </li>
            </ul>

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
                        <a href="<?= SITE_URL ?>/calendar-view.php" role="menuitem">
                            <i class="fa-regular fa-calendar-days"></i> Calendar View
                        </a>
                        <a href="<?= SITE_URL ?>/community.php" role="menuitem">
                            <i class="fa-solid fa-comments"></i> Community
                        </a>
                        <a href="<?= SITE_URL ?>/city-search.php" role="menuitem">
                            <i class="fa-solid fa-compass"></i> Explore Cities
                        </a>
                        <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                        <div class="dd-divider"></div>
                        <a href="<?= SITE_URL ?>/admin/index.php" role="menuitem">
                            <i class="fa-solid fa-shield-halved text-primary"></i> Admin Dashboard
                        </a>
                        <?php endif; ?>
                        <div class="dd-divider"></div>
                        <a href="<?= SITE_URL ?>/logout.php" class="logout-link" role="menuitem">
                            <i class="fa-solid fa-right-from-bracket text-danger"></i> Logout
                        </a>
                    </div>
                </div>

            </div><!-- /nav-controls -->
        </div><!-- /navbar-collapse -->
    </div><!-- /container-fluid -->
</nav>
<?php else: ?>
<!-- ===================== SIMPLIFIED GUEST NAVBAR ===================== -->
<nav class="gt-navbar navbar navbar-expand-lg bg-white border-bottom shadow-xs py-2.5" id="guestNavbar">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary font-[Outfit] text-decoration-none" href="<?= SITE_URL ?>/index.php">
            <i class="fa-solid fa-globe brand-icon fs-4"></i>
            <span class="fs-5">GlobeTrotter</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= SITE_URL ?>/city-search.php" class="btn btn-light btn-sm rounded-pill px-3 py-1.5 fw-medium text-secondary me-1 d-none d-sm-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-compass"></i> Explore
            </a>
            <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline-primary btn-sm rounded-pill px-3.5 py-1.5 fw-semibold">
                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Log In
            </a>
            <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary btn-sm rounded-pill px-3.5 py-1.5 fw-semibold shadow-2xs">
                <i class="fa-solid fa-user-plus me-1"></i> Sign Up
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>
