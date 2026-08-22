<?php


$pageTitle    = 'My Trips — GlobeTrotter';
$loadTripsCSS = true;
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();
$user = current_user();
// ── Fetch all trips with stop count + cities list ─────────────────────────
$tripsQ = $pdo->prepare("
    SELECT t.id, t.trip_name, t.start_date, t.end_date, t.cover_photo, t.visibility,
           COUNT(DISTINCT s.id) AS stop_count,
           STRING_AGG(DISTINCT c.name, ', ') AS cities_list,
           COUNT(DISTINCT c.country) AS countries_count,
           CASE 
               WHEN t.end_date < CURRENT_DATE THEN 'completed'
               WHEN t.start_date <= CURRENT_DATE AND t.end_date >= CURRENT_DATE THEN 'ongoing'
               ELSE 'upcoming'
           END AS status
    FROM trips t
    LEFT JOIN trip_stops s ON s.trip_id = t.id
    LEFT JOIN cities c ON c.id = s.city_id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.start_date DESC
");
$tripsQ->execute([$userId]);
$allTrips = $tripsQ->fetchAll();

// ── Group by status ───────────────────────────────────────────────────────
$tripsByStatus = ['ongoing' => [], 'upcoming' => [], 'completed' => []];
foreach ($allTrips as $t) {
    $key = $t['status'];
    if (isset($tripsByStatus[$key])) $tripsByStatus[$key][] = $t;
}

// ── Stats ─────────────────────────────────────────────────────────────────
$totalTrips = count($allTrips);
$totalDays = 0;
foreach ($tripsByStatus['completed'] as $t) {
    if ($t['start_date'] && $t['end_date']) {
        $start = new DateTime($t['start_date']);
        $end = new DateTime($t['end_date']);
        $totalDays += (int)$start->diff($end)->days + 1;
    }
}

$countriesQ = $pdo->prepare("
    SELECT COUNT(DISTINCT c.country) AS cnt
    FROM trips t
    JOIN trip_stops s ON s.trip_id = t.id
    JOIN cities c ON c.id = s.city_id
    WHERE t.user_id = ? AND t.end_date < CURRENT_DATE
");
$countriesQ->execute([$userId]);
$totalCountries = (int)$countriesQ->fetchColumn();

// ── Gradient classes ───────────────────────────────────────────────────────
$thumbGrads = ['thumb-g1','thumb-g2','thumb-g3','thumb-g4'];

require_once __DIR__ . '/includes/header.php';

// Helper: render a trip horizontal card
function renderTripCard(array $t, int $idx, array $thumbGrads, string $siteUrl): void {
    $grad    = $thumbGrads[$idx % 4];
    $initial = strtoupper(substr($t['trip_name'], 0, 1));
    $status  = $t['status'];
    $today   = new DateTime('today');

    // Badge class
    $badgeClass = match($status) {
        'completed' => 'badge bg-success',
        'ongoing'   => 'badge bg-warning text-dark',
        default     => 'badge bg-info text-dark',
    };

    // Contextual info line
    $infoLine = '';
    if ($status === 'upcoming' && $t['start_date']) {
        $start = new DateTime($t['start_date']);
        $diff  = (int)$today->diff($start)->days;
        $infoLine = "<i class='fa-regular fa-clock'></i> Starts in {$diff} day" . ($diff !== 1 ? 's' : '');
    } elseif ($status === 'ongoing' && $t['end_date']) {
        $end  = new DateTime($t['end_date']);
        $diff = (int)$today->diff($end)->days;
        $infoLine = "<i class='fa-solid fa-hourglass-half'></i> {$diff} day" . ($diff !== 1 ? 's' : '') . ' remaining';
    } elseif ($status === 'completed' && $t['end_date']) {
        $infoLine = "<i class='fa-solid fa-calendar-check'></i> Completed " . date('M j, Y', strtotime($t['end_date']));
    }

    $dateRange = '';
    if ($t['start_date'] && $t['end_date']) {
        $dateRange = date('M j', strtotime($t['start_date'])) . ' – ' . date('M j, Y', strtotime($t['end_date']));
    }

    $coverSrc = !empty($t['cover_photo'])
        ? $siteUrl . '/assets/uploads/covers/' . htmlspecialchars($t['cover_photo'])
        : '';

    $stopCount = (int)$t['stop_count'];
    $cities    = htmlspecialchars($t['cities_list'] ?? '');

    echo <<<HTML
    <div class="trip-hcard" id="tripCard{$t['id']}" data-status="{$status}" data-name="{$initial}" data-date="{$t['start_date']}">
        <div class="trip-hcard-thumb">
HTML;

    if ($coverSrc) {
        echo "<img src=\"{$coverSrc}\" alt=\"" . htmlspecialchars($t['trip_name']) . "\" loading=\"lazy\">";
    } else {
        echo "<div class=\"thumb-gradient {$grad}\">{$initial}</div>";
    }

    echo <<<HTML
        </div>
        <span class="trip-hcard-badge {$badgeClass}">{$status}</span>
        <div class="trip-hcard-body">
            <div class="trip-hcard-name">{$t['trip_name']}</div>
            <div class="trip-hcard-meta">
HTML;
    if ($dateRange) echo "<span><i class='fa-regular fa-calendar'></i> {$dateRange}</span>";
    if ($stopCount) echo "<span><i class='fa-solid fa-map-pin'></i> {$stopCount} stop" . ($stopCount !== 1 ? 's' : '') . "</span>";
    if ($infoLine)  echo "<span>{$infoLine}</span>";
    echo <<<HTML
            </div>
HTML;
    if ($cities) {
        echo "<div class='trip-hcard-cities'><i class='fa-solid fa-city me-1'></i>{$cities}</div>";
    }
    echo <<<HTML
        </div>
        <div class="trip-hcard-actions">
            <a href="itinerary-view.php?trip_id={$t['id']}"
               class="btn-trip-action btn-trip-view"
               id="viewBtn{$t['id']}">
                <i class="fa-solid fa-eye"></i> View
            </a>
            <a href="itinerary-builder.php?trip_id={$t['id']}"
               class="btn-trip-action btn-trip-edit"
               id="editBtn{$t['id']}">
                <i class="fa-solid fa-pen"></i> Builder
            </a>
HTML;
    if ($status === 'completed') {
        $shareTitle = $t['visibility'] !== 'public' ? 'title="Make trip public to share"' : '';
        echo <<<HTML
            <button type="button" class="btn-trip-action btn-trip-share"
                    id="shareBtn{$t['id']}"
                    onclick="openShare({$t['id']}, '{$initial}')"
                    {$shareTitle}>
                <i class="fa-solid fa-share-nodes"></i> Share
            </button>
HTML;
    }
    if ($status !== 'ongoing') {
        $safeTripName = htmlspecialchars($t['trip_name'], ENT_QUOTES);
        echo <<<HTML
            <button type="button" class="btn-trip-action btn-trip-delete"
                    id="deleteBtn{$t['id']}"
                    onclick="confirmDelete({$t['id']}, '{$safeTripName}')">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
HTML;
    }
    echo "</div></div>\n";
}
?>

<!-- ══ Stats Bar ═════════════════════════════════════════════════════════ -->
<div class="trips-stats-bar">
    <div class="container">
        <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap">
            <div class="trips-stat-item">
                <div class="trips-stat-num"><?= $totalTrips ?></div>
                <div class="trips-stat-label">Total Trips</div>
            </div>
            <div class="trips-stat-divider d-none d-md-block"></div>
            <div class="trips-stat-item">
                <div class="trips-stat-num"><?= $totalCountries ?></div>
                <div class="trips-stat-label">Countries Visited</div>
            </div>
            <div class="trips-stat-divider d-none d-md-block"></div>
            <div class="trips-stat-item">
                <div class="trips-stat-num"><?= $totalDays ?></div>
                <div class="trips-stat-label">Days Traveled</div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Filter + Sort Bar ════════════════════════════════════════════════ -->
<div class="trips-filter-bar">
    <div class="container d-flex align-items-stretch">
        <div class="filter-tabs flex-grow-1" id="filterTabs" role="tablist">
            <button class="filter-tab active" data-filter="all" role="tab" id="tabAll">
                All <span class="tab-badge"><?= $totalTrips ?></span>
            </button>
            <button class="filter-tab" data-filter="ongoing" role="tab" id="tabOngoing">
                <i class="fa-solid fa-circle text-warning" style="font-size:0.55rem"></i>
                Ongoing <span class="tab-badge"><?= count($tripsByStatus['ongoing']) ?></span>
            </button>
            <button class="filter-tab" data-filter="upcoming" role="tab" id="tabUpcoming">
                <i class="fa-solid fa-circle text-primary" style="font-size:0.55rem"></i>
                Upcoming <span class="tab-badge"><?= count($tripsByStatus['upcoming']) ?></span>
            </button>
            <button class="filter-tab" data-filter="completed" role="tab" id="tabCompleted">
                <i class="fa-solid fa-circle text-success" style="font-size:0.55rem"></i>
                Completed <span class="tab-badge"><?= count($tripsByStatus['completed']) ?></span>
            </button>
        </div>
        <div class="filter-sort-wrap">
            <i class="fa-solid fa-arrow-up-wide-short text-muted" style="font-size:0.8rem"></i>
            <select class="sort-select" id="sortSelect" aria-label="Sort trips">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="az">A–Z</option>
                <option value="date">By Start Date</option>
            </select>
        </div>
    </div>
</div>

<!-- ══ Trip Listing ══════════════════════════════════════════════════════ -->
<?php if ($totalTrips === 0): ?>
<div class="trips-empty-full">
    <div class="trips-empty-icon mx-auto">
        <div class="icon-bg">
            <i class="fa-solid fa-map-location-dot"></i>
        </div>
    </div>
    <h4>You haven't planned any trips yet</h4>
    <p>Start by creating your first trip — explore the world one stop at a time!</p>
    <a href="create-trip.php" class="btn btn-primary btn-lg" id="firstTripBtn">
        <i class="fa-solid fa-plus me-2"></i> Plan Your First Trip
    </a>
</div>

<?php else: ?>
<div class="trips-page">
    <div class="container" id="tripsListContainer">

        <?php
        $sections = [
            'ongoing'   => ['label' => 'Ongoing',   'icon' => 'fa-circle-play',    'noMsg' => 'No ongoing trips right now.'],
            'upcoming'  => ['label' => 'Upcoming',   'icon' => 'fa-calendar-days',  'noMsg' => 'No upcoming trips planned.'],
            'completed' => ['label' => 'Completed',  'icon' => 'fa-circle-check',   'noMsg' => 'No completed trips yet.'],
        ];

        foreach ($sections as $statusKey => $sec):
            $trips = $tripsByStatus[$statusKey];
        ?>
        <div class="status-section" id="section-<?= $statusKey ?>">
            <div class="status-heading <?= $statusKey ?>">
                <i class="fa-solid <?= $sec['icon'] ?>"></i>
                <?= $sec['label'] ?>
                <span style="font-weight:400;font-size:0.8rem;opacity:0.7">
                    (<?= count($trips) ?>)
                </span>
            </div>

            <div id="tripsList-<?= $statusKey ?>">
                <?php if (!empty($trips)):
                    foreach ($trips as $idx => $t):
                        renderTripCard($t, $idx, $thumbGrads, SITE_URL);
                    endforeach;
                else: ?>
                <div class="no-section-trips" id="noSection-<?= $statusKey ?>">
                    <i class="fa-regular fa-calendar-xmark me-2"></i>
                    <?= $sec['noMsg'] ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- New trip CTA -->
        <div class="text-center mt-4 pt-2">
            <a href="create-trip.php" class="btn btn-primary" id="newTripCta">
                <i class="fa-solid fa-plus me-2"></i> Plan a New Trip
            </a>
        </div>

    </div>
</div>
<?php endif; ?>

<!-- ══ Delete Confirmation Modal ═════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1"
     aria-labelledby="deleteModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                    Delete Trip
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete
                    <strong id="deleteTripName"></strong>?
                </p>
                <p class="text-danger small mb-0">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    This will permanently delete all stops and activities. This cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn"
                        onclick="executeDelete()">
                    <span class="btn-text"><i class="fa-solid fa-trash me-1"></i> Delete</span>
                    <span class="btn-spinner"><span class="spinner-border spinner-border-sm"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Share Modal ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="shareModal" tabindex="-1"
     aria-labelledby="shareModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">
                    <i class="fa-solid fa-share-nodes text-success me-2"></i>
                    Share Trip
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Share this trip with friends:</p>
                <div class="share-url-box" id="shareUrlBox"></div>
                <div class="share-btns">
                    <button type="button" class="btn-share-option btn-copy-url" id="copyUrlBtn"
                            onclick="copyShareUrl()">
                        <i class="fa-regular fa-copy"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAB -->
<a href="create-trip.php" class="gt-fab" id="fabNewTrip"
   title="Plan a New Trip" aria-label="Plan a new trip">
    <i class="fa-solid fa-plus"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    'use strict';

    const SITE_URL = '<?= SITE_URL ?>';
    let pendingDeleteId = null;
    let shareUrl        = '';

    /* ═══════════════════════════════════════════════════════════════════
       FILTER TABS (client-side show/hide)
    ═══════════════════════════════════════════════════════════════════ */
    const tabs = document.querySelectorAll('.filter-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
            this.classList.add('active');
            this.setAttribute('aria-selected','true');

            const filter = this.dataset.filter;
            const sections = document.querySelectorAll('.status-section');
            sections.forEach(sec => {
                if (filter === 'all' || sec.id === `section-${filter}`) {
                    sec.style.display = '';
                } else {
                    sec.style.display = 'none';
                }
            });
        });
    });

    /* ═══════════════════════════════════════════════════════════════════
       SORT (client-side)
    ═══════════════════════════════════════════════════════════════════ */
    document.getElementById('sortSelect')?.addEventListener('change', function() {
        const val = this.value;
        const sections = ['ongoing','upcoming','completed'];
        sections.forEach(status => {
            const container = document.getElementById(`tripsList-${status}`);
            if (!container) return;
            const cards = Array.from(container.querySelectorAll('.trip-hcard'));
            cards.sort((a, b) => {
                const nameA = (a.querySelector('.trip-hcard-name')?.textContent || '').trim();
                const nameB = (b.querySelector('.trip-hcard-name')?.textContent || '').trim();
                const dateA = a.dataset.date || '';
                const dateB = b.dataset.date || '';
                if (val === 'az')     return nameA.localeCompare(nameB);
                if (val === 'oldest') return dateA.localeCompare(dateB);
                if (val === 'date')   return dateA.localeCompare(dateB);
                return dateB.localeCompare(dateA); // newest
            });
            cards.forEach(c => container.appendChild(c));
        });
    });

    /* ═══════════════════════════════════════════════════════════════════
       DELETE
    ═══════════════════════════════════════════════════════════════════ */
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    window.confirmDelete = function(tripId, tripName) {
        pendingDeleteId = tripId;
        document.getElementById('deleteTripName').textContent = tripName;
        deleteModal.show();
    };

    window.executeDelete = async function() {
        if (!pendingDeleteId) return;
        const btn = document.getElementById('confirmDeleteBtn');
        btn.classList.add('btn-loading');
        btn.disabled = true;

        try {
            const res  = await fetch(`${SITE_URL}/api/trips.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: pendingDeleteId }),
            });
            const data = await res.json();

            if (data.success) {
                deleteModal.hide();
                const card = document.getElementById(`tripCard${pendingDeleteId}`);
                if (card) {
                    card.classList.add('removing');
                    setTimeout(() => {
                        card.remove();
                        updateTabBadges();
                    }, 380);
                }
                pendingDeleteId = null;
            } else {
                alert('Failed to delete. Please try again.');
            }
        } catch(e) {
            alert('Network error. Please try again.');
        } finally {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
        }
    };

    function updateTabBadges() {
        const statuses = ['ongoing','upcoming','completed'];
        let total = 0;
        statuses.forEach(s => {
            const cnt = document.querySelectorAll(`#tripsList-${s} .trip-hcard`).length;
            const badge = document.querySelector(`#tab${s.charAt(0).toUpperCase()+s.slice(1)} .tab-badge`);
            if (badge) badge.textContent = cnt;
            total += cnt;
        });
        const allBadge = document.querySelector('#tabAll .tab-badge');
        if (allBadge) allBadge.textContent = total;
    }

    /* ═══════════════════════════════════════════════════════════════════
       SHARE
    ═══════════════════════════════════════════════════════════════════ */
    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));

    window.openShare = function(tripId, tripName) {
        shareUrl = `${SITE_URL}/itinerary-view.php?trip_id=${tripId}`;
        document.getElementById('shareUrlBox').textContent = shareUrl;
        document.getElementById('copyUrlBtn').classList.remove('copied');
        document.getElementById('copyUrlBtn').innerHTML = '<i class="fa-regular fa-copy"></i> Copy Link';
        shareModal.show();
    };

    window.copyShareUrl = async function() {
        try {
            await navigator.clipboard.writeText(shareUrl);
            const btn = document.getElementById('copyUrlBtn');
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy Link';
            }, 2500);
        } catch(e) {
            // Fallback
            const el = document.getElementById('shareUrlBox');
            const sel = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(el);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    };

</script>
<script src="<?= SITE_URL ?>/assets/js/trips.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
