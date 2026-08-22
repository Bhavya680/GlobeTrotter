<?php
// ── Bootstrap ──────────────────────────────────────────────────────────────
$pageTitle    = 'Plan a New Trip — GlobeTrotter';
$loadTripsCSS = true;
require_once __DIR__ . '/includes/auth.php';
$userId = require_login_page();
$user = current_user();
// ── Edit mode: pre-fill if trip_id provided ───────────────────────────────
$editMode  = false;
$trip      = null;
$tripStops = [];
$tripId    = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if ($tripId) {
    $ts = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
    $ts->execute([$tripId, $userId]);
    $trip = $ts->fetch();
    if ($trip) {
        $editMode  = true;
        $pageTitle = 'Edit Trip — GlobeTrotter';
        $stopsQ    = $pdo->prepare(
            "SELECT ts.*, c.name AS city_name, c.country
             FROM trip_stops ts JOIN cities c ON c.id = ts.city_id
             WHERE ts.trip_id = ? ORDER BY ts.order_index"
        );
        $stopsQ->execute([$tripId]);
        $tripStops = $stopsQ->fetchAll();
    }
}

// ── POST: Save trip ───────────────────────────────────────────────────────
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tripName    = trim($_POST['trip_name']    ?? '');
    $startDate   = $_POST['start_date']        ?? '';
    $endDate     = $_POST['end_date']          ?? '';
    $description = trim($_POST['description']  ?? '');
    $visibility  = in_array($_POST['visibility'] ?? '', ['public','private'])
                   ? $_POST['visibility'] : 'private';

    // Server-side validation
    if (empty($tripName))           $errors['trip_name']   = 'Trip name is required.';
    if (strlen($tripName) > 255)    $errors['trip_name']   = 'Max 255 characters.';
    if (empty($startDate))          $errors['start_date']  = 'Start date is required.';
    if (empty($endDate))            $errors['end_date']    = 'End date is required.';
    if ($startDate && $endDate && $endDate < $startDate)
                                    $errors['end_date']    = 'End date must be after start date.';
    if (strlen($description) > 500) $errors['description'] = 'Max 500 characters.';

    // Cover photo
    $coverPhoto = $trip['cover_photo'] ?? null;
    if (!empty($_FILES['cover_photo']['name'])) {
        $uploaded = uploadFile($_FILES['cover_photo'], 'covers');
        if ($uploaded) { $coverPhoto = $uploaded; }
        else           { $errors['cover_photo'] = 'Invalid file. Max 2MB, jpg/png/webp only.'; }
    }

    // Parse stops
    $stops          = [];
    $stopCityIds    = $_POST['stop_city_id']       ?? [];
    $stopArrivals   = $_POST['stop_arrival_date']  ?? [];
    $stopDepartures = $_POST['stop_departure_date'] ?? [];
    $stopNotes      = $_POST['stop_notes']         ?? [];
    $stopActivityJs = $_POST['stop_activities']    ?? [];

    foreach ($stopCityIds as $i => $cid) {
        if (!empty($cid)) {
            $stops[] = [
                'city_id'        => (int)$cid,
                'arrival_date'   => $stopArrivals[$i]   ?? null,
                'departure_date' => $stopDepartures[$i] ?? null,
                'notes'          => trim($stopNotes[$i] ?? ''),
                'activities'     => json_decode($stopActivityJs[$i] ?? '[]', true) ?: [],
                'order_index'    => $i,
            ];
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $status = getTripStatus($startDate, $endDate);

            if ($editMode && $tripId) {
                $upd = $pdo->prepare(
                    "UPDATE trips SET trip_name=?,description=?,start_date=?,end_date=?,
                     cover_photo=?,visibility=?,status=? WHERE id=? AND user_id=?"
                );
                $upd->execute([$tripName,$description?:null,$startDate,$endDate,
                               $coverPhoto,$visibility,$status,$tripId,$userId]);
                $pdo->prepare("DELETE FROM trip_stops WHERE trip_id=?")->execute([$tripId]);
                $savedTripId = $tripId;
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO trips (user_id,trip_name,description,start_date,end_date,
                     cover_photo,visibility,status) VALUES (?,?,?,?,?,?,?,?) RETURNING id"
                );
                $ins->execute([$userId,$tripName,$description?:null,$startDate,$endDate,
                               $coverPhoto,$visibility,$status]);
                $savedTripId = (int)$ins->fetchColumn();
            }

            foreach ($stops as $stop) {
                $si = $pdo->prepare(
                    "INSERT INTO trip_stops (trip_id,city_id,arrival_date,departure_date,order_index,notes)
                     VALUES (?,?,?,?,?,?) RETURNING id"
                );
                $si->execute([
                    $savedTripId,
                    $stop['city_id'],
                    $stop['arrival_date'] ?: null,
                    $stop['departure_date'] ?: null,
                    $stop['order_index'],
                    $stop['notes'] ?: null,
                ]);
                $stopId = (int)$si->fetchColumn();

                foreach ($stop['activities'] as $actId) {
                    $actId = (int)$actId;
                    if ($actId > 0) {
                        $ai = $pdo->prepare(
                            "INSERT INTO trip_activities (trip_stop_id,activity_id)
                             VALUES (?,?) ON CONFLICT DO NOTHING"
                        );
                        $ai->execute([$stopId, $actId]);
                    }
                }
            }

            $pdo->commit();
            setFlash('success', $editMode ? 'Trip updated!' : 'Trip created! Start building your itinerary.');
            redirect("itinerary-builder.php?trip_id={$savedTripId}");

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['db'] = 'Something went wrong. Please try again.';
        }
    }
}

$renderCount = max(1, count($tripStops));
require_once __DIR__ . '/includes/header.php';
?>

<!-- ══ Page Header ════════════════════════════════════════════════════════ -->
<div class="gt-page-header">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="my-trips.php">My Trips</a></li>
                <li class="breadcrumb-item active"><?= $editMode ? 'Edit Trip' : 'New Trip' ?></li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1>
                    <i class="fa-solid fa-<?= $editMode ? 'pen-to-square' : 'route' ?> me-2"></i>
                    <?= $editMode ? 'Edit Your Trip' : 'Plan a New Trip' ?>
                </h1>
                <p class="page-subtitle mb-0">
                    <?= $editMode
                        ? 'Update your trip details and stops below.'
                        : "Fill in the details, add stops, and pick activities — we'll help you build the perfect itinerary." ?>
                </p>
            </div>
            <?php if (!$editMode): ?>
            <button type="button" class="btn btn-light btn-sm fw-semibold shadow-sm text-primary" id="btnAutoFillRandom">
                <i class="fa-solid fa-dice text-warning me-1"></i> Auto-Fill Random Details
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ Main Content ═══════════════════════════════════════════════════════ -->
<div style="background: var(--gt-light); min-height: 70vh; padding-bottom: 3rem;">
<div class="container py-4">

    <?php if (!empty($errors['db'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <?= htmlspecialchars($errors['db']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="createTripForm" novalidate>

        <div class="row g-4">

            <!-- ── Left: Main Details + Stops + Activities ─────────────── -->
            <div class="col-lg-8">

                <!-- Trip Details Card -->
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="fa-solid fa-info-circle"></i> Trip Details
                    </div>

                    <!-- Trip Name -->
                    <div class="mb-3">
                        <label for="trip_name" class="form-label">
                            Trip Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control <?= isset($errors['trip_name']) ? 'is-invalid' : '' ?>"
                               id="trip_name" name="trip_name"
                               value="<?= htmlspecialchars($trip['trip_name'] ?? ($_POST['trip_name'] ?? '')) ?>"
                               placeholder="e.g., Paris Summer 2024"
                               maxlength="255" required>
                        <?php if (isset($errors['trip_name'])): ?>
                        <div class="text-danger small mt-1"><?= htmlspecialchars($errors['trip_name']) ?></div>
                        <?php endif; ?>
                        <div class="char-counter"><span id="nameCount">0</span>/255</div>
                    </div>

                    <!-- Dates -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                                   id="start_date" name="start_date"
                                   value="<?= htmlspecialchars($trip['start_date'] ?? ($_POST['start_date'] ?? '')) ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                            <?php if (isset($errors['start_date'])): ?>
                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['start_date']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>"
                                   id="end_date" name="end_date"
                                   value="<?= htmlspecialchars($trip['end_date'] ?? ($_POST['end_date'] ?? '')) ?>"
                                   required>
                            <?php if (isset($errors['end_date'])): ?>
                            <div class="text-danger small mt-1"><?= htmlspecialchars($errors['end_date']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Description <span class="text-muted fw-normal small">(optional)</span>
                        </label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description"
                                  rows="3" maxlength="500"
                                  placeholder="What's special about this trip?"><?= htmlspecialchars($trip['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                        <div class="text-danger small mt-1"><?= htmlspecialchars($errors['description']) ?></div>
                        <?php endif; ?>
                        <div class="char-counter"><span id="descCount">0</span>/500</div>
                    </div>

                    <!-- Visibility -->
                    <div>
                        <label class="form-label">Visibility</label>
                        <?php $curVis = $trip['visibility'] ?? ($_POST['visibility'] ?? 'private'); ?>
                        <div class="visibility-toggle">
                            <label class="vis-option <?= $curVis==='private'?'selected':'' ?>">
                                <input type="radio" name="visibility" value="private"
                                       <?= $curVis==='private'?'checked':'' ?>>
                                <span class="vis-icon">🔒</span>
                                <span class="vis-label">Private</span>
                            </label>
                            <label class="vis-option <?= $curVis==='public'?'selected':'' ?>">
                                <input type="radio" name="visibility" value="public"
                                       <?= $curVis==='public'?'checked':'' ?>>
                                <span class="vis-icon">🌍</span>
                                <span class="vis-label">Public</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Stops Card -->
                <div class="form-card">
                    <div class="form-card-title">
                        <i class="fa-solid fa-map-pin"></i> Add Stops
                    </div>
                    <div class="stops-section" id="stopsContainer">
                        <?php for ($si = 0; $si < $renderCount; $si++):
                            $es = $tripStops[$si] ?? null;
                        ?>
                        <div class="stop-row" id="stopRow<?= $si ?>" data-stop-index="<?= $si ?>">
                            <div class="stop-header">
                                <span class="stop-number">Stop <?= $si + 1 ?></span>
                                <?php if ($si > 0): ?>
                                <button type="button" class="stop-remove-btn"
                                        onclick="removeStop(<?= $si ?>)" aria-label="Remove stop">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">City / Destination</label>
                                    <div class="city-ac-wrap">
                                        <input type="text"
                                               class="form-control city-ac-input"
                                               placeholder="Search for a city…"
                                               value="<?= $es ? htmlspecialchars($es['city_name'].', '.$es['country']) : '' ?>"
                                               autocomplete="off"
                                               data-stop="<?= $si ?>"
                                               id="cityInput<?= $si ?>">
                                        <input type="hidden" name="stop_city_id[]"
                                               id="cityId<?= $si ?>"
                                               value="<?= $es ? (int)$es['city_id'] : '' ?>">
                                        <div class="city-ac-dropdown" id="cityAc<?= $si ?>"></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Arrival Date</label>
                                    <input type="date" class="form-control" name="stop_arrival_date[]"
                                           value="<?= $es ? htmlspecialchars($es['arrival_date']) : '' ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Departure Date</label>
                                    <input type="date" class="form-control" name="stop_departure_date[]"
                                           value="<?= $es ? htmlspecialchars($es['departure_date']) : '' ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                                    <input type="text" class="form-control" name="stop_notes[]"
                                           value="<?= $es ? htmlspecialchars($es['notes'] ?? '') : '' ?>"
                                           placeholder="Hotel name, address, reminders…">
                                </div>
                                <input type="hidden" name="stop_activities[]" id="stopActivities<?= $si ?>" value="[]">
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <button type="button" class="btn-add-stop mt-1" id="addStopBtn">
                        <i class="fa-solid fa-plus"></i> Add Another Stop
                    </button>
                </div>

                <!-- Activity Suggestions -->
                <div class="activities-section mb-4">
                    <div class="activities-section-title">
                        <i class="fa-solid fa-star"></i> Suggested Activities
                    </div>
                    <p class="activities-hint">
                        Select a city in any stop above to see activity suggestions.
                        Click <strong>Add to Trip</strong> to include them in that stop's itinerary.
                    </p>
                    <div class="row g-3" id="activitiesGrid">
                        <div class="col-12">
                            <div class="activities-empty-state" id="activitiesPlaceholder">
                                <i class="fa-solid fa-compass"></i>
                                Pick a destination above to see suggested activities.
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Right: Cover Photo + Summary ───────────────────────── -->
            <div class="col-lg-4">
                <div class="form-card" style="position: sticky; top: 80px;">
                    <div class="form-card-title">
                        <i class="fa-solid fa-image"></i> Cover Photo
                    </div>

                    <div class="cover-preview-wrap <?= !empty($trip['cover_photo']) ? 'has-image' : '' ?>"
                         id="coverWrap"
                         onclick="document.getElementById('cover_photo').click()"
                         role="button" tabindex="0" aria-label="Upload cover photo">
                        <div class="cover-placeholder">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Click to upload<br>
                                  <small class="text-muted">JPG, PNG, WebP · max 2 MB</small></span>
                        </div>
                        <img id="coverPreview"
                             src="<?= !empty($trip['cover_photo'])
                                 ? SITE_URL.'/assets/uploads/covers/'.htmlspecialchars($trip['cover_photo'])
                                 : '' ?>"
                             alt="Cover preview">
                        <button type="button" class="cover-remove-btn" id="coverRemoveBtn"
                                onclick="event.stopPropagation(); removeCover()"
                                aria-label="Remove cover photo">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <input type="file" id="cover_photo" name="cover_photo"
                           accept="image/jpeg,image/png,image/webp" class="d-none">
                    <?php if (isset($errors['cover_photo'])): ?>
                    <div class="text-danger small mt-2"><?= htmlspecialchars($errors['cover_photo']) ?></div>
                    <?php endif; ?>

                    <hr class="my-3">

                    <div class="small text-muted">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-clock text-primary"></i>
                            <span id="summaryDuration">Select dates to see duration</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-map-pin text-primary"></i>
                            <span id="summaryStops">No stops added yet</span>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /row -->

        <!-- Submit area -->
        <div class="form-submit-area mt-2">
            <button type="submit" class="btn-submit-trip" id="submitBtn">
                <span class="btn-text">
                    <i class="fa-solid fa-<?= $editMode ? 'floppy-disk' : 'paper-plane' ?> me-1"></i>
                    <?= $editMode ? 'Save Changes' : 'Create Trip' ?>
                </span>
                <span class="btn-spinner">
                    <span class="spinner-border spinner-border-sm me-1"></span> Saving…
                </span>
            </button>
            <a href="<?= $editMode ? 'my-trips.php' : 'dashboard.php' ?>"
               class="btn btn-outline-secondary">Cancel</a>
            <?php if ($editMode): ?>
            <span class="text-muted small ms-auto">
                <i class="fa-solid fa-info-circle me-1"></i>
                Editing: <?= htmlspecialchars($trip['trip_name']) ?>
            </span>
            <?php endif; ?>
        </div>

    </form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    const SITE_URL = '<?= SITE_URL ?>';
    let stopCount = <?= $renderCount ?>;
    const selectedActivities = {};

    /* ── City Autocomplete ────────────────────────────────────────────── */
    function initCityAC(idx) {
        const input    = document.getElementById(`cityInput${idx}`);
        const hidden   = document.getElementById(`cityId${idx}`);
        const dropdown = document.getElementById(`cityAc${idx}`);
        if (!input || !dropdown) return;
        let timer;

        input.addEventListener('input', function () {
            clearTimeout(timer);
            hidden.value = '';
            const q = this.value.trim();
            if (q.length < 2) { dropdown.innerHTML = ''; dropdown.classList.remove('visible'); return; }

            timer = setTimeout(async () => {
                try {
                    const res  = await fetch(`${SITE_URL}/api/cities.php?search=${encodeURIComponent(q)}&limit=8`);
                    const data = await res.json();
                    if (!data.success || !data.data.length) {
                        dropdown.innerHTML = `<div class="city-ac-item" style="color:#94a3b8;cursor:default">No cities found</div>`;
                        dropdown.classList.add('visible');
                        return;
                    }
                    dropdown.innerHTML = data.data.map(c =>
                        `<div class="city-ac-item"
                              data-id="${c.id}"
                              data-name="${esc(c.name)}"
                              data-country="${esc(c.country||'')}">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>${esc(c.name)} <span class="ac-country">${esc(c.country||'')}</span></span>
                        </div>`
                    ).join('');
                    dropdown.classList.add('visible');

                    dropdown.querySelectorAll('.city-ac-item').forEach(item => {
                        item.addEventListener('click', function () {
                            input.value  = this.dataset.country
                                ? `${this.dataset.name}, ${this.dataset.country}`
                                : this.dataset.name;
                            hidden.value = this.dataset.id;
                            dropdown.classList.remove('visible');
                            fetchActivities(this.dataset.id, idx);
                            updateStopSummary();
                        });
                    });
                } catch(e) { console.error(e); }
            }, 280);
        });

        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !dropdown.contains(e.target))
                dropdown.classList.remove('visible');
        });
    }

    /* ── Activity Suggestions ─────────────────────────────────────────── */
    const catIcons = {
        sightseeing:'fa-landmark', food:'fa-utensils',
        adventure:'fa-person-hiking', culture:'fa-masks-theater',
        shopping:'fa-bag-shopping', wellness:'fa-spa'
    };

    async function fetchActivities(cityId, stopIdx) {
        const grid = document.getElementById('activitiesGrid');
        grid.innerHTML = Array(4).fill(
            `<div class="col-6 col-md-3"><div class="activity-skeleton"></div></div>`
        ).join('');

        try {
            const res  = await fetch(`${SITE_URL}/api/activities.php?city_id=${cityId}`);
            const data = await res.json();
            if (!data.success || !data.data.length) {
                grid.innerHTML = `<div class="col-12"><div class="activities-empty-state">
                    <i class="fa-solid fa-face-meh"></i>No activities listed for this city yet.</div></div>`;
                return;
            }
            if (!selectedActivities[stopIdx]) selectedActivities[stopIdx] = new Set();
            const sel = selectedActivities[stopIdx];

            grid.innerHTML = data.data.map(act => {
                const icon  = catIcons[act.category] || 'fa-star';
                const cost  = parseFloat(act.cost) === 0 ? 'Free' : `$${parseFloat(act.cost).toFixed(0)}`;
                const dur   = act.duration_hours ? `${act.duration_hours}h` : '';
                const isSel = sel.has(String(act.id));
                return `
                <div class="col-6 col-md-3">
                    <div class="activity-card${isSel?' selected':''}" id="actCard${act.id}" data-id="${act.id}">
                        <div class="activity-card-img">
                            ${act.image_url
                                ? `<img src="${esc(act.image_url)}" alt="${esc(act.name)}" loading="lazy">`
                                : `<i class="fa-solid ${icon}"></i>`}
                        </div>
                        <div class="activity-check"><i class="fa-solid fa-check"></i></div>
                        <div class="activity-card-body">
                            <div class="activity-name" title="${esc(act.name)}">${esc(act.name)}</div>
                            <div class="activity-meta">
                                <span class="activity-badge badge-${act.category||'sightseeing'}">${esc(act.category||'')}</span>
                                <span class="activity-cost">${cost}</span>
                            </div>
                            ${dur?`<div class="activity-dur"><i class="fa-regular fa-clock me-1"></i>${dur}</div>`:''}
                            <button type="button" class="btn-add-activity mt-2" id="actBtn${act.id}"
                                    onclick="toggleAct('${act.id}',${stopIdx})">
                                ${isSel?'✓ Added':'+ Add to Trip'}
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        } catch(e) { console.error(e); }
    }

    window.toggleAct = function(actId, stopIdx) {
        if (!selectedActivities[stopIdx]) selectedActivities[stopIdx] = new Set();
        const sel  = selectedActivities[stopIdx];
        const card = document.getElementById(`actCard${actId}`);
        const btn  = document.getElementById(`actBtn${actId}`);
        if (sel.has(String(actId))) {
            sel.delete(String(actId));
            card.classList.remove('selected');
            btn.textContent = '+ Add to Trip';
        } else {
            sel.add(String(actId));
            card.classList.add('selected');
            btn.textContent = '✓ Added';
        }
        const hidden = document.getElementById(`stopActivities${stopIdx}`);
        if (hidden) hidden.value = JSON.stringify(Array.from(sel));
    };

    /* ── Dynamic Stops ────────────────────────────────────────────────── */
    document.getElementById('addStopBtn').addEventListener('click', function() {
        const idx = stopCount;
        const tpl = `
        <div class="stop-row" id="stopRow${idx}" data-stop-index="${idx}">
            <div class="stop-header">
                <span class="stop-number">Stop ${idx+1}</span>
                <button type="button" class="stop-remove-btn" onclick="removeStop(${idx})" aria-label="Remove stop">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">City / Destination</label>
                    <div class="city-ac-wrap">
                        <input type="text" class="form-control city-ac-input"
                               placeholder="Search for a city…" autocomplete="off"
                               data-stop="${idx}" id="cityInput${idx}">
                        <input type="hidden" name="stop_city_id[]" id="cityId${idx}" value="">
                        <div class="city-ac-dropdown" id="cityAc${idx}"></div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Arrival Date</label>
                    <input type="date" class="form-control" name="stop_arrival_date[]">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Departure Date</label>
                    <input type="date" class="form-control" name="stop_departure_date[]">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                    <input type="text" class="form-control" name="stop_notes[]"
                           placeholder="Hotel name, address, reminders…">
                </div>
                <input type="hidden" name="stop_activities[]" id="stopActivities${idx}" value="[]">
            </div>
        </div>`;
        document.getElementById('stopsContainer').insertAdjacentHTML('beforeend', tpl);
        initCityAC(idx);
        stopCount++;
        updateStopSummary();
    });

    window.removeStop = function(idx) {
        const row = document.getElementById(`stopRow${idx}`);
        if (!row) return;
        row.style.opacity = '0';
        row.style.transform = 'translateY(-8px)';
        row.style.transition = 'all 0.2s';
        setTimeout(() => { row.remove(); renumberStops(); updateStopSummary(); }, 200);
    };

    function renumberStops() {
        document.querySelectorAll('.stop-row').forEach((r, i) => {
            const lbl = r.querySelector('.stop-number');
            if (lbl) lbl.textContent = `Stop ${i+1}`;
        });
    }

    function updateStopSummary() {
        const count = [...document.querySelectorAll('[id^="cityId"]')].filter(e => e.value).length;
        const el = document.getElementById('summaryStops');
        if (el) el.textContent = count ? `${count} stop${count>1?'s':''} added` : 'No stops added yet';
    }

    /* ── Cover Photo ──────────────────────────────────────────────────── */
    document.getElementById('cover_photo').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('coverPreview').src = e.target.result;
            document.getElementById('coverWrap').classList.add('has-image');
        };
        reader.readAsDataURL(file);
    });

    window.removeCover = function() {
        document.getElementById('cover_photo').value = '';
        document.getElementById('coverPreview').src  = '';
        document.getElementById('coverWrap').classList.remove('has-image');
    };

    /* ── Visibility Toggle ────────────────────────────────────────────── */
    document.querySelectorAll('.vis-option input').forEach(r => {
        r.addEventListener('change', function() {
            document.querySelectorAll('.vis-option').forEach(l => l.classList.remove('selected'));
            this.closest('.vis-option').classList.add('selected');
        });
    });

    /* ── Char Counters ────────────────────────────────────────────────── */
    function counter(inId, cntId) {
        const el = document.getElementById(inId), cnt = document.getElementById(cntId);
        if (!el || !cnt) return;
        const u = () => cnt.textContent = el.value.length;
        el.addEventListener('input', u); u();
    }
    counter('trip_name',   'nameCount');
    counter('description', 'descCount');

    /* ── Duration Summary ─────────────────────────────────────────────── */
    function updateDuration() {
        const s = document.getElementById('start_date').value;
        const e = document.getElementById('end_date').value;
        const el = document.getElementById('summaryDuration');
        if (!el) return;
        if (s && e && e >= s) {
            const d = Math.round((new Date(e) - new Date(s)) / 86400000) + 1;
            el.textContent = `${d} day${d>1?'s':''}`;
        } else {
            el.textContent = 'Select dates to see duration';
        }
    }
    ['start_date','end_date'].forEach(id =>
        document.getElementById(id).addEventListener('change', updateDuration)
    );
    updateDuration();

    /* ── Form Validation ──────────────────────────────────────────────── */
    document.getElementById('createTripForm').addEventListener('submit', function(e) {
        let ok = true;
        const name  = document.getElementById('trip_name');
        const start = document.getElementById('start_date');
        const end   = document.getElementById('end_date');
        [name,start,end].forEach(f => f.classList.remove('is-invalid'));

        if (!name.value.trim())  { name.classList.add('is-invalid');  ok = false; }
        if (!start.value)        { start.classList.add('is-invalid'); ok = false; }
        if (!end.value)          { end.classList.add('is-invalid');   ok = false; }
        if (start.value && end.value && end.value < start.value) {
            end.classList.add('is-invalid'); ok = false;
        }
        if (!ok) { e.preventDefault(); name.scrollIntoView({behavior:'smooth',block:'center'}); return; }

        const btn = document.getElementById('submitBtn');
        btn.classList.add('btn-loading');
        btn.disabled = true;
    });

    /* ── Init ─────────────────────────────────────────────────────────── */
    for (let i = 0; i < stopCount; i++) initCityAC(i);
    updateStopSummary();

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
<script src="<?= SITE_URL ?>/assets/js/trips.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
