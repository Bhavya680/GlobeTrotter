<?php
require_once __DIR__ . '/../../includes/auth.php';

$userId = isLoggedIn() ? current_user_id() : null;
$tab = $_GET['tab'] ?? 'cities';
if (!in_array($tab, ['cities', 'activities'])) {
    $tab = 'cities';
}

$pageTitle = ($tab === 'cities' ? 'City Search' : 'Activity Search') . ' — GlobeTrotter';
require_once __DIR__ . '/../../includes/header.php';

// Pre-fetch some data for dropdowns
$trips = [];
if ($userId) {
    $stmt = $pdo->prepare("SELECT id, trip_name, start_date, end_date FROM trips WHERE user_id = ? AND status IN ('upcoming', 'ongoing') ORDER BY start_date ASC");
    $stmt->execute([$userId]);
    $trips = $stmt->fetchAll();
}

$citiesList = [];
$cStmt = $pdo->query("SELECT id, name, country FROM cities ORDER BY name ASC");
$citiesList = $cStmt->fetchAll();

// Pre-fill search inputs from GET params
$q = clean_str($_GET['q'] ?? '');
$region = clean_str($_GET['region'] ?? '');
$cityId = clean_str($_GET['city_id'] ?? '');
?>

<div class="container py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 fw-bold mb-1"><i class="fa-solid fa-magnifying-glass text-primary me-2"></i>Explore</h1>
            <p class="text-muted">Find your next destination or exciting activities for your trip.</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="searchTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tab === 'cities' ? 'active' : '' ?>" id="cities-tab" data-bs-toggle="tab" data-bs-target="#cities-panel" type="button" role="tab" aria-controls="cities-panel" aria-selected="<?= $tab === 'cities' ? 'true' : 'false' ?>">
                <i class="fa-solid fa-city me-1"></i> City Search
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tab === 'activities' ? 'active' : '' ?>" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities-panel" type="button" role="tab" aria-controls="activities-panel" aria-selected="<?= $tab === 'activities' ? 'true' : 'false' ?>">
                <i class="fa-solid fa-person-hiking me-1"></i> Activity Search
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="searchTabsContent">
        <!-- CITIES TAB -->
        <div class="tab-pane fade <?= $tab === 'cities' ? 'show active' : '' ?>" id="cities-panel" role="tabpanel" aria-labelledby="cities-tab">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body bg-light rounded">
                    <form id="citySearchForm" class="row g-3 align-items-end">
                        <div class="col-md-12 col-lg-4">
                            <label class="form-label fw-bold text-muted small">Search Cities</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" name="q" class="form-control border-start-0" placeholder="Search cities, countries..." value="<?= htmlspecialchars($q) ?>">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label fw-bold text-muted small">Region</label>
                            <select name="region" class="form-select">
                                <option value="">All Regions</option>
                                <?php foreach(['Europe', 'Asia', 'North America', 'South America', 'Africa', 'Oceania'] as $r): ?>
                                    <option value="<?= $r ?>" <?= $region === $r ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label fw-bold text-muted small">Cost Max (Index 1-100)</label>
                            <input type="number" name="cost_max" class="form-control" placeholder="Any" min="1" max="100">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label fw-bold text-muted small">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="popularity">Popularity</option>
                                <option value="cost_low">Cost (Low to High)</option>
                                <option value="cost_high">Cost (High to Low)</option>
                                <option value="name">Name (A-Z)</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cities Results Container -->
            <div id="citiesResults" class="row g-4"></div>
            
            <!-- City Pagination -->
            <div id="citiesPagination" class="w-100 mt-4"></div>
            
            <div id="citiesLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <!-- ACTIVITIES TAB -->
        <div class="tab-pane fade <?= $tab === 'activities' ? 'show active' : '' ?>" id="activities-panel" role="tabpanel" aria-labelledby="activities-tab">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body bg-light rounded">
                    <form id="activitySearchForm" class="row g-3 align-items-end">
                        <div class="col-md-12 col-lg-4">
                            <label class="form-label fw-bold text-muted small">Search Activities</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" name="q" class="form-control border-start-0" placeholder="Search activities..." value="<?= htmlspecialchars($tab === 'activities' ? $q : '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label fw-bold text-muted small">City</label>
                            <select name="city_id" class="form-select">
                                <option value="">All Cities</option>
                                <?php foreach($citiesList as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $cityId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ', ' . $c['country']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label fw-bold text-muted small">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach(['sightseeing', 'food', 'adventure', 'culture', 'relaxation', 'other'] as $cat): ?>
                                    <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-1">
                            <label class="form-label fw-bold text-muted small">Max $</label>
                            <input type="number" name="cost_max" class="form-control" placeholder="Any" min="0">
                        </div>
                        <div class="col-md-4 col-lg-1">
                            <label class="form-label fw-bold text-muted small">Max Hrs</label>
                            <select name="duration_max" class="form-select">
                                <option value="">Any</option>
                                <option value="1">1 hr</option>
                                <option value="3">3 hrs</option>
                                <option value="5">5 hrs</option>
                                <option value="24">Full day</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <label class="form-label fw-bold text-muted small">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="name">Name (A-Z)</option>
                                <option value="cost">Cost (Low to High)</option>
                                <option value="duration">Duration (Short to Long)</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activities Results Container -->
            <div id="activitiesResults" class="row g-4"></div>
            
            <!-- Activity Pagination -->
            <div id="activitiesPagination" class="w-100 mt-4"></div>
            
            <div id="activitiesLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add City to Trip Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plane-arrival text-primary me-2"></i>Add to Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCityForm">
                    <input type="hidden" name="city_id" id="addCity_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Trip</label>
                        <select name="trip_id" class="form-select" required>
                            <option value="">-- Choose a trip --</option>
                            <?php foreach ($trips as $t): ?>
                                <option value="<?= $t['id'] ?>" data-start="<?= $t['start_date'] ?>" data-end="<?= $t['end_date'] ?>"><?= htmlspecialchars($t['trip_name']) ?> (<?= $t['start_date'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Arrival Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Departure Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-primary">Add Stop</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Activity to Trip Modal -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-person-hiking text-primary me-2"></i>Add Activity to Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addActivityForm">
                    <input type="hidden" name="activity_id" id="addActivity_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Trip</label>
                        <select id="activityTripSelect" class="form-select" required>
                            <option value="">-- Choose a trip --</option>
                            <?php foreach ($trips as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trip_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Stop (City)</label>
                        <select name="stop_id" id="activityStopSelect" class="form-select" required disabled>
                            <option value="">-- Select trip first --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Scheduled Date</label>
                        <input type="date" name="scheduled_date" id="activityDate" class="form-control" required>
                    </div>
                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-primary">Add Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick View Activity Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0" id="quickViewContent">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>
</div>

<script>
// Expose auth status
const isLoggedIn = <?= $userId ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', () => {
    
    let citiesCurrentPage = 1;
    let activitiesCurrentPage = 1;
    let debounceTimer;

    const cityForm = document.getElementById('citySearchForm');
    const activityForm = document.getElementById('activitySearchForm');

    // Debounce function for inputs
    const attachDebounce = (form, fetchFunc) => {
        form.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchFunc(1);
                }, 400);
            });
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', () => fetchFunc(1));
            }
        });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchFunc(1);
        });
    };

    attachDebounce(cityForm, fetchCities);
    attachDebounce(activityForm, fetchActivities);

    // Initial fetch based on active tab
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || '<?= $tab ?>';
    
    if (initialTab === 'cities') {
        fetchCities();
        // Also fetch activities in background so it's ready
        fetchActivities();
    } else {
        fetchActivities();
        fetchCities();
    }

    // Update URL on tab change
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', (e) => {
            const target = e.target.getAttribute('data-bs-target');
            const tabName = target === '#cities-panel' ? 'cities' : 'activities';
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        });
    });

    async function fetchCities(page = citiesCurrentPage) {
        citiesCurrentPage = page;
        const formData = new FormData(cityForm);
        const params = new URLSearchParams(formData);
        params.append('page', page);
        
        document.getElementById('citiesResults').innerHTML = '';
        document.getElementById('citiesLoading').classList.remove('d-none');
        document.getElementById('citiesPagination').innerHTML = '';

        try {
            const res = await api('GET', `/api/cities.php?${params.toString()}`);
            document.getElementById('citiesLoading').classList.add('d-none');
            
            if (res && res.success) {
                renderCities(res.data.cities);
                renderPagination('citiesPagination', res.data.pagination, fetchCities);
            }
        } catch (err) {
            document.getElementById('citiesLoading').classList.add('d-none');
            toast('Failed to load cities', 'error');
        }
    }

    function renderCities(cities) {
        const container = document.getElementById('citiesResults');
        if (cities.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-city fa-3x text-muted mb-3"></i>
                    <h5>No cities found</h5>
                    <p class="text-muted">Try adjusting your filters.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = cities.map(c => {
            const img = c.image_url || 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=800&q=80';
            return `
                <div class="col-md-6 col-lg-12">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden hover-shadow transition">
                        <div class="row g-0 h-100">
                            <div class="col-md-4" style="min-height: 200px; background-image: url('${img}'); background-size: cover; background-position: center;">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body d-flex flex-column h-100 justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h5 class="fw-bold mb-0 text-dark">${c.name}, ${c.country}</h5>
                                            <span class="badge bg-light text-dark border">Cost Index: ${c.cost_index}</span>
                                        </div>
                                        <span class="text-muted small d-block mb-2"><i class="fa-solid fa-globe me-1"></i>${c.region || 'Global'} &nbsp;&bull;&nbsp; <i class="fa-solid fa-fire text-danger me-1"></i>${c.popularity} Popularity</span>
                                        <p class="text-muted small mb-0">${c.description || 'Popular travel destination.'}</p>
                                    </div>
                                    <div class="d-flex justify-content-end align-items-center mt-3 pt-3 border-top gap-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewCityActivities(${c.id}, '${c.name.replace(/'/g, "\\'")}')">View Activities</button>
                                        <button class="btn btn-sm btn-outline-danger save-city-btn" data-id="${c.id}"><i class="fa-regular fa-heart"></i></button>
                                        <button class="btn btn-sm btn-primary" onclick="openAddCityModal(${c.id})"><i class="fa-solid fa-plus me-1"></i> Add to Trip</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Attach save handlers
        document.querySelectorAll('.save-city-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!isLoggedIn) {
                    window.location.href = 'login.php';
                    return;
                }
                const cityId = this.dataset.id;
                try {
                    const res = await api('POST', '/api/profile.php?action=toggle_saved', { city_id: cityId });
                    if (res && res.success) {
                        const icon = this.querySelector('i');
                        if (res.data.saved) {
                            this.classList.replace('btn-outline-danger', 'btn-danger');
                            icon.classList.replace('fa-regular', 'fa-solid');
                            toast('City saved!', 'success');
                        } else {
                            this.classList.replace('btn-danger', 'btn-outline-danger');
                            icon.classList.replace('fa-solid', 'fa-regular');
                            toast('City removed from saved.', 'info');
                        }
                    }
                } catch (e) {
                    toast('Error saving city', 'error');
                }
            });
        });
    }

    async function fetchActivities(page = activitiesCurrentPage) {
        activitiesCurrentPage = page;
        const formData = new FormData(activityForm);
        const params = new URLSearchParams(formData);
        params.append('page', page);
        
        document.getElementById('activitiesResults').innerHTML = '';
        document.getElementById('activitiesLoading').classList.remove('d-none');
        document.getElementById('activitiesPagination').innerHTML = '';

        try {
            const res = await api('GET', `/api/activities.php?${params.toString()}`);
            document.getElementById('activitiesLoading').classList.add('d-none');
            
            if (res && res.success) {
                renderActivities(res.data.activities);
                renderPagination('activitiesPagination', res.data.pagination, fetchActivities);
            }
        } catch (err) {
            document.getElementById('activitiesLoading').classList.add('d-none');
            toast('Failed to load activities', 'error');
        }
    }

    function renderActivities(activities) {
        const container = document.getElementById('activitiesResults');
        if (activities.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-person-hiking fa-3x text-muted mb-3"></i>
                    <h5>No activities found</h5>
                    <p class="text-muted">Try removing some filters.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = activities.map(a => {
            const img = a.image_url || 'https://images.unsplash.com/photo-1523480717984-24cba35ae1ef?auto=format&fit=crop&w=800&q=80';
            return `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden hover-shadow transition">
                        <div class="position-relative" style="height: 180px; background-image: url('${img}'); background-size: cover; background-position: center;">
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge bg-dark bg-opacity-75 fs-6">$${a.cost}</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">${a.name}</h5>
                                <div class="d-flex justify-content-between text-muted small mb-2">
                                    <span><i class="fa-solid fa-location-dot me-1 text-danger"></i>${a.city_name}</span>
                                    <span><i class="fa-regular fa-clock me-1"></i>${a.duration_hours}h</span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">${a.category.toUpperCase()}</span>
                                <p class="text-muted small mb-0 text-truncate" style="max-height: 40px;">${a.description || 'Enjoy this wonderful activity.'}</p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <button class="btn btn-sm btn-outline-secondary" onclick="openQuickView(${a.id})">Quick View</button>
                                <button class="btn btn-sm btn-primary" onclick="openAddActivityModal(${a.id})"><i class="fa-solid fa-plus me-1"></i> Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderPagination(containerId, pagination, fetchFunc) {
        const el = document.getElementById(containerId);
        if (!el) return;
        if (!pagination || pagination.total_pages <= 1) {
            el.innerHTML = '';
            return;
        }
        
        if (typeof createWatermelonPagination === 'function') {
            createWatermelonPagination('#' + containerId, {
                currentPage: pagination.page,
                totalPages: pagination.total_pages,
                totalItems: pagination.total,
                onPageChange: (p) => {
                    fetchFunc(p);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }
    }

    // Modal handlers
    window.openAddCityModal = (cityId) => {
        if (!isLoggedIn) { window.location.href = 'login.php'; return; }
        document.getElementById('addCity_id').value = cityId;
        const modal = new bootstrap.Modal(document.getElementById('addCityModal'));
        modal.show();
    };

    window.openAddActivityModal = (activityId) => {
        if (!isLoggedIn) { window.location.href = 'login.php'; return; }
        document.getElementById('addActivity_id').value = activityId;
        const modal = new bootstrap.Modal(document.getElementById('addActivityModal'));
        modal.show();
    };

    window.openQuickView = async (activityId) => {
        const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
        const content = document.getElementById('quickViewContent');
        content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        modal.show();
        
        try {
            const res = await api('GET', `/api/activities.php?id=${activityId}`);
            if (res && res.success) {
                const a = res.data;
                const img = a.image_url || 'https://images.unsplash.com/photo-1523480717984-24cba35ae1ef?auto=format&fit=crop&w=1200&q=80';
                content.innerHTML = `
                    <div class="row g-0 mx-n3 mt-n3 mb-3">
                        <div class="col-12" style="height: 300px; background-image: url('${img}'); background-size: cover; background-position: center;"></div>
                    </div>
                    <div class="px-2">
                        <h3 class="fw-bold text-dark">${a.name}</h3>
                        <div class="d-flex gap-3 text-muted mb-3">
                            <span><i class="fa-solid fa-location-dot me-1"></i>${a.city_name}, ${a.city_country}</span>
                            <span><i class="fa-regular fa-clock me-1"></i>${a.duration_hours} hours</span>
                            <span><i class="fa-solid fa-money-bill-wave me-1"></i>$${a.cost}</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">${a.category.toUpperCase()}</span>
                        </div>
                        <p class="fs-5">${a.description || 'No detailed description available.'}</p>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="openAddActivityModal(${a.id}); bootstrap.Modal.getInstance(document.getElementById('quickViewModal')).hide();"><i class="fa-solid fa-plus me-1"></i> Add to Trip</button>
                        </div>
                    </div>
                `;
            }
        } catch (e) {
            content.innerHTML = '<div class="alert alert-danger m-3">Failed to load details.</div>';
        }
    };

    window.viewCityActivities = (cityId, cityName) => {
        // Switch to activities tab and filter by city
        const btn = document.getElementById('activities-tab');
        const tab = new bootstrap.Tab(btn);
        tab.show();
        
        const sel = document.querySelector('#activitySearchForm select[name="city_id"]');
        if (sel) {
            sel.value = cityId;
            fetchActivities(1);
        }
    };

    // Form Submissions
    document.getElementById('addCityForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
        
        const data = {
            city_id: document.getElementById('addCity_id').value,
            start_date: e.target.elements['start_date'].value,
            end_date: e.target.elements['end_date'].value
        };
        const tripId = e.target.elements['trip_id'].value;
        
        try {
            const res = await api('POST', `/api/stops.php?trip_id=${tripId}`, data);
            if (res && res.success) {
                toast('City added to trip successfully!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addCityModal')).hide();
                e.target.reset();
            } else {
                toast(res.error || 'Failed to add city', 'error');
            }
        } catch (err) {
            toast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Add Stop';
        }
    });

    // Populate stops when trip is selected for activity
    const actTripSelect = document.getElementById('activityTripSelect');
    const actStopSelect = document.getElementById('activityStopSelect');
    const actDateInput = document.getElementById('activityDate');
    
    actTripSelect.addEventListener('change', async (e) => {
        const tripId = e.target.value;
        actStopSelect.innerHTML = '<option value="">Loading stops...</option>';
        actStopSelect.disabled = true;
        
        if (!tripId) {
            actStopSelect.innerHTML = '<option value="">-- Select trip first --</option>';
            return;
        }
        
        try {
            const res = await api('GET', `/api/stops.php?trip_id=${tripId}`);
            if (res && res.success && res.data.length > 0) {
                actStopSelect.innerHTML = '<option value="">-- Choose a stop --</option>';
                res.data.forEach(stop => {
                    const opt = document.createElement('option');
                    opt.value = stop.id;
                    opt.dataset.start = stop.start_date;
                    opt.dataset.end = stop.end_date;
                    opt.textContent = `${stop.city_name} (${stop.start_date} to ${stop.end_date})`;
                    actStopSelect.appendChild(opt);
                });
                actStopSelect.disabled = false;
            } else {
                actStopSelect.innerHTML = '<option value="">No stops in this trip</option>';
            }
        } catch (err) {
            actStopSelect.innerHTML = '<option value="">Error loading stops</option>';
        }
    });

    actStopSelect.addEventListener('change', (e) => {
        const opt = e.target.options[e.target.selectedIndex];
        if (opt && opt.value) {
            // Set date to start date of stop
            actDateInput.value = opt.dataset.start;
            actDateInput.min = opt.dataset.start;
            actDateInput.max = opt.dataset.end;
        } else {
            actDateInput.value = '';
            actDateInput.removeAttribute('min');
            actDateInput.removeAttribute('max');
        }
    });

    document.getElementById('addActivityForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const stopId = actStopSelect.value;
        if (!stopId) {
            toast('Please select a valid stop first.', 'error');
            return;
        }

        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
        
        const data = {
            activity_id: document.getElementById('addActivity_id').value,
            scheduled_date: actDateInput.value
        };
        
        try {
            const res = await api('POST', `/api/stops.php?action=activities&stop_id=${stopId}`, data);
            if (res && res.success) {
                toast('Activity added to trip successfully!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('addActivityModal')).hide();
                e.target.reset();
                actStopSelect.innerHTML = '<option value="">-- Select trip first --</option>';
                actStopSelect.disabled = true;
            } else {
                toast(res.error || 'Failed to add activity', 'error');
            }
        } catch (err) {
            toast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Add Activity';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
