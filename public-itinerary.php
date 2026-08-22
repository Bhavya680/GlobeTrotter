<?php
require_once __DIR__ . '/includes/auth.php';

$slug = clean_str($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT t.id, t.trip_name AS name, t.start_date, t.end_date, t.description, t.cover_photo,
           (u.first_name || \' \' || u.last_name) AS owner_name
    FROM trips t
    JOIN users u ON u.id = t.user_id
    WHERE t.share_slug = ? AND t.visibility = \'public\'
');
$stmt->execute([$slug]);
$trip = $stmt->fetch();

if (!$trip) {
    echo '<div style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Trip Not Found or Private</h2><p>The itinerary link you accessed is no longer public.</p><a href="/dashboard.php">Go to GlobeTrotter Dashboard</a></div>';
    exit;
}

$stopsStmt = $pdo->prepare('
    SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
           s.transport_note, s.accommodation,
           c.name AS city_name, c.country AS city_country
    FROM trip_stops s
    JOIN cities c ON c.id = s.city_id
    WHERE s.trip_id = ?
    ORDER BY s.order_index ASC
');
$stopsStmt->execute([$trip['id']]);
$stops = $stopsStmt->fetchAll();

foreach ($stops as &$stop) {
    $actStmt = $pdo->prepare('
        SELECT a.name, a.category, a.duration_hours, sa.scheduled_date, sa.scheduled_time
        FROM trip_activities sa
        JOIN activities a ON a.id = sa.activity_id
        WHERE sa.trip_stop_id = ?
        ORDER BY sa.scheduled_date, sa.scheduled_time NULLS LAST
    ');
    $actStmt->execute([$stop['id']]);
    $stop['activities'] = $actStmt->fetchAll();
}
unset($stop);

$pageTitle = htmlspecialchars($trip['name']) . ' — Shared Itinerary — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="card border-0 shadow-lg overflow-hidden mb-4">
        <div class="card-body p-4 bg-primary text-white">
            <span class="badge bg-white text-primary mb-2">Public Shared Itinerary</span>
            <h1 class="fw-bold mb-1 text-white"><?= htmlspecialchars($trip['name']) ?></h1>
            <p class="mb-2 opacity-90"><i class="fa-solid fa-user me-1"></i> Planned by <?= htmlspecialchars($trip['owner_name']) ?></p>
            <p class="mb-0 small"><i class="fa-regular fa-calendar me-1"></i> <?= date('M j, Y', strtotime($trip['start_date'])) ?> – <?= date('M j, Y', strtotime($trip['end_date'])) ?></p>
        </div>
        <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="fa-solid fa-globe me-1"></i> Anyone with this link can view this itinerary.</span>
            <?php if (is_logged_in()): ?>
                <button class="btn btn-success fw-bold" id="copyTripBtn">
                    <i class="fa-solid fa-copy me-1"></i> Copy Trip to My Account
                </button>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary fw-bold">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login to Copy Trip
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($trip['description']): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-muted small text-uppercase">Overview</h6>
                <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($trip['description'])) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <h4 class="fw-bold mb-3"><i class="fa-solid fa-route text-primary me-2"></i>Day-by-Day Itinerary</h4>
    <div class="timeline">
        <?php foreach ($stops as $idx => $stop): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2">Stop <?= $idx + 1 ?></span>
                        <strong class="fs-5 text-dark"><?= htmlspecialchars($stop['city_name']) ?>, <?= htmlspecialchars($stop['city_country']) ?></strong>
                    </div>
                    <span class="text-muted small"><?= date('M j', strtotime($stop['start_date'])) ?> – <?= date('M j, Y', strtotime($stop['end_date'])) ?></span>
                </div>
                <div class="card-body p-3">
                    <?php if (!empty($stop['activities'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($stop['activities'] as $act): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($act['name']) ?></strong>
                                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($act['category']) ?></span>
                                        <span class="small text-muted"><?= $act['duration_hours'] ?> hrs</span>
                                    </div>
                                    <span class="small text-muted"><?= date('M j', strtotime($act['scheduled_date'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No activities listed.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copyTripBtn');
    if (!copyBtn) return;

    copyBtn.addEventListener('click', async function() {
        copyBtn.disabled = true;
        copyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Copying...';
        try {
            const res = await api('POST', '/api/community.php?action=copy', { slug: '<?= $slug ?>' });
            if (res && res.success && res.data.trip_id) {
                toast('Trip copied to your account!', 'success');
                setTimeout(function() {
                    window.location.href = 'itinerary-builder.php?trip_id=' + res.data.trip_id;
                }, 1000);
            } else {
                toast(res.error || 'Failed to copy trip', 'error');
                copyBtn.disabled = false;
            }
        } catch (err) {
            toast('Error copying trip', 'error');
            copyBtn.disabled = false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
