<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if (!$tripId) {
    header('Location: my-trips.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT t.id, t.trip_name AS name, t.start_date, t.end_date, t.description, t.cover_photo, t.visibility, t.share_slug
    FROM trips t
    WHERE t.id = ? AND t.user_id = ?
');
$stmt->execute([$tripId, $userId]);
$trip = $stmt->fetch();
if (!$trip) {
    header('Location: my-trips.php');
    exit;
}

$stopsStmt = $pdo->prepare('
    SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
           s.transport_note, s.accommodation, s.accommodation_cost, s.notes AS stop_notes,
           c.name AS city_name, c.country AS city_country, c.image_url AS city_image
    FROM trip_stops s
    JOIN cities c ON c.id = s.city_id
    WHERE s.trip_id = ?
    ORDER BY s.order_index ASC, s.arrival_date ASC
');
$stopsStmt->execute([$tripId]);
$stops = $stopsStmt->fetchAll();

foreach ($stops as &$stop) {
    $actStmt = $pdo->prepare('
        SELECT sa.id, sa.scheduled_date, sa.scheduled_time, sa.notes,
               a.name, a.category, a.duration_hours,
               COALESCE(sa.custom_cost, a.cost) AS effective_cost
        FROM trip_activities sa
        JOIN activities a ON a.id = sa.activity_id
        WHERE sa.trip_stop_id = ?
        ORDER BY sa.scheduled_date ASC, sa.scheduled_time ASC NULLS LAST
    ');
    $actStmt->execute([$stop['id']]);
    $stop['activities'] = $actStmt->fetchAll();
}
unset($stop);

$pageTitle = 'Itinerary View — ' . htmlspecialchars($trip['name']) . ' — GlobeTrotter';
$loadTripsCSS = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="fw-bold h2 mb-0"><?= htmlspecialchars($trip['name']) ?></h1>
                <span class="badge bg-<?= $trip['visibility'] === 'public' ? 'success' : 'secondary' ?>">
                    <i class="fa-solid fa-<?= $trip['visibility'] === 'public' ? 'globe' : 'lock' ?> me-1"></i>
                    <?= ucfirst($trip['visibility']) ?>
                </span>
            </div>
            <p class="text-muted mb-0 mt-1">
                <i class="fa-regular fa-calendar me-1"></i>
                <?= date('M j, Y', strtotime($trip['start_date'])) ?> – <?= date('M j, Y', strtotime($trip['end_date'])) ?>
                <span class="mx-2">•</span>
                <i class="fa-solid fa-location-dot me-1"></i> <?= count($stops) ?> Destinations
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="itinerary-builder.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Itinerary
            </a>
            <a href="calendar-view.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-secondary">
                <i class="fa-regular fa-calendar-days me-1"></i> Calendar View
            </a>
            <a href="budget-view.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-success">
                <i class="fa-solid fa-wallet me-1"></i> Budget Tracking
            </a>
            <?php if ($trip['visibility'] === 'public' && !empty($trip['share_slug'])): ?>
                <button class="btn btn-info text-white" onclick="navigator.clipboard.writeText('<?= SITE_URL ?>/public-itinerary.php?slug=<?= $trip['share_slug'] ?>'); toast('Share link copied!', 'success');">
                    <i class="fa-solid fa-share-nodes me-1"></i> Copy Public Link
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($trip['description']): ?>
        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-body">
                <h6 class="fw-bold text-muted small text-uppercase mb-1">Trip Overview</h6>
                <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($trip['description'])) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($stops)): ?>
        <div class="text-center py-5 bg-light rounded-3 border">
            <i class="fa-solid fa-map-location-dot fa-3x text-muted mb-3"></i>
            <h4>No stops added to this trip yet</h4>
            <p class="text-muted">Start adding cities and dates to build your personalized itinerary.</p>
            <a href="itinerary-builder.php?trip_id=<?= $trip['id'] ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Add Cities in Itinerary Builder
            </a>
        </div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($stops as $idx => $stop): ?>
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-primary fw-bold fs-6">Stop <?= $idx + 1 ?></span>
                            <h4 class="h5 mb-0 font-weight-bold text-white"><?= htmlspecialchars($stop['city_name']) ?>, <?= htmlspecialchars($stop['city_country']) ?></h4>
                        </div>
                        <span class="small bg-white bg-opacity-25 px-3 py-1 rounded-pill">
                            <?= date('M j', strtotime($stop['start_date'])) ?> – <?= date('M j, Y', strtotime($stop['end_date'])) ?>
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($stop['transport_note'] || $stop['accommodation']): ?>
                            <div class="row g-3 mb-3 p-3 bg-light rounded">
                                <?php if ($stop['transport_note']): ?>
                                    <div class="col-md-6">
                                        <strong class="small text-uppercase text-muted d-block mb-1"><i class="fa-solid fa-bus me-1"></i> Transport</strong>
                                        <span class="small text-dark"><?= htmlspecialchars($stop['transport_note']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($stop['accommodation']): ?>
                                    <div class="col-md-6">
                                        <strong class="small text-uppercase text-muted d-block mb-1"><i class="fa-solid fa-hotel me-1"></i> Accommodation</strong>
                                        <span class="small text-dark"><?= htmlspecialchars($stop['accommodation']) ?> <?= $stop['accommodation_cost'] ? '($' . number_format($stop['accommodation_cost'], 2) . ')' : '' ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-ticket text-primary me-2"></i>Scheduled Activities</h6>
                        <?php if (empty($stop['activities'])): ?>
                            <p class="text-muted small mb-0 fs-6">No specific activities scheduled for this stop.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($stop['activities'] as $act): ?>
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($act['name']) ?></h6>
                                            <span class="badge bg-light text-dark border me-2"><?= htmlspecialchars($act['category']) ?></span>
                                            <span class="small text-muted me-2"><i class="fa-regular fa-clock me-1"></i><?= $act['duration_hours'] ?> hrs</span>
                                            <span class="small text-muted"><i class="fa-regular fa-calendar me-1"></i><?= date('M j', strtotime($act['scheduled_date'])) ?></span>
                                        </div>
                                        <div class="fw-bold text-success">
                                            $<?= number_format($act['effective_cost'], 2) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
