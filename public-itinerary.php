<?php
require_once __DIR__ . '/includes/auth.php';

$tripId = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
$slug = clean_str($_GET['slug'] ?? '');
$currentUserId = current_user_id();

// Query trip by ID or share_slug
if ($tripId > 0) {
    $stmt = $pdo->prepare('
        SELECT t.*, (u.first_name || \' \' || u.last_name) AS owner_name, u.profile_photo AS owner_photo
        FROM trips t
        JOIN users u ON u.id = t.user_id
        WHERE t.id = ?
    ');
    $stmt->execute([$tripId]);
} elseif ($slug !== '') {
    $stmt = $pdo->prepare('
        SELECT t.*, (u.first_name || \' \' || u.last_name) AS owner_name, u.profile_photo AS owner_photo
        FROM trips t
        JOIN users u ON u.id = t.user_id
        WHERE t.share_slug = ?
    ');
    $stmt->execute([$slug]);
} else {
    // If no ID or slug provided, redirect to dashboard or login
    if (is_logged_in()) {
        redirect('/dashboard.php');
    } else {
        redirect('/login.php');
    }
}

$trip = $stmt->fetch();

// Check existence and visibility
$isOwner = $trip && $currentUserId && ((int)$trip['user_id'] === $currentUserId);
$isPublic = $trip && ($trip['visibility'] === 'public');

$pageTitle = $trip ? htmlspecialchars($trip['trip_name']) . ' — Shared Itinerary | GlobeTrotter' : 'Trip Not Found — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 1140px;">
    <?php if (!$trip || (!$isPublic && !$isOwner)): ?>
        <!-- Private / Not Found State -->
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-5 bg-white">
            <div class="w-16 h-16 bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                <i class="fa-solid fa-lock text-secondary"></i>
            </div>
            <h2 class="fw-bold text-dark mb-2 font-[Outfit]">This Itinerary is Private</h2>
            <p class="text-secondary max-w-md mx-auto mb-4" style="max-width: 480px;">
                The creator has set this travel itinerary to private or the link is no longer active.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                    <i class="fa-solid fa-house me-1.5"></i> <?= is_logged_in() ? 'Back to Dashboard' : 'Sign In to GlobeTrotter' ?>
                </a>
                <a href="city-search.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold">
                    <i class="fa-solid fa-compass me-1.5"></i> Explore Destinations
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php
        // Fetch Stops & Activities
        $stopsStmt = $pdo->prepare('
            SELECT s.*, c.name AS city_name, c.country AS city_country, c.image_url AS city_image, c.region
            FROM trip_stops s
            JOIN cities c ON c.id = s.city_id
            WHERE s.trip_id = ?
            ORDER BY s.order_index ASC, s.arrival_date ASC
        ');
        $stopsStmt->execute([$trip['id']]);
        $stops = $stopsStmt->fetchAll();

        foreach ($stops as &$stop) {
            $actStmt = $pdo->prepare('
                SELECT a.name, a.category, a.duration_hours, a.image_url,
                       sa.scheduled_date, sa.scheduled_time, sa.notes
                FROM trip_activities sa
                JOIN activities a ON a.id = sa.activity_id
                WHERE sa.trip_stop_id = ?
                ORDER BY sa.scheduled_date, sa.scheduled_time NULLS LAST
            ');
            $actStmt->execute([$stop['id']]);
            $stop['activities'] = $actStmt->fetchAll();
        }
        unset($stop);

        // Fetch Community Posts linked to this trip
        $postsStmt = $pdo->prepare('
            SELECT p.*, (u.first_name || \' \' || u.last_name) AS author_name, u.profile_photo AS author_photo
            FROM community_posts p
            JOIN users u ON u.id = p.user_id
            WHERE p.trip_id = ?
            ORDER BY p.created_at DESC
        ');
        $postsStmt->execute([$trip['id']]);
        $communityPosts = $postsStmt->fetchAll();

        $coverUrl = $trip['cover_photo'] 
            ? (str_starts_with($trip['cover_photo'], 'http') ? $trip['cover_photo'] : 'assets/uploads/covers/' . htmlspecialchars($trip['cover_photo']))
            : 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&h=450&fit=crop';

        $totalDays = (strtotime($trip['end_date']) - strtotime($trip['start_date'])) / 86400 + 1;
        $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        ?>

        <!-- ── HERO BANNER ──────────────────────────────────────────────────────── -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4 position-relative">
            <div style="height: 320px; background: url('<?= htmlspecialchars($coverUrl) ?>') center/cover no-repeat; position: relative;">
                <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(15,23,42,0.2) 0%, rgba(15,23,42,0.85) 100%);"></div>
                <div class="position-absolute bottom-0 start-0 end-0 p-4 text-white d-flex justify-content-between align-items-end flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary px-3 py-1.5 rounded-pill text-uppercase fw-bold" style="letter-spacing: 0.05em;">
                                <i class="fa-solid fa-globe me-1"></i> Public Itinerary
                            </span>
                            <span class="badge bg-white bg-opacity-25 px-2.5 py-1 rounded-pill small">
                                <i class="fa-regular fa-clock me-1"></i> <?= max(1, (int)$totalDays) ?> Days
                            </span>
                            <span class="badge bg-white bg-opacity-25 px-2.5 py-1 rounded-pill small">
                                <i class="fa-solid fa-location-dot me-1"></i> <?= count($stops) ?> Stops
                            </span>
                        </div>
                        <h1 class="fw-bold mb-1 display-6 font-[Outfit] text-white"><?= htmlspecialchars($trip['trip_name']) ?></h1>
                        <div class="d-flex align-items-center gap-3 text-white-50 small mt-2">
                            <div class="d-flex align-items-center gap-1.5 text-white">
                                <i class="fa-solid fa-user-circle fs-6"></i>
                                <span>Planned by <strong><?= htmlspecialchars($trip['owner_name']) ?></strong></span>
                            </div>
                            <span>&bull;</span>
                            <div class="text-white">
                                <i class="fa-regular fa-calendar me-1"></i>
                                <?= date('M j, Y', strtotime($trip['start_date'])) ?> &ndash; <?= date('M j, Y', strtotime($trip['end_date'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Copy / Action Button -->
                    <div class="d-flex align-items-center gap-2">
                        <?php if (is_logged_in()): ?>
                            <button class="btn btn-success px-4 py-2.5 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2" id="copyPublicTripBtn" data-id="<?= $trip['id'] ?>">
                                <i class="fa-solid fa-copy"></i>
                                <span>Copy This Trip</span>
                            </button>
                        <?php else: ?>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-light px-4 py-2.5 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2 text-dark">
                                <i class="fa-solid fa-arrow-right-to-bracket text-primary"></i>
                                <span>Log In to Copy Trip</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Share & Action Toolbar -->
            <div class="card-footer bg-white p-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3 border-0 border-top">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small fw-bold text-muted text-uppercase me-2" style="font-size: 0.75rem;">Share This Plan:</span>
                    <!-- WhatsApp -->
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Check out this itinerary for " . $trip['trip_name'] . ": " . $currentUrl) ?>" 
                       target="_blank" rel="noopener" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1.5">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                    <!-- Twitter/X -->
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode("Exploring " . $trip['trip_name'] . " on GlobeTrotter!") ?>&url=<?= urlencode($currentUrl) ?>" 
                       target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1.5">
                        <i class="fa-brands fa-x-twitter"></i> X / Twitter
                    </a>
                    <!-- Facebook -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" 
                       target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1.5">
                        <i class="fa-brands fa-facebook"></i> Facebook
                    </a>
                    <!-- Copy Link -->
                    <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1.5" id="shareCopyLinkBtn" onclick="copyCurrentLink()">
                        <i class="fa-regular fa-clone"></i> Copy Link
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button onclick="window.print()" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-secondary">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($trip['description'])): ?>
            <!-- Trip Description / Overview -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-2 font-[Outfit]">About This Journey</h5>
                <p class="text-secondary mb-0 leading-relaxed"><?= nl2br(htmlspecialchars($trip['description'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- ── DAY-BY-DAY ITINERARY STOPS ──────────────────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h4 class="fw-bold text-dark mb-0 font-[Outfit]">
                <i class="fa-solid fa-route text-primary me-2"></i>Day-by-Day Itinerary
            </h4>
            <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill"><?= count($stops) ?> destinations</span>
        </div>

        <?php if (empty($stops)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-3">
                <i class="fa-solid fa-map-location-dot fa-3x text-muted opacity-40 mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">No stops scheduled yet</h5>
                <p class="text-muted small mb-0">The creator has not added any destinations to this itinerary.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($stops as $idx => $stop): 
                    $stopDays = (strtotime($stop['departure_date']) - strtotime($stop['arrival_date'])) / 86400 + 1;
                ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
                        <div class="card-header bg-white border-bottom p-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-primary rounded-pill px-3 py-1.5 fw-bold">Stop <?= $idx + 1 ?></span>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($stop['city_name']) ?>, <?= htmlspecialchars($stop['city_country']) ?></h5>
                                    <span class="badge bg-light text-secondary border small mt-1"><?= htmlspecialchars($stop['region'] ?? 'Global') ?></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-dark">
                                    <?= date('M j', strtotime($stop['arrival_date'])) ?> &ndash; <?= date('M j, Y', strtotime($stop['departure_date'])) ?>
                                </div>
                                <div class="small text-muted"><?= max(1, (int)$stopDays) ?> <?= $stopDays == 1 ? 'day' : 'days' ?> stay</div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <!-- Stop Details (Accommodation, Transport, Notes) -->
                            <?php if (!empty($stop['accommodation']) || !empty($stop['transport_type']) || !empty($stop['notes'])): ?>
                                <div class="row g-3 mb-3 p-3 bg-light rounded-3">
                                    <?php if (!empty($stop['accommodation'])): ?>
                                        <div class="col-md-4">
                                            <div class="small text-muted text-uppercase fw-bold"><i class="fa-solid fa-hotel me-1 text-primary"></i> Stay</div>
                                            <div class="fw-medium text-dark mt-0.5"><?= htmlspecialchars($stop['accommodation']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($stop['transport_type'])): ?>
                                        <div class="col-md-4">
                                            <div class="small text-muted text-uppercase fw-bold"><i class="fa-solid fa-plane-departure me-1 text-success"></i> Transport</div>
                                            <div class="fw-medium text-dark mt-0.5"><?= htmlspecialchars(ucfirst($stop['transport_type'])) ?> <?= !empty($stop['transport_note']) ? '— ' . htmlspecialchars($stop['transport_note']) : '' ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($stop['notes'])): ?>
                                        <div class="col-md-4">
                                            <div class="small text-muted text-uppercase fw-bold"><i class="fa-solid fa-note-sticky me-1 text-warning"></i> Notes</div>
                                            <div class="text-secondary small mt-0.5"><?= htmlspecialchars($stop['notes']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Activities List -->
                            <h6 class="fw-bold text-dark mb-3 small text-uppercase tracking-wider">
                                <i class="fa-solid fa-ticket text-success me-1.5"></i> Planned Activities (<?= count($stop['activities']) ?>)
                            </h6>

                            <?php if (empty($stop['activities'])): ?>
                                <div class="p-3 bg-light rounded-3 text-center text-muted small">
                                    No specific activities scheduled for this stop.
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($stop['activities'] as $act): ?>
                                        <div class="col-md-6">
                                            <div class="p-3 border rounded-3 h-100 bg-white d-flex flex-column justify-content-between">
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-start mb-1.5">
                                                        <strong class="text-dark fs-6"><?= htmlspecialchars($act['name']) ?></strong>
                                                        <span class="badge bg-light text-dark border small text-capitalize"><?= htmlspecialchars($act['category']) ?></span>
                                                    </div>
                                                    <?php if ($act['notes']): ?>
                                                        <p class="text-secondary small mb-2"><?= htmlspecialchars($act['notes']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center text-muted small mt-2 pt-2 border-top">
                                                    <span><i class="fa-regular fa-calendar me-1"></i><?= $act['scheduled_date'] ? date('M j, Y', strtotime($act['scheduled_date'])) : 'Anytime' ?></span>
                                                    <span><i class="fa-regular fa-clock me-1"></i><?= (float)$act['duration_hours'] ?> hrs</span>
                                                </div>
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

        <!-- ── COMMUNITY STORIES & DISCUSSION ────────────────────────────────── -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mt-5 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 font-[Outfit]">
                    <i class="fa-solid fa-comments text-purple me-2"></i>Community Stories & Travel Notes
                </h5>
                <a href="community.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    View All Community Posts
                </a>
            </div>

            <?php if (empty($communityPosts)): ?>
                <div class="p-4 bg-light rounded-3 text-center text-muted">
                    <i class="fa-regular fa-message fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0 fw-medium">No community stories shared for this trip yet.</p>
                    <small class="text-secondary">Travelers can share reviews, tips, and photos in the Community hub.</small>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($communityPosts as $post): ?>
                        <div class="p-3.5 border rounded-3 bg-light mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar-sm bg-primary text-white" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                        <?= strtoupper(substr($post['author_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong class="text-dark small"><?= htmlspecialchars($post['author_name']) ?></strong>
                                        <div class="text-muted small" style="font-size: 0.7rem;"><?= date('M j, Y', strtotime($post['created_at'])) ?></div>
                                    </div>
                                </div>
                                <span class="badge bg-white text-danger border shadow-2xs">
                                    <i class="fa-solid fa-heart me-1"></i> <?= (int)$post['likes_count'] ?> likes
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                            <p class="text-secondary small mb-0"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<script>
function copyCurrentLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        if (typeof showToast === 'function') {
            showToast('Link copied to clipboard!', 'success');
        } else if (typeof toast === 'function') {
            toast('Link copied to clipboard!', 'success');
        } else {
            alert('Link copied to clipboard!');
        }
    }).catch(() => {
        alert('Could not copy link. Please copy from browser address bar.');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copyPublicTripBtn');
    if (!copyBtn) return;

    copyBtn.addEventListener('click', async function () {
        const tripId = this.dataset.id;
        copyBtn.disabled = true;
        copyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span> Copying Trip...';

        try {
            const res = await api('POST', 'api/trips.php?action=copy', { trip_id: parseInt(tripId, 10) });
            if (res && res.success && res.data.trip_id) {
                if (typeof showToast === 'function') {
                    showToast('Trip copied to your account!', 'success');
                } else if (typeof toast === 'function') {
                    toast('Trip copied to your account!', 'success');
                }
                setTimeout(() => {
                    window.location.href = 'itinerary-builder.php?trip_id=' + res.data.trip_id;
                }, 800);
            } else {
                alert(res.error || 'Failed to copy trip.');
                copyBtn.disabled = false;
                copyBtn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy This Trip';
            }
        } catch (err) {
            alert('Error copying trip. Please make sure you are logged in.');
            copyBtn.disabled = false;
            copyBtn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy This Trip';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
