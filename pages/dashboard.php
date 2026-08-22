<?php
require_once __DIR__ . '/../includes/auth.php';

$userId = require_login_page();
$user = current_user();

$upcomingStmt = $pdo->prepare('
    SELECT t.id, t.trip_name AS name, t.start_date, t.end_date, t.cover_photo,
           COUNT(DISTINCT s.city_id) AS destination_count
    FROM trips t
    LEFT JOIN trip_stops s ON s.trip_id = t.id
    WHERE t.user_id = ? AND t.end_date >= CURRENT_DATE
    GROUP BY t.id
    ORDER BY t.start_date ASC
    LIMIT 5
');
$upcomingStmt->execute([$userId]);
$upcomingTrips = $upcomingStmt->fetchAll();

$recentStmt = $pdo->prepare('
    SELECT t.id, t.trip_name AS name, t.start_date, t.end_date, t.cover_photo
    FROM trips t
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
');
$recentStmt->execute([$userId]);
$recentTrips = $recentStmt->fetchAll();

$recommendedStmt = $pdo->prepare('
    SELECT c.id, c.name, c.country, c.cost_index, c.popularity_score AS popularity, c.image_url
    FROM cities c
    WHERE c.id NOT IN (
        SELECT DISTINCT s.city_id
        FROM trip_stops s
        JOIN trips t ON t.id = s.trip_id
        WHERE t.user_id = ?
    )
    ORDER BY c.popularity_score DESC
    LIMIT 6
');
$recommendedStmt->execute([$userId]);
$recommendedCities = $recommendedStmt->fetchAll();

$budgetStmt = $pdo->prepare('
    SELECT
        (
            SELECT COALESCE(SUM(b.transport_budget + b.stay_budget + b.activities_budget + b.meals_budget + b.misc_budget), 0)
            FROM trip_budget b
            JOIN trips t2 ON t2.id = b.trip_id
            WHERE t2.user_id = ?
        ) AS manual_total,
        (
            SELECT COALESCE(SUM(COALESCE(sa.custom_cost, a.cost)), 0)
            FROM trip_activities sa
            JOIN activities a ON a.id = sa.activity_id
            JOIN trip_stops s ON s.id = sa.trip_stop_id
            JOIN trips t3 ON t3.id = s.trip_id
            WHERE t3.user_id = ?
        ) AS activities_total
');
$budgetStmt->execute([$userId, $userId]);
$budgetRow = $budgetStmt->fetch();
$budgetHighlights = [
    'total_planned' => round((float) $budgetRow['manual_total'] + (float) $budgetRow['activities_total'], 2),
    'trip_count' => (int) $pdo->query('SELECT COUNT(*) FROM trips WHERE user_id = ' . (int) $userId)->fetchColumn(),
];

$firstName = htmlspecialchars($user['first_name'] ?? 'Traveler');

$statsStmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(CASE WHEN end_date >= CURRENT_DATE THEN 1 ELSE 0 END) AS upcoming, SUM(CASE WHEN end_date < CURRENT_DATE THEN 1 ELSE 0 END) AS completed FROM trips WHERE user_id = ?');
$statsStmt->execute([$userId]);
$statsRow = $statsStmt->fetch();
$stats = [
    'total_trips' => (int) $statsRow['total'],
    'upcoming_count' => (int) $statsRow['upcoming'],
    'completed_count' => (int) $statsRow['completed']
];

$countriesStmt = $pdo->prepare('SELECT COUNT(DISTINCT c.country) FROM trip_stops s JOIN trips t ON t.id = s.trip_id JOIN cities c ON c.id = s.city_id WHERE t.user_id = ?');
$countriesStmt->execute([$userId]);
$countriesVisited = (int) $countriesStmt->fetchColumn();

$regions = [
    ['key' => 'Europe', 'label' => 'Europe', 'class' => 'bg-europe'],
    ['key' => 'Asia', 'label' => 'Asia', 'class' => 'bg-asia'],
    ['key' => 'North America', 'label' => 'North America', 'class' => 'bg-na'],
    ['key' => 'South America', 'label' => 'South America', 'class' => 'bg-sa'],
    ['key' => 'Africa', 'label' => 'Africa', 'class' => 'bg-africa'],
    ['key' => 'Oceania', 'label' => 'Oceania', 'class' => 'bg-oceania'],
];
$regionCounts = [];

$pageTitle = 'Dashboard — GlobeTrotter';
$loadDashboardCSS = true;

require_once __DIR__ . '/../includes/header.php';
?>
<!-- ======================================================================
     HERO SECTION
     ====================================================================== -->
<section class="gt-hero" id="hero" aria-label="Welcome banner">
    <div class="gt-hero-content">
        <h1 class="gt-hero-title">
            Welcome back, <span class="name-highlight"><?= $firstName ?>!</span><br>
            Where to next?
        </h1>
        <p class="gt-hero-subtitle">
            <i class="fa-solid fa-location-dot me-1"></i>
            Discover, plan, and live your next great adventure
        </p>
        <div class="hero-ctas">
            <a href="create-trip.php" class="btn-hero-primary" id="heroCreateTrip">
                <i class="fa-solid fa-plus"></i> Plan a New Trip
            </a>
            <a href="city-search.php" class="btn-hero-outline" id="heroExplore">
                <i class="fa-solid fa-compass"></i> Explore Destinations
            </a>
        </div>
    </div>
</section>

<!-- ======================================================================
     STICKY SEARCH BAR
     ====================================================================== -->
<div class="gt-search-bar" id="stickySearchBar" role="search">
    <div class="container">
        <div class="search-bar-inner">

            <!-- Main search -->
            <div class="main-search-group">
                <form action="city-search.php" method="GET" id="mainSearchForm">
                    <input type="text"
                           class="main-search-input"
                           id="mainSearchInput"
                           name="q"
                           placeholder="Search destinations, activities, or your trips…"
                           autocomplete="off"
                           aria-label="Search destinations">
                    <button type="submit" class="main-search-btn" aria-label="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
                <!-- Autocomplete dropdown -->
                <div class="main-autocomplete" id="mainAutocomplete" role="listbox"></div>
            </div>

            <!-- Group By -->
            <select class="bar-select" id="barGroupBy" aria-label="Group By">
                <option value="">Group By</option>
                <option value="region">By Region</option>
                <option value="date">By Date</option>
                <option value="status">By Status</option>
            </select>

            <!-- Filter button -->
            <button class="btn-filter" id="barFilterBtn"
                    data-bs-toggle="modal" data-bs-target="#filterModal"
                    aria-label="Open filters">
                <i class="fa-solid fa-sliders"></i>
                Filter
            </button>

            <!-- Sort By -->
            <select class="bar-select" id="barSortBy" aria-label="Sort By">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="az">A–Z</option>
                <option value="budget">Budget ↑</option>
            </select>

        </div>
    </div>
</div>

<!-- ======================================================================
     STATS BAR
     ====================================================================== -->
<section class="gt-stats-bar" aria-label="Trip statistics">
    <div class="container">
        <div class="row g-3">

            <div class="col-6 col-md-3 fade-up fade-up-1">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <div>
                        <div class="stat-num" id="statTotal"><?= (int)$stats['total_trips'] ?></div>
                        <div class="stat-label">Total Trips</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3 fade-up fade-up-2">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fa-solid fa-earth-americas"></i>
                    </div>
                    <div>
                        <div class="stat-num" id="statCountries"><?= $countriesVisited ?></div>
                        <div class="stat-label">Countries Visited</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3 fade-up fade-up-3">
                <div class="stat-card">
                    <div class="stat-icon amber">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div>
                        <div class="stat-num" id="statUpcoming"><?= (int)$stats['upcoming_count'] ?></div>
                        <div class="stat-label">Upcoming Trips</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3 fade-up fade-up-4">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-num" id="statCompleted"><?= (int)$stats['completed_count'] ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ======================================================================
     REGION CARDS
     ====================================================================== -->
<section class="regions-section" aria-labelledby="regionsHeading">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" id="regionsHeading">
                <i class="fa-solid fa-compass"></i> Explore by Region
            </h2>
        </div>

        <div class="regions-scroll-wrapper" role="list" aria-label="Regions">
            <div class="regions-row">
                <?php foreach ($regions as $region):
                    $count   = $regionCounts[$region['key']] ?? 0;
                    $slug    = urlencode($region['key']);
                ?>
                <a href="city-search.php?region=<?= $slug ?>"
                   class="region-card <?= htmlspecialchars($region['class']) ?>"
                   role="listitem"
                   aria-label="<?= htmlspecialchars($region['label']) ?>, <?= $count ?> cities"
                   id="region-<?= htmlspecialchars(strtolower(str_replace(' ', '-', $region['key']))) ?>">
                    <div class="region-card-bg"></div>
                    <div class="region-card-overlay"></div>
                    <div class="region-card-content">
                        <span class="region-name"><?= htmlspecialchars($region['label']) ?></span>
                        <span class="region-count">
                            <i class="fa-solid fa-city"></i>
                            <?= $count ?> <?= $count === 1 ? 'city' : 'cities' ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================================
     YOUR TRIPS
     ====================================================================== -->
<section class="trips-section" aria-labelledby="tripsHeading">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" id="tripsHeading">
                <i class="fa-solid fa-suitcase"></i> Your Trips
            </h2>
            <?php if (!empty($userTrips)): ?>
            <a href="my-trips.php" class="section-link" id="viewAllTrips">
                View All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($userTrips)): ?>

        <div class="row g-4" id="tripsGrid">
            <?php foreach ($userTrips as $i => $trip):
                $gradClass  = $gradients[$i % 4];
                $gradIcon   = $gradIcons[$i % 4];

                // Status badge styling
                $badgeClass = match($trip['status']) {
                    'completed' => 'badge bg-success',
                    'ongoing'   => 'badge bg-warning text-dark',
                    default     => 'badge bg-info text-dark',
                };

                $dateRange = '';
                if ($trip['start_date'] && $trip['end_date']) {
                    $dateRange = formatDate($trip['start_date'], 'M j') . ' – ' . formatDate($trip['end_date'], 'M j, Y');
                }

                $stops = (int) $trip['stop_count'];
            ?>
            <div class="col-12 col-sm-6 col-lg-3 fade-up fade-up-<?= ($i % 4) + 1 ?>">
                <a href="my-trips.php?trip_id=<?= (int)$trip['id'] ?>"
                   class="trip-card"
                   id="tripCard<?= (int)$trip['id'] ?>"
                   aria-label="<?= htmlspecialchars($trip['trip_name']) ?>">

                    <div class="trip-card-cover">
                        <?php if (!empty($trip['cover_photo'])): ?>
                            <img src="<?= SITE_URL ?>/assets/uploads/covers/<?= htmlspecialchars($trip['cover_photo']) ?>"
                                 alt="<?= htmlspecialchars($trip['trip_name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="trip-cover-gradient <?= $gradClass ?>">
                                <i class="fa-solid <?= $gradIcon ?>"></i>
                            </div>
                        <?php endif; ?>
                        <span class="trip-status-badge <?= $badgeClass ?>">
                            <?= htmlspecialchars(ucfirst($trip['status'])) ?>
                        </span>
                    </div>

                    <div class="trip-card-body">
                        <div class="trip-name"><?= htmlspecialchars($trip['trip_name']) ?></div>

                        <?php if ($dateRange): ?>
                        <div class="trip-dates">
                            <i class="fa-regular fa-calendar"></i>
                            <?= htmlspecialchars($dateRange) ?>
                        </div>
                        <?php endif; ?>

                        <div class="trip-stops">
                            <i class="fa-solid fa-map-pin"></i>
                            <?= $stops ?> <?= $stops === 1 ? 'stop' : 'stops' ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Plan a Trip strip -->
        <div class="plan-cta-strip mt-4">
            <a href="create-trip.php" class="btn btn-outline-primary" id="planTripCta">
                <i class="fa-solid fa-plus me-1"></i> Plan a New Trip
            </a>
        </div>

        <?php else: ?>
        <!-- Empty state -->
        <div class="trips-empty" id="tripsEmpty">
            <div class="empty-illustration" aria-hidden="true">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <h5>No trips yet!</h5>
            <p>Start planning your first adventure and make memories that last a lifetime.</p>
            <a href="create-trip.php" class="btn btn-primary mt-2" id="startFirstTrip">
                <i class="fa-solid fa-plus me-1"></i> Plan Your First Trip
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- ======================================================================
     FILTER MODAL
     ====================================================================== -->
<div class="modal fade" id="filterModal" tabindex="-1"
     aria-labelledby="filterModalLabel" aria-hidden="true"
     role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="fa-solid fa-sliders me-2 text-primary"></i>Filter Trips
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">

                <!-- Status filter -->
                <div class="mb-4">
                    <div class="filter-section-label">Status</div>
                    <div class="filter-pills" id="statusPills">
                        <span class="filter-pill" data-filter="status" data-value="upcoming">Upcoming</span>
                        <span class="filter-pill" data-filter="status" data-value="ongoing">Ongoing</span>
                        <span class="filter-pill" data-filter="status" data-value="completed">Completed</span>
                    </div>
                </div>

                <!-- Region filter -->
                <div class="mb-4">
                    <div class="filter-section-label">Region</div>
                    <div class="filter-pills" id="regionPills">
                        <?php foreach ($regions as $r): ?>
                        <span class="filter-pill" data-filter="region"
                              data-value="<?= htmlspecialchars($r['key']) ?>">
                            <?= htmlspecialchars($r['label']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Budget range -->
                <div class="mb-3">
                    <div class="filter-section-label">
                        Budget Range
                        <span class="text-primary ms-1 fw-normal" id="budgetLabel">$0 – $10,000</span>
                    </div>
                    <div class="budget-range">
                        <input type="range" id="budgetRange" min="0" max="10000" step="500" value="10000"
                               aria-label="Maximum budget" class="w-100">
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                    Clear All
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="applyFilters"
                        data-bs-dismiss="modal">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================
     FLOATING ACTION BUTTON
     ====================================================================== -->
<a href="create-trip.php" class="gt-fab" id="fabNewTrip"
   title="Plan a New Trip" aria-label="Plan a new trip">
    <i class="fa-solid fa-plus"></i>
</a>

<!-- ======================================================================
     SCRIPTS
     ====================================================================== -->
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    const SITE_URL = '<?= SITE_URL ?>';

    /* ── User avatar dropdown ────────────────────────────────────────── */
    const avatarBtn    = document.getElementById('navAvatarBtn');
    const userDropdown = document.getElementById('navUserDropdown');

    if (avatarBtn && userDropdown) {
        avatarBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = userDropdown.classList.toggle('open');
            avatarBtn.setAttribute('aria-expanded', open);
        });

        document.addEventListener('click', function () {
            userDropdown.classList.remove('open');
            avatarBtn.setAttribute('aria-expanded', 'false');
        });
    }

    /* ── Generic autocomplete helper ────────────────────────────────── */
    function initAutocomplete(inputEl, dropdownEl, containerClass) {
        if (!inputEl || !dropdownEl) return;

        let debounceTimer;

        inputEl.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();

            if (q.length < 2) {
                dropdownEl.innerHTML = '';
                dropdownEl.classList.remove('visible');
                return;
            }

            debounceTimer = setTimeout(async function () {
                try {
                    const res  = await fetch(`${SITE_URL}/api/cities.php?search=${encodeURIComponent(q)}&limit=5`);
                    const data = await res.json();

                    if (!data.success || !data.data.length) {
                        dropdownEl.innerHTML = '';
                        dropdownEl.classList.remove('visible');
                        return;
                    }

                    dropdownEl.innerHTML = data.data.slice(0, 5).map(city => {
                        const name    = city.name.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        const country = (city.country || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        const icon    = containerClass === 'navbar'
                            ? `<i class="fa-solid fa-location-dot city-icon"></i>`
                            : `<i class="fa-solid fa-location-dot ac-icon"></i>`;
                        const metaClass = containerClass === 'navbar' ? 'city-meta' : 'ac-country';
                        const itemClass = containerClass === 'navbar' ? 'autocomplete-item' : 'main-autocomplete-item';

                        return `<a href="${SITE_URL}/city-search.php?city_id=${encodeURIComponent(city.id)}"
                                   class="${itemClass}" role="option"
                                   aria-label="${name}, ${country}">
                                   ${icon}
                                   <span>
                                       <strong>${name}</strong>
                                       <span class="${metaClass}">${country}</span>
                                   </span>
                               </a>`;
                    }).join('');

                    dropdownEl.classList.add('visible');
                } catch (err) {
                    console.error('Autocomplete error:', err);
                }
            }, 280);
        });

        // Hide on outside click
        document.addEventListener('click', function (e) {
            if (!inputEl.contains(e.target) && !dropdownEl.contains(e.target)) {
                dropdownEl.classList.remove('visible');
            }
        });
    }

    // Init both search bars
    initAutocomplete(
        document.getElementById('navSearchInput'),
        document.getElementById('navAutocomplete'),
        'navbar'
    );
    initAutocomplete(
        document.getElementById('mainSearchInput'),
        document.getElementById('mainAutocomplete'),
        'main'
    );

    /* ── Sticky search bar shadow on scroll ─────────────────────────── */
    const stickyBar = document.getElementById('stickySearchBar');
    if (stickyBar) {
        window.addEventListener('scroll', function () {
            stickyBar.style.boxShadow = window.scrollY > 100
                ? '0 6px 24px rgba(0,0,0,0.14)'
                : '0 4px 16px rgba(0,0,0,0.06)';
        }, { passive: true });
    }

    /* ── Filter pills toggle ─────────────────────────────────────────── */
    document.querySelectorAll('.filter-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            // For status, allow only one active at a time within the group
            const filter = this.dataset.filter;
            const siblings = document.querySelectorAll(`.filter-pill[data-filter="${filter}"]`);
            siblings.forEach(s => s.classList.remove('active'));
            this.classList.toggle('active');
        });
    });

    /* ── Budget range label ──────────────────────────────────────────── */
    const budgetRange = document.getElementById('budgetRange');
    const budgetLabel = document.getElementById('budgetLabel');
    if (budgetRange && budgetLabel) {
        budgetRange.addEventListener('input', function () {
            budgetLabel.textContent = '$0 – $' + parseInt(this.value).toLocaleString();
        });
    }

    /* ── Clear filters button ────────────────────────────────────────── */
    const clearBtn = document.getElementById('clearFilters');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            document.querySelectorAll('.filter-pill.active').forEach(p => p.classList.remove('active'));
            if (budgetRange) { budgetRange.value = 10000; }
            if (budgetLabel) { budgetLabel.textContent = '$0 – $10,000'; }
        });
    }

    /* ── Animate stat counters on load ──────────────────────────────── */
    function animateCount(el) {
        if (!el) return;
        const target = parseInt(el.textContent, 10);
        if (isNaN(target) || target === 0) return;
        let current  = 0;
        const step   = Math.ceil(target / 30);
        const timer  = setInterval(function () {
            current = Math.min(current + step, target);
            el.textContent = current;
            if (current >= target) clearInterval(timer);
        }, 30);
    }

    window.addEventListener('load', function () {
        ['statTotal', 'statCountries', 'statUpcoming', 'statCompleted'].forEach(function (id) {
            animateCount(document.getElementById(id));
        });
    });

})();
</script>

</body>
</html>
