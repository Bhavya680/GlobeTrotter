<?php
require_once __DIR__ . '/includes/auth.php';

$userId = isLoggedIn() ? current_user_id() : null;
$q = clean_str($_GET['q'] ?? '');
$region = clean_str($_GET['region'] ?? '');
$country = clean_str($_GET['country'] ?? '');

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(name ILIKE ? OR country ILIKE ? OR region ILIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}
if ($region !== '') {
    $where[] = 'region = ?';
    $params[] = $region;
}
if ($country !== '') {
    $where[] = 'country = ?';
    $params[] = $country;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT id, name, country, region, cost_index, popularity_score, description, image_url
    FROM cities
    {$whereSql}
    ORDER BY popularity_score DESC
");
$stmt->execute($params);
$cities = $stmt->fetchAll();

// Fetch saved destinations for logged in user
$savedCityIds = [];
if ($userId) {
    $sStmt = $pdo->prepare('SELECT city_id FROM saved_destinations WHERE user_id = ?');
    $sStmt->execute([$userId]);
    $savedCityIds = $sStmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Explore Cities & Destinations — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="h2 fw-bold mb-1"><i class="fa-solid fa-earth-americas text-primary me-2"></i>Explore Cities & Destinations</h1>
        <p class="text-muted">Discover top global destinations, compare cost indices, and save favorites to your profile.</p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="city-search.php" method="GET" class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search city, country, or region..." value="<?= htmlspecialchars($q) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="region" class="form-select">
                        <option value="">All Regions</option>
                        <option value="Europe" <?= $region === 'Europe' ? 'selected' : '' ?>>Europe</option>
                        <option value="Asia" <?= $region === 'Asia' ? 'selected' : '' ?>>Asia</option>
                        <option value="North America" <?= $region === 'North America' ? 'selected' : '' ?>>North America</option>
                        <option value="South America" <?= $region === 'South America' ? 'selected' : '' ?>>South America</option>
                        <option value="Africa" <?= $region === 'Africa' ? 'selected' : '' ?>>Africa</option>
                        <option value="Oceania" <?= $region === 'Oceania' ? 'selected' : '' ?>>Oceania</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Apply Filters</button>
                    <?php if ($q || $region || $country): ?>
                        <a href="city-search.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Cities Grid -->
    <div class="row g-4">
        <?php if (empty($cities)): ?>
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-city fa-3x text-muted mb-3"></i>
                <h5>No cities found matching your search</h5>
                <p class="text-muted">Try removing search filters to explore all global destinations.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cities as $c): ?>
                <?php
                $isSaved = in_array($c['id'], $savedCityIds);
                $img = $c['image_url'] ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=800&q=80';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden hover-shadow transition">
                        <div class="position-relative" style="height: 200px; background-image: url('<?= htmlspecialchars($img) ?>'); background-size: cover; background-position: center;">
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge bg-dark bg-opacity-75 fs-6">$<?= $c['cost_index'] ?> Index</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($c['name']) ?></h5>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-fire me-1"></i><?= $c['popularity_score'] ?> Popularity</span>
                                </div>
                                <span class="text-muted small d-block mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars($c['country']) ?> (<?= htmlspecialchars($c['region'] ?: 'Global') ?>)</span>
                                <p class="text-muted small mb-0"><?= htmlspecialchars($c['description'] ?: 'Popular travel destination offering cultural sights, dining, and local experiences.') ?></p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <?php if ($userId): ?>
                                    <button class="btn btn-sm <?= $isSaved ? 'btn-danger' : 'btn-outline-danger' ?> toggle-save-city-btn" data-city-id="<?= $c['id'] ?>">
                                        <i class="fa-<?= $isSaved ? 'solid' : 'regular' ?> fa-heart me-1"></i> <?= $isSaved ? 'Saved' : 'Save' ?>
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-heart me-1"></i> Save</a>
                                <?php endif; ?>
                                <a href="create-trip.php" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-plus me-1"></i> Add to Trip
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-save-city-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const cityId = btn.dataset.cityId;
            try {
                const res = await api('POST', '/api/profile.php?action=toggle_saved', { city_id: cityId });
                if (res && res.success) {
                    if (res.data.saved) {
                        btn.className = 'btn btn-sm btn-danger toggle-save-city-btn';
                        btn.innerHTML = '<i class="fa-solid fa-heart me-1"></i> Saved';
                        toast('Destination saved to your profile!', 'success');
                    } else {
                        btn.className = 'btn btn-sm btn-outline-danger toggle-save-city-btn';
                        btn.innerHTML = '<i class="fa-regular fa-heart me-1"></i> Save';
                        toast('Destination removed from saved', 'info');
                    }
                }
            } catch (err) {
                toast('Error saving destination', 'error');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
