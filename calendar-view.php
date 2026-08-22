<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if (!$tripId) {
    header('Location: my-trips.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT t.id, t.trip_name AS name, t.start_date, t.end_date, t.description
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
    SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date,
           c.name AS city_name, c.country AS city_country
    FROM trip_stops s
    JOIN cities c ON c.id = s.city_id
    WHERE s.trip_id = ?
    ORDER BY s.arrival_date ASC
');
$stopsStmt->execute([$tripId]);
$stops = $stopsStmt->fetchAll();

// Build timeline dates array from start_date to end_date
$dates = [];
$curr = new DateTime($trip['start_date']);
$end = new DateTime($trip['end_date']);
while ($curr <= $end) {
    $dates[] = $curr->format('Y-m-d');
    $curr->modify('+1 day');
}

$pageTitle = 'Calendar View — ' . htmlspecialchars($trip['name']) . ' — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h2 fw-bold mb-0"><i class="fa-regular fa-calendar-days text-primary me-2"></i><?= htmlspecialchars($trip['name']) ?> — Timeline</h1>
            <p class="text-muted mb-0 mt-1"><?= date('M j, Y', strtotime($trip['start_date'])) ?> – <?= date('M j, Y', strtotime($trip['end_date'])) ?></p>
        </div>
        <div>
            <a href="itinerary-view.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-list me-1"></i> List View
            </a>
            <a href="itinerary-builder.php?trip_id=<?= $trip['id'] ?>" class="btn btn-primary">
                <i class="fa-solid fa-pen me-1"></i> Edit Builder
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($dates as $dateStr): ?>
            <?php
            $matchingStops = array_filter($stops, function($s) use ($dateStr) {
                return $dateStr >= $s['start_date'] && $dateStr <= $s['end_date'];
            });
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong class="text-dark"><?= date('D, M j, Y', strtotime($dateStr)) ?></strong>
                        <span class="badge bg-primary-subtle text-primary">Day <?= array_search($dateStr, $dates) + 1 ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($matchingStops)): ?>
                            <p class="text-muted small mb-0">No stop scheduled for this day.</p>
                        <?php else: ?>
                            <?php foreach ($matchingStops as $st): ?>
                                <div class="p-2 mb-2 bg-primary text-white rounded shadow-sm">
                                    <strong class="d-block"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($st['city_name']) ?></strong>
                                    <span class="small opacity-75"><?= htmlspecialchars($st['city_country']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
