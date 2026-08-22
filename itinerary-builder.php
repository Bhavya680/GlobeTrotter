<?php
// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page('/login.php');

// ── Validate trip_id ──────────────────────────────────────────────────────────
$tripId = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : 0;
if (!$tripId) {
    header('Location: my-trips.php');
    exit;
}

// ── Fetch trip (must belong to user) ──────────────────────────────────────────
$tripStmt = $pdo->prepare(
    'SELECT id, name, start_date, end_date, description FROM trips WHERE id = ? AND user_id = ?'
);
$tripStmt->execute([$tripId, $userId]);
$trip = $tripStmt->fetch();

if (!$trip) {
    header('Location: my-trips.php');
    exit;
}

// ── Fetch stops with activities ───────────────────────────────────────────────
$stopsStmt = $pdo->prepare('
    SELECT s.id, s.city_id, s.start_date, s.end_date, s.sort_order,
           s.transport_note, s.accommodation, s.accommodation_cost, s.stop_notes,
           c.name AS city_name, c.country AS city_country
    FROM stops s
    JOIN cities c ON c.id = s.city_id
    WHERE s.trip_id = ?
    ORDER BY s.sort_order ASC, s.start_date ASC
');
$stopsStmt->execute([$tripId]);
$stops = $stopsStmt->fetchAll();

if ($stops) {
    $stopIds      = array_column($stops, 'id');
    $placeholders = implode(',', array_fill(0, count($stopIds), '?'));
    $actStmt      = $pdo->prepare("
        SELECT sa.id, sa.stop_id, sa.activity_id, sa.scheduled_date, sa.scheduled_time,
               sa.cost_override, sa.notes,
               a.name, a.category, a.duration_hours,
               COALESCE(sa.cost_override, a.cost) AS effective_cost
        FROM stop_activities sa
        JOIN activities a ON a.id = sa.activity_id
        WHERE sa.stop_id IN ({$placeholders})
        ORDER BY sa.scheduled_date ASC, sa.scheduled_time ASC NULLS LAST
    ");
    $actStmt->execute($stopIds);
    $allActivities = $actStmt->fetchAll();

    $actsByStop = [];
    foreach ($allActivities as $act) {
        $actsByStop[$act['stop_id']][] = $act;
    }
    foreach ($stops as &$stop) {
        $stop['activities'] = $actsByStop[$stop['id']] ?? [];
    }
    unset($stop);
}

// ── Page meta (for header.php) ────────────────────────────────────────────────
$pageTitle       = 'Build Itinerary — ' . htmlspecialchars($trip['name']) . ' — GlobeTrotter';
$loadDashboardCSS = true;
$extraCSS        = [SITE_URL . '/assets/css/itinerary.css'];
$extraHead       = '
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
    const TRIP_DATA    = ' . json_encode(['id' => $trip['id'], 'name' => $trip['name'], 'start_date' => $trip['start_date'], 'end_date' => $trip['end_date']]) . ';
    const INITIAL_STOPS = ' . json_encode(array_values($stops)) . ';
    </script>';

// ── PHP helper: render activity row ──────────────────────────────────────────
function renderActivityRowPHP(array $act): string {
    $name = htmlspecialchars($act['name']);
    $cat  = htmlspecialchars($act['category']);
    $cost = number_format((float) ($act['effective_cost'] ?? 0), 2);
    $time = !empty($act['scheduled_time'])
        ? '<span class="ib-activity-time"><i class="fa-regular fa-clock"></i> ' . htmlspecialchars($act['scheduled_time']) . '</span>'
        : '';
    $saId = (int) $act['id'];
    return <<<HTML
    <div class="ib-activity-row" data-sa-id="{$saId}">
        <span class="ib-activity-name">{$name}</span>
        <span class="ib-activity-badge badge-{$cat}">{$cat}</span>
        {$time}
        <span class="ib-activity-cost">\${$cost}</span>
        <button class="ib-activity-remove" data-sa-id="{$saId}" title="Remove activity" aria-label="Remove {$name}">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    HTML;
}

// ── PHP helper: render a stop card ───────────────────────────────────────────
function renderStopCard(array $stop, int $index, array $trip): string {
    $sid       = (int) $stop['id'];
    $cityId    = (int) $stop['city_id'];
    $city      = htmlspecialchars($stop['city_name']);
    $country   = htmlspecialchars($stop['city_country']);
    $transport = htmlspecialchars($stop['transport_note'] ?? '');
    $accom     = htmlspecialchars($stop['accommodation'] ?? '');
    $accomCost = htmlspecialchars($stop['accommodation_cost'] ?? '');
    $notes     = htmlspecialchars($stop['stop_notes'] ?? '');
    $startDate = htmlspecialchars($stop['start_date']);
    $endDate   = htmlspecialchars($stop['end_date']);
    $tripStart = htmlspecialchars($trip['start_date']);
    $tripEnd   = htmlspecialchars($trip['end_date']);
    $sortOrder = (int) ($stop['sort_order'] ?? $index - 1);

    $actsHTML = '';
    foreach ($stop['activities'] ?? [] as $act) {
        $actsHTML .= renderActivityRowPHP($act);
    }
    if (!$actsHTML) {
        $actsHTML = '<p class="text-muted" style="font-size:0.82rem;margin:0;">No activities added yet.</p>';
    }

    return <<<HTML
    <div class="ib-stop-card" id="stop-{$sid}"
         draggable="true"
         data-stop-id="{$sid}"
         data-city-id="{$cityId}"
         data-sort="{$sortOrder}">

        <div class="ib-stop-header">
            <i class="ib-drag-handle fa-solid fa-grip-vertical" title="Drag to reorder"></i>
            <span class="ib-stop-badge">Stop {$index}</span>
            <div class="ib-stop-title">
                {$city}, <span class="ib-stop-country">{$country}</span>
            </div>
            <div class="ib-stop-actions">
                <button class="ib-btn-icon" data-action="move-up" data-stop-id="{$sid}" title="Move Up" aria-label="Move stop up">
                    <i class="fa-solid fa-chevron-up"></i>
                </button>
                <button class="ib-btn-icon" data-action="move-down" data-stop-id="{$sid}" title="Move Down" aria-label="Move stop down">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <button class="ib-btn-icon danger" data-action="remove-stop" data-stop-id="{$sid}" title="Remove Stop" aria-label="Remove stop">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </div>

        <div class="ib-stop-body">

            <p class="ib-stop-desc">
                <i class="fa-solid fa-circle-info me-1"></i>
                Fill in the date range, activities, transport, and accommodation for your time in <strong>{$city}</strong>.
            </p>

            <div class="ib-stop-meta-row">
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="arrival-{$sid}">Arrival Date</label>
                    <input type="date" class="ib-field-input stop-date" id="arrival-{$sid}"
                           data-stop-id="{$sid}" data-field="start_date"
                           value="{$startDate}" min="{$tripStart}" max="{$tripEnd}"
                           aria-label="Arrival date for {$city}">
                </div>
                <div style="color:#94a3b8;padding-bottom:0.5rem;flex-shrink:0;">→</div>
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="departure-{$sid}">Departure Date</label>
                    <input type="date" class="ib-field-input stop-date" id="departure-{$sid}"
                           data-stop-id="{$sid}" data-field="end_date"
                           value="{$endDate}" min="{$tripStart}" max="{$tripEnd}"
                           aria-label="Departure date for {$city}">
                </div>
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="budget-{$sid}">Stop Budget</label>
                    <div class="ib-input-wrap">
                        <span class="prefix-icon">$</span>
                        <input type="number" class="ib-field-input has-prefix stop-budget" id="budget-{$sid}"
                               data-stop-id="{$sid}" value="{$accomCost}"
                               min="0" step="1" placeholder="e.g. 500"
                               aria-label="Budget for {$city}">
                    </div>
                </div>
            </div>

            <div class="ib-section-divider"><i class="fa-solid fa-ticket"></i>Activities</div>
            <div class="ib-activities-list" id="activities-{$sid}">{$actsHTML}</div>
            <button class="ib-add-activity-btn mt-1"
                    data-stop-id="{$sid}"
                    data-city-id="{$cityId}"
                    data-city-name="{$city}"
                    data-start="{$startDate}">
                <i class="fa-solid fa-plus"></i> Add Activity
            </button>

            <div class="ib-section-divider mt-3"><i class="fa-solid fa-route"></i>Transport to This Stop</div>
            <div class="ib-input-wrap">
                <i class="prefix-icon fa-solid fa-bus"></i>
                <textarea class="ib-field-input has-prefix stop-transport" id="transport-{$sid}"
                          data-stop-id="{$sid}"
                          style="min-height:60px;resize:vertical;padding-left:1.75rem;"
                          placeholder="e.g. Fly from London Heathrow to Paris CDG, then Métro to hotel"
                          aria-label="Transport note for {$city}">{$transport}</textarea>
            </div>

            <div class="ib-section-divider mt-3"><i class="fa-solid fa-hotel"></i>Accommodation</div>
            <div class="ib-accom-row">
                <div class="ib-stop-meta-field" style="flex:2;">
                    <label class="ib-field-label" for="accom-name-{$sid}">Hotel / Airbnb Name</label>
                    <div class="ib-input-wrap">
                        <i class="prefix-icon fa-solid fa-bed"></i>
                        <input type="text" class="ib-field-input has-prefix stop-accom" id="accom-name-{$sid}"
                               data-stop-id="{$sid}" value="{$accom}"
                               placeholder="e.g. Hotel de Crillon"
                               aria-label="Accommodation name for {$city}">
                    </div>
                </div>
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="accom-cost-{$sid}">Cost / Night</label>
                    <div class="ib-input-wrap">
                        <span class="prefix-icon">$</span>
                        <input type="number" class="ib-field-input has-prefix stop-accom-cost" id="accom-cost-{$sid}"
                               data-stop-id="{$sid}" value="{$accomCost}"
                               min="0" step="0.01" placeholder="e.g. 150"
                               aria-label="Accommodation cost for {$city}">
                    </div>
                </div>
            </div>

            <button class="ib-notes-toggle" data-stop-id="{$sid}" aria-expanded="false">
                <i class="fa-solid fa-note-sticky"></i>
                Notes &amp; Tips
                <i class="fa-solid fa-chevron-down chevron ms-auto"></i>
            </button>
            <div class="ib-notes-body" id="notes-body-{$sid}">
                <textarea class="ib-notes-textarea stop-notes" id="notes-{$sid}"
                          data-stop-id="{$sid}"
                          placeholder="Add tips, reminders, links, or any other notes for this stop…"
                          aria-label="Notes for {$city}">{$notes}</textarea>
                <div class="ib-autosave-indicator" id="note-save-{$sid}"></div>
            </div>

        </div>
    </div>
    HTML;
}

// ── Include header (outputs <html><head>...<body>) ────────────────────────────
include __DIR__ . '/includes/header.php';
?>

<div class="ib-page-body">

<!-- ===================== PAGE HEADER ===================== -->
<header class="ib-page-header">
    <div class="container-fluid px-3 px-md-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb ib-breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/dashboard.php"><i class="fa-solid fa-house fa-xs me-1"></i>Home</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/my-trips.php">My Trips</a></li>
                <li class="breadcrumb-item active" aria-current="page">Itinerary Builder</li>
            </ol>
        </nav>

        <h1>
            Build Your Itinerary —
            <span class="trip-name-text" id="tripNameDisplay"><?= htmlspecialchars($trip['name']) ?></span>
            <button class="ib-edit-name-btn" id="editTripNameBtn" title="Edit trip name" aria-label="Edit trip name">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        </h1>

        <div class="meta-row">
            <span>
                <i class="fa-solid fa-calendar-days"></i>
                <?= date('D, d M Y', strtotime($trip['start_date'])) ?>
                &nbsp;→&nbsp;
                <?= date('D, d M Y', strtotime($trip['end_date'])) ?>
            </span>
            <span>
                <i class="fa-solid fa-map-pin"></i>
                <span id="stopCountDisplay"><?= count($stops) ?></span> stop<?= count($stops) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <button class="ib-sidebar-toggle" id="sidebarToggle" aria-label="Toggle trip overview">
            <i class="fa-solid fa-map"></i> Trip Overview
        </button>
    </div>
</header>

<!-- Sidebar mobile backdrop -->
<div class="ib-sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

<!-- ===================== LAYOUT ===================== -->
<div class="ib-layout">

    <!-- ============ SIDEBAR ============ -->
    <aside class="ib-sidebar" id="ibSidebar" aria-label="Trip overview sidebar">

        <div class="ib-sidebar-section">
            <p class="ib-sidebar-heading">Trip Overview</p>
            <div class="ib-trip-dates-badge mb-2">
                <i class="fa-solid fa-calendar-range"></i>
                <?= date('d M', strtotime($trip['start_date'])) ?> – <?= date('d M Y', strtotime($trip['end_date'])) ?>
            </div>
        </div>

        <div class="ib-sidebar-section">
            <p class="ib-sidebar-heading">
                Stops <span id="sidebarStopCount" class="text-muted fw-normal">(<?= count($stops) ?>)</span>
            </p>
            <ul class="ib-stop-nav-list" id="sidebarStopList" role="navigation" aria-label="Stop navigation">
                <?php foreach ($stops as $i => $s): ?>
                <li>
                    <a href="#stop-<?= $s['id'] ?>" class="ib-stop-nav-item" data-stop-id="<?= $s['id'] ?>">
                        <span class="ib-stop-nav-num"><?= $i + 1 ?></span>
                        <span><?= htmlspecialchars($s['city_name']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="ib-sidebar-section">
            <p class="ib-sidebar-heading">Total Budget Cap</p>
            <div class="ib-budget-input-wrap">
                <span class="currency-prefix">$</span>
                <input type="number" class="ib-budget-input" id="totalBudgetInput"
                       min="0" step="1" placeholder="Optional limit"
                       aria-label="Total budget cap">
            </div>
            <div id="totalBudgetUsed" class="mt-2 d-none"></div>
        </div>

    </aside>

    <!-- ============ MAIN CONTENT ============ -->
    <main class="ib-main" id="ibMain" aria-label="Itinerary builder main area">

        <div id="stopCardsContainer">
            <?php if (empty($stops)): ?>
            <div id="emptyState" class="text-center py-5">
                <i class="fa-solid fa-map-location-dot fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted fw-semibold mb-1">No stops added yet.</p>
                <p class="text-muted" style="font-size:0.85rem;">Click "Add Another Section" below to start planning your stops.</p>
            </div>
            <?php else: ?>
            <?php foreach ($stops as $i => $s): ?>
            <?= renderStopCard($s, $i + 1, $trip) ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button class="ib-add-section-btn" id="addSectionBtn" aria-label="Add another stop">
            <i class="fa-solid fa-plus"></i>
            Add Another Section
        </button>

    </main>
</div>

<!-- ===================== STICKY SAVE BAR ===================== -->
<div class="ib-sticky-bar" role="region" aria-label="Save controls">
    <span class="ib-save-indicator" id="saveDraftIndicator">
        <i class="fa-regular fa-circle-dot"></i> All changes auto-saved
    </span>
    <button class="ib-btn-secondary" id="saveDraftBtn">
        <i class="fa-regular fa-floppy-disk"></i> Save Draft
    </button>
    <button class="ib-btn-primary" id="continueBtn">
        <i class="fa-solid fa-arrow-right"></i> Continue to View
    </button>
</div>

<!-- ===================== ACTIVITY SEARCH MODAL ===================== -->
<div class="modal fade ib-modal" id="activityModal" tabindex="-1"
     aria-labelledby="activityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityModalLabel">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Search Activities
                    <span id="modalCityLabel" class="ms-2 fw-normal" style="color:rgba(255,255,255,0.7);font-size:0.85rem;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ib-modal-search-bar">
                    <input type="text" class="ib-modal-search-input" id="actSearchInput"
                           placeholder="Search activities…" aria-label="Search activities">
                    <select class="ib-modal-cat-select" id="actCategoryFilter" aria-label="Filter by category">
                        <option value="">All Categories</option>
                        <option value="sightseeing">Sightseeing</option>
                        <option value="food">Food</option>
                        <option value="adventure">Adventure</option>
                        <option value="culture">Culture</option>
                        <option value="relaxation">Relaxation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="ib-activities-results" id="actResultsList" role="list">
                    <div class="ib-modal-empty">
                        <i class="fa-solid fa-compass"></i>
                        Search or pick a category to find activities
                    </div>
                </div>
                <div class="ib-modal-date-row">
                    <label class="ib-modal-date-label" for="actScheduledDate">
                        <i class="fa-solid fa-calendar-check me-1" style="color:var(--ib-primary);"></i>
                        Scheduled Date:
                    </label>
                    <input type="date" class="ib-modal-date-input" id="actScheduledDate"
                           aria-label="Scheduled date for activities">
                </div>
            </div>
            <div class="modal-footer">
                <span id="selectedCountLabel" style="font-size:0.82rem;color:#64748b;margin-right:auto;">0 selected</span>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="ib-add-selected-btn" id="addSelectedBtn" disabled>
                    <i class="fa-solid fa-plus"></i> Add Selected
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== NEW STOP FORM TEMPLATE ===================== -->
<template id="newStopFormTpl">
    <div class="ib-new-stop-card" id="newStopForm">
        <p class="ib-new-stop-title">
            <i class="fa-solid fa-location-dot me-2" style="color:var(--ib-primary);"></i>New Stop
        </p>
        <div class="row g-3">
            <div class="col-12">
                <label class="ib-field-label" for="newCityInput">City <span class="text-danger">*</span></label>
                <div class="ib-city-search-wrap">
                    <input type="text" class="ib-field-input" id="newCityInput"
                           placeholder="Search city…" autocomplete="off" aria-label="City name">
                    <div class="ib-city-autocomplete" id="newCityAutocomplete" role="listbox"></div>
                    <input type="hidden" id="newCityId">
                </div>
            </div>
            <div class="col-md-6">
                <label class="ib-field-label" for="newArrivalDate">Arrival Date <span class="text-danger">*</span></label>
                <input type="date" class="ib-field-input" id="newArrivalDate"
                       min="<?= $trip['start_date'] ?>" max="<?= $trip['end_date'] ?>"
                       aria-label="Arrival date for new stop">
            </div>
            <div class="col-md-6">
                <label class="ib-field-label" for="newDepartureDate">Departure Date <span class="text-danger">*</span></label>
                <input type="date" class="ib-field-input" id="newDepartureDate"
                       min="<?= $trip['start_date'] ?>" max="<?= $trip['end_date'] ?>"
                       aria-label="Departure date for new stop">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <button class="ib-btn-secondary" id="cancelNewStop" type="button">Cancel</button>
                <button class="ib-btn-primary" id="confirmNewStop" type="button">
                    <i class="fa-solid fa-plus"></i> Add Stop
                </button>
            </div>
        </div>
    </div>
</template>

<!-- ===================== TOAST CONTAINER ===================== -->
<div class="ib-toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

</div><!-- /.ib-page-body -->

<!-- ===================== JAVASCRIPT ===================== -->
<script>
/* ============================================================
   GlobeTrotter — Itinerary Builder
   ============================================================ */
'use strict';

// ── Config ───────────────────────────────────────────────────────────────────
const API_STOPS      = '/api/stops.php';
const API_ACTIVITIES = '/api/activities.php';
const API_CITIES     = '/api/cities.php';
const API_TRIPS      = '/api/trips.php';
const TRIP_ID        = TRIP_DATA.id;

// ── State ────────────────────────────────────────────────────────────────────
let stopsData          = [...INITIAL_STOPS];
let activeModalStopId  = null;
let activityModal      = null;
let dragSrcEl          = null;
let noteDebounceTimers = {};
let fieldDebounceTimers = {};

// ── DOM refs ─────────────────────────────────────────────────────────────────
const container      = document.getElementById('stopCardsContainer');
const addSectionBtn  = document.getElementById('addSectionBtn');
const sidebarToggle  = document.getElementById('sidebarToggle');
const sidebar        = document.getElementById('ibSidebar');
const backdrop       = document.getElementById('sidebarBackdrop');
const saveDraftBtn   = document.getElementById('saveDraftBtn');
const continueBtn    = document.getElementById('continueBtn');
const saveIndicator  = document.getElementById('saveDraftIndicator');
const toastContainer = document.getElementById('toastContainer');

// Modal refs
const actSearchInput    = document.getElementById('actSearchInput');
const actCategoryFilter = document.getElementById('actCategoryFilter');
const actResultsList    = document.getElementById('actResultsList');
const actScheduledDate  = document.getElementById('actScheduledDate');
const selectedCountLbl  = document.getElementById('selectedCountLabel');
const addSelectedBtn    = document.getElementById('addSelectedBtn');
const modalCityLabel    = document.getElementById('modalCityLabel');

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
    document.getElementById('activityModal').addEventListener('hidden.bs.modal', resetModal);

    initSidebar();
    initStickyBar();
    initEditTripName();
    bindAllCards();
    initDragDrop();
    initIntersectionObserver();
    updateBudgetSummary();
});

// ── Toast ────────────────────────────────────────────────────────────────────
function toast(msg, type = 'info') {
    const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
    const el = Object.assign(document.createElement('div'), { className: `ib-toast ${type}` });
    el.innerHTML = `<i class="fa-solid ${icons[type]}"></i>${esc(msg)}`;
    toastContainer.appendChild(el);
    setTimeout(() => {
        el.style.cssText = 'opacity:0;transform:translateX(60px);transition:all 0.3s';
        setTimeout(() => el.remove(), 300);
    }, 3200);
}

// ── API ───────────────────────────────────────────────────────────────────────
async function api(method, url, body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    if (!res.ok && res.status >= 500) throw new Error('Server error');
    return res.json();
}

// ── Escape HTML ───────────────────────────────────────────────────────────────
function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Sidebar ───────────────────────────────────────────────────────────────────
function initSidebar() {
    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show');
    });
    backdrop?.addEventListener('click', closeSidebar);
}

function closeSidebar() {
    sidebar.classList.remove('open');
    backdrop.classList.remove('show');
}

function rebuildSidebarNav() {
    const list = document.getElementById('sidebarStopList');
    const countEl = document.getElementById('sidebarStopCount');
    const displayEl = document.getElementById('stopCountDisplay');
    if (!list) return;

    list.innerHTML = stopsData.map((s, i) => `
        <li>
            <a href="#stop-${s.id}" class="ib-stop-nav-item" data-stop-id="${s.id}">
                <span class="ib-stop-nav-num">${i + 1}</span>
                <span>${esc(s.city_name)}</span>
            </a>
        </li>
    `).join('');

    if (countEl) countEl.textContent = `(${stopsData.length})`;
    if (displayEl) displayEl.textContent = stopsData.length;

    list.querySelectorAll('.ib-stop-nav-item').forEach(a => a.addEventListener('click', closeSidebar));
}

// ── Budget summary ────────────────────────────────────────────────────────────
function updateBudgetSummary() {
    const usedEl = document.getElementById('totalBudgetUsed');
    const cap = parseFloat(document.getElementById('totalBudgetInput')?.value || 0);
    let total = 0;
    stopsData.forEach(s => {
        (s.activities || []).forEach(a => { total += parseFloat(a.effective_cost || 0); });
        if (s.accommodation_cost) total += parseFloat(s.accommodation_cost);
    });
    if (usedEl) {
        usedEl.classList.toggle('d-none', cap <= 0);
        if (cap > 0) {
            const pct   = Math.min(100, Math.round((total / cap) * 100));
            const color = pct > 90 ? '#ef4444' : pct > 70 ? '#f59e0b' : '#16a34a';
            usedEl.innerHTML = `
                <span style="color:${color};">$${total.toFixed(2)} / $${cap.toFixed(2)}</span>
                <div style="height:4px;background:#e2e8f0;border-radius:4px;margin-top:4px;">
                    <div style="height:100%;width:${pct}%;background:${color};border-radius:4px;transition:width .3s;"></div>
                </div>`;
        }
    }
}
document.getElementById('totalBudgetInput')?.addEventListener('input', updateBudgetSummary);

// ── Category badge ────────────────────────────────────────────────────────────
function catBadge(cat) {
    return `<span class="ib-activity-badge badge-${esc(cat)}">${esc(cat)}</span>`;
}

// ── Render activity row ───────────────────────────────────────────────────────
function renderActivityRow(act) {
    const time = act.scheduled_time
        ? `<span class="ib-activity-time"><i class="fa-regular fa-clock"></i> ${esc(act.scheduled_time)}</span>` : '';
    return `
        <div class="ib-activity-row" data-sa-id="${act.id}">
            <span class="ib-activity-name">${esc(act.name)}</span>
            ${catBadge(act.category)}
            ${time}
            <span class="ib-activity-cost">$${parseFloat(act.effective_cost).toFixed(2)}</span>
            <button class="ib-activity-remove" data-sa-id="${act.id}" title="Remove" aria-label="Remove ${esc(act.name)}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>`;
}

// ── Render full stop card (for JS-created stops) ──────────────────────────────
function renderStopCardHTML(stop, index) {
    const activitiesHTML = (stop.activities || []).map(renderActivityRow).join('') ||
        '<p class="text-muted" style="font-size:0.82rem;margin:0;">No activities added yet.</p>';
    const dupWarn = stopsData.filter(s => s.city_id == stop.city_id && s.id != stop.id).length > 0
        ? `<span class="ib-warn-badge ms-1"><i class="fa-solid fa-triangle-exclamation"></i> Duplicate city</span>` : '';
    const sid = stop.id;

    return `
    <div class="ib-stop-card" id="stop-${sid}" draggable="true"
         data-stop-id="${sid}" data-city-id="${stop.city_id}" data-sort="${stop.sort_order ?? index - 1}">
        <div class="ib-stop-header">
            <i class="ib-drag-handle fa-solid fa-grip-vertical" title="Drag to reorder"></i>
            <span class="ib-stop-badge">Stop ${index}</span>
            <div class="ib-stop-title">${esc(stop.city_name)}, <span class="ib-stop-country">${esc(stop.city_country)}</span>${dupWarn}</div>
            <div class="ib-stop-actions">
                <button class="ib-btn-icon" data-action="move-up" data-stop-id="${sid}" title="Move Up" aria-label="Move stop up"><i class="fa-solid fa-chevron-up"></i></button>
                <button class="ib-btn-icon" data-action="move-down" data-stop-id="${sid}" title="Move Down" aria-label="Move stop down"><i class="fa-solid fa-chevron-down"></i></button>
                <button class="ib-btn-icon danger" data-action="remove-stop" data-stop-id="${sid}" title="Remove Stop" aria-label="Remove stop"><i class="fa-solid fa-trash-can"></i></button>
            </div>
        </div>
        <div class="ib-stop-body">
            <p class="ib-stop-desc"><i class="fa-solid fa-circle-info me-1"></i>Fill in the date range, activities, transport, and accommodation for your time in <strong>${esc(stop.city_name)}</strong>.</p>
            <div class="ib-stop-meta-row">
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="arrival-${sid}">Arrival Date</label>
                    <input type="date" class="ib-field-input stop-date" id="arrival-${sid}" data-stop-id="${sid}" data-field="start_date" value="${esc(stop.start_date)}" min="${esc(TRIP_DATA.start_date)}" max="${esc(TRIP_DATA.end_date)}" aria-label="Arrival date">
                </div>
                <div style="color:#94a3b8;padding-bottom:0.5rem;flex-shrink:0;">→</div>
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="departure-${sid}">Departure Date</label>
                    <input type="date" class="ib-field-input stop-date" id="departure-${sid}" data-stop-id="${sid}" data-field="end_date" value="${esc(stop.end_date)}" min="${esc(TRIP_DATA.start_date)}" max="${esc(TRIP_DATA.end_date)}" aria-label="Departure date">
                </div>
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="budget-${sid}">Stop Budget</label>
                    <div class="ib-input-wrap">
                        <span class="prefix-icon">$</span>
                        <input type="number" class="ib-field-input has-prefix stop-budget" id="budget-${sid}" data-stop-id="${sid}" value="${stop.accommodation_cost ?? ''}" min="0" step="1" placeholder="e.g. 500" aria-label="Budget">
                    </div>
                </div>
            </div>
            <div class="ib-section-divider"><i class="fa-solid fa-ticket"></i>Activities</div>
            <div class="ib-activities-list" id="activities-${sid}">${activitiesHTML}</div>
            <button class="ib-add-activity-btn mt-1" data-stop-id="${sid}" data-city-id="${stop.city_id}" data-city-name="${esc(stop.city_name)}" data-start="${esc(stop.start_date)}"><i class="fa-solid fa-plus"></i> Add Activity</button>
            <div class="ib-section-divider mt-3"><i class="fa-solid fa-route"></i>Transport to This Stop</div>
            <div class="ib-input-wrap"><i class="prefix-icon fa-solid fa-bus"></i><textarea class="ib-field-input has-prefix stop-transport" id="transport-${sid}" data-stop-id="${sid}" style="min-height:60px;resize:vertical;padding-left:1.75rem;" placeholder="e.g. Fly from London to Paris, then metro to hotel" aria-label="Transport note">${esc(stop.transport_note ?? '')}</textarea></div>
            <div class="ib-section-divider mt-3"><i class="fa-solid fa-hotel"></i>Accommodation</div>
            <div class="ib-accom-row">
                <div class="ib-stop-meta-field" style="flex:2;">
                    <label class="ib-field-label" for="accom-name-${sid}">Hotel / Airbnb Name</label>
                    <div class="ib-input-wrap"><i class="prefix-icon fa-solid fa-bed"></i><input type="text" class="ib-field-input has-prefix stop-accom" id="accom-name-${sid}" data-stop-id="${sid}" value="${esc(stop.accommodation ?? '')}" placeholder="e.g. Hotel de Crillon" aria-label="Accommodation name"></div>
                </div>
                <div class="ib-stop-meta-field">
                    <label class="ib-field-label" for="accom-cost-${sid}">Cost / Night</label>
                    <div class="ib-input-wrap"><span class="prefix-icon">$</span><input type="number" class="ib-field-input has-prefix stop-accom-cost" id="accom-cost-${sid}" data-stop-id="${sid}" value="${stop.accommodation_cost ?? ''}" min="0" step="0.01" placeholder="e.g. 150" aria-label="Accommodation cost"></div>
                </div>
            </div>
            <button class="ib-notes-toggle" data-stop-id="${sid}" aria-expanded="false"><i class="fa-solid fa-note-sticky"></i>Notes &amp; Tips<i class="fa-solid fa-chevron-down chevron ms-auto"></i></button>
            <div class="ib-notes-body" id="notes-body-${sid}">
                <textarea class="ib-notes-textarea stop-notes" id="notes-${sid}" data-stop-id="${sid}" placeholder="Add tips, reminders, or any notes for this stop…" aria-label="Notes">${esc(stop.stop_notes ?? '')}</textarea>
                <div class="ib-autosave-indicator" id="note-save-${sid}"></div>
            </div>
        </div>
    </div>`;
}

// ── Bind all card events ──────────────────────────────────────────────────────
function bindAllCards() {
    container.querySelectorAll('.ib-stop-card').forEach(card => bindCardEvents(card));
}

function bindCardEvents(card) {
    const stopId = parseInt(card.dataset.stopId);

    // Action buttons (move-up, move-down, remove-stop)
    card.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', () => {
            const { action, stopId: sid } = btn.dataset;
            if (action === 'move-up')    moveStop(parseInt(sid), -1);
            if (action === 'move-down')  moveStop(parseInt(sid), +1);
            if (action === 'remove-stop') removeStop(parseInt(sid));
        });
    });

    // Date inputs → save on change
    card.querySelectorAll('.stop-date').forEach(inp => {
        inp.addEventListener('change', () => {
            const sid   = parseInt(inp.dataset.stopId);
            const field = inp.dataset.field;
            if (!inp.value) return;

            // Validate dates
            const arrival   = card.querySelector(`#arrival-${sid}`)?.value;
            const departure = card.querySelector(`#departure-${sid}`)?.value;
            if (arrival && departure && departure < arrival) {
                toast('Departure must be on or after arrival date', 'error');
                inp.value = inp.defaultValue;
                return;
            }
            saveStopField(sid, { [field]: inp.value });
        });
    });

    // Transport – debounced autosave
    const transportEl = card.querySelector('.stop-transport');
    if (transportEl) {
        transportEl.addEventListener('input', debounce(() => {
            saveStopField(stopId, { transport_note: transportEl.value });
        }, 1000));
    }

    // Accommodation name – debounced
    const accomEl = card.querySelector('.stop-accom');
    if (accomEl) {
        accomEl.addEventListener('input', debounce(() => {
            saveStopField(stopId, { accommodation: accomEl.value });
        }, 1000));
    }

    // Accommodation cost – save on change
    const accomCostEl = card.querySelector('.stop-accom-cost');
    if (accomCostEl) {
        accomCostEl.addEventListener('change', () => {
            const v = accomCostEl.value;
            saveStopField(stopId, { accommodation_cost: v === '' ? null : parseFloat(v) });
        });
    }

    // Add Activity button
    card.querySelectorAll('.ib-add-activity-btn').forEach(btn => {
        btn.addEventListener('click', () => openActivityModal(
            parseInt(btn.dataset.stopId),
            parseInt(btn.dataset.cityId),
            btn.dataset.cityName,
            btn.dataset.start
        ));
    });

    // Remove activity buttons
    card.querySelectorAll('.ib-activity-remove').forEach(btn => {
        btn.addEventListener('click', () => removeActivity(parseInt(btn.dataset.saId), card));
    });

    // Notes toggle
    const notesToggle = card.querySelector('.ib-notes-toggle');
    const notesBody   = card.querySelector('.ib-notes-body');
    if (notesToggle && notesBody) {
        notesToggle.addEventListener('click', () => {
            const open = notesBody.classList.toggle('open');
            notesToggle.classList.toggle('open', open);
            notesToggle.setAttribute('aria-expanded', String(open));
        });
    }

    // Notes autosave
    const notesEl = card.querySelector('.stop-notes');
    if (notesEl) {
        notesEl.addEventListener('input', () => {
            const ind = document.getElementById(`note-save-${stopId}`);
            if (ind) { ind.className = 'ib-autosave-indicator saving'; ind.innerHTML = '<i class="fa-solid fa-spinner fa-spin fa-xs"></i> Saving…'; }
            clearTimeout(noteDebounceTimers[stopId]);
            noteDebounceTimers[stopId] = setTimeout(async () => {
                const res = await saveStopField(stopId, { stop_notes: notesEl.value });
                if (ind) {
                    if (res?.success) {
                        ind.className = 'ib-autosave-indicator saved';
                        ind.innerHTML = '<i class="fa-solid fa-check fa-xs"></i> Saved';
                        setTimeout(() => { ind.className = 'ib-autosave-indicator'; ind.innerHTML = ''; }, 2500);
                    } else {
                        ind.className = 'ib-autosave-indicator error';
                        ind.innerHTML = '<i class="fa-solid fa-xmark fa-xs"></i> Error saving';
                    }
                }
            }, 900);
        });
    }
}

// ── Save a stop field via API ─────────────────────────────────────────────────
async function saveStopField(stopId, fields) {
    try {
        const res = await api('PUT', `${API_STOPS}?id=${stopId}`, fields);
        if (!res.success) toast(res.error || 'Save failed', 'error');
        const idx = stopsData.findIndex(s => s.id === stopId);
        if (idx >= 0) Object.assign(stopsData[idx], fields);
        updateBudgetSummary();
        return res;
    } catch (e) {
        toast('Network error', 'error');
        return null;
    }
}

// ── Move stop up / down ───────────────────────────────────────────────────────
async function moveStop(stopId, dir) {
    const idx = stopsData.findIndex(s => s.id === stopId);
    if (idx < 0) return;
    const nIdx = idx + dir;
    if (nIdx < 0 || nIdx >= stopsData.length) return;
    [stopsData[idx], stopsData[nIdx]] = [stopsData[nIdx], stopsData[idx]];
    stopsData.forEach((s, i) => s.sort_order = i);
    reRenderAllCards();
    await persistStopOrder();
}

async function persistStopOrder() {
    try {
        await api('POST', `${API_STOPS}?action=reorder`, { stops: stopsData.map((s, i) => ({ id: s.id, sort_order: i })) });
    } catch {}
}

// ── Remove stop ───────────────────────────────────────────────────────────────
async function removeStop(stopId) {
    if (!confirm('Remove this stop and all its activities? This cannot be undone.')) return;
    try {
        const res = await api('DELETE', `${API_STOPS}?id=${stopId}`);
        if (!res.success) { toast(res.error || 'Failed to remove', 'error'); return; }
        stopsData = stopsData.filter(s => s.id !== stopId);
        stopsData.forEach((s, i) => s.sort_order = i);
        reRenderAllCards();
        toast('Stop removed', 'success');
    } catch { toast('Network error', 'error'); }
}

// ── Remove activity ───────────────────────────────────────────────────────────
async function removeActivity(saId, card) {
    try {
        const res = await api('DELETE', `${API_STOPS}?action=activities&id=${saId}`);
        if (!res.success) { toast(res.error || 'Failed to remove', 'error'); return; }
        const row = card.querySelector(`.ib-activity-row[data-sa-id="${saId}"]`);
        if (row) row.remove();
        const stopId = parseInt(card.dataset.stopId);
        const idx = stopsData.findIndex(s => s.id === stopId);
        if (idx >= 0) stopsData[idx].activities = (stopsData[idx].activities || []).filter(a => a.id !== saId);
        const list = card.querySelector('.ib-activities-list');
        if (list && !list.querySelector('.ib-activity-row')) {
            list.innerHTML = '<p class="text-muted" style="font-size:0.82rem;margin:0;">No activities added yet.</p>';
        }
        updateBudgetSummary();
    } catch { toast('Network error', 'error'); }
}

// ── Re-render all cards ───────────────────────────────────────────────────────
function reRenderAllCards() {
    container.innerHTML = '';
    if (stopsData.length === 0) {
        container.innerHTML = `<div id="emptyState" class="text-center py-5">
            <i class="fa-solid fa-map-location-dot fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted fw-semibold mb-1">No stops added yet.</p>
            <p class="text-muted" style="font-size:0.85rem;">Click "Add Another Section" below to start planning.</p>
        </div>`;
        rebuildSidebarNav();
        return;
    }
    stopsData.forEach((stop, i) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = renderStopCardHTML(stop, i + 1);
        const card = wrapper.firstElementChild;
        container.appendChild(card);
        bindCardEvents(card);
    });
    initDragDrop();
    rebuildSidebarNav();
    initIntersectionObserver();
    updateBudgetSummary();
}

// ── Add Section ───────────────────────────────────────────────────────────────
addSectionBtn.addEventListener('click', () => {
    if (document.getElementById('newStopForm')) return;
    const tpl   = document.getElementById('newStopFormTpl');
    const clone = tpl.content.cloneNode(true);
    container.appendChild(clone);

    const form = document.getElementById('newStopForm');
    initCityAutocomplete(
        document.getElementById('newCityInput'),
        document.getElementById('newCityAutocomplete'),
        document.getElementById('newCityId')
    );
    document.getElementById('cancelNewStop').addEventListener('click', () => form.remove());
    document.getElementById('confirmNewStop').addEventListener('click', () => confirmAddStop(form));
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
});

async function confirmAddStop(form) {
    const cityId    = document.getElementById('newCityId').value;
    const cityInput = document.getElementById('newCityInput').value.trim();
    const start     = document.getElementById('newArrivalDate').value;
    const end       = document.getElementById('newDepartureDate').value;

    if (!cityId)       { toast('Please select a city from the list', 'error'); return; }
    if (!start)        { toast('Arrival date is required', 'error'); return; }
    if (!end)          { toast('Departure date is required', 'error'); return; }
    if (end < start)   { toast('Departure must be after arrival', 'error'); return; }
    if (start < TRIP_DATA.start_date || end > TRIP_DATA.end_date) {
        toast(`Dates must be within trip range (${TRIP_DATA.start_date} – ${TRIP_DATA.end_date})`, 'error');
        return;
    }

    const dup = stopsData.find(s => s.city_id == cityId);
    if (dup && !confirm(`${cityInput} is already in this trip. Add it again?`)) return;

    const btn = document.getElementById('confirmNewStop');
    btn.disabled = true;
    btn.innerHTML = '<span class="ib-spinner"></span> Adding…';

    try {
        const res = await api('POST', `${API_STOPS}?trip_id=${TRIP_ID}`, {
            city_id: parseInt(cityId), start_date: start, end_date: end
        });
        if (!res.success) {
            toast(res.error || 'Failed to add stop', 'error');
            btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Stop';
            return;
        }
        const newStop = { ...res.data, activities: [] };
        stopsData.push(newStop);
        stopsData.forEach((s, i) => s.sort_order = i);
        form.remove();
        reRenderAllCards();
        toast(`${newStop.city_name} added!`, 'success');
        setTimeout(() => document.getElementById(`stop-${newStop.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 120);
    } catch {
        toast('Network error', 'error');
        btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Stop';
    }
}

// ── City Autocomplete ─────────────────────────────────────────────────────────
function initCityAutocomplete(input, dropdown, hiddenId) {
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        hiddenId.value = '';
        const q = input.value.trim();
        if (q.length < 2) { dropdown.classList.remove('show'); return; }
        timer = setTimeout(() => fetchCities(q, dropdown, input, hiddenId), 280);
    });
    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.remove('show');
    }, { capture: true });
}

async function fetchCities(q, dropdown, input, hiddenId) {
    try {
        const res  = await fetch(`${API_CITIES}?q=${encodeURIComponent(q)}&per_page=8`);
        const data = await res.json();
        const cities = data.data?.cities || [];
        if (!cities.length) {
            dropdown.innerHTML = '<div class="ib-city-option" style="color:#94a3b8;cursor:default;">No cities found</div>';
        } else {
            dropdown.innerHTML = cities.map(c => `
                <div class="ib-city-option" data-city-id="${c.id}" data-city-name="${esc(c.name)}" role="option" tabindex="0">
                    <i class="fa-solid fa-location-dot" style="color:var(--ib-primary);font-size:0.8rem;"></i>
                    <span class="city-name">${esc(c.name)}</span>
                    <span class="city-country">${esc(c.country)}</span>
                </div>`).join('');
            dropdown.querySelectorAll('.ib-city-option[data-city-id]').forEach(opt => {
                opt.addEventListener('click', () => {
                    input.value    = opt.dataset.cityName;
                    hiddenId.value = opt.dataset.cityId;
                    dropdown.classList.remove('show');
                });
            });
        }
        dropdown.classList.add('show');
    } catch {}
}

// ── Activity Modal ────────────────────────────────────────────────────────────
function openActivityModal(stopId, cityId, cityName, startDate) {
    activeModalStopId = stopId;
    addSelectedBtn.dataset.cityId = cityId;
    modalCityLabel.textContent = `in ${cityName}`;
    actScheduledDate.value = startDate || TRIP_DATA.start_date;
    actScheduledDate.min = TRIP_DATA.start_date;
    actScheduledDate.max = TRIP_DATA.end_date;
    actSearchInput.value = '';
    actCategoryFilter.value = '';
    selectedCountLbl.textContent = '0 selected';
    addSelectedBtn.disabled = true;
    activityModal.show();
    loadActivities(cityId);
}

function resetModal() {
    activeModalStopId = null;
}

let actSearchTimer;
actSearchInput.addEventListener('input', () => {
    clearTimeout(actSearchTimer);
    actSearchTimer = setTimeout(() => loadActivities(parseInt(addSelectedBtn.dataset.cityId)), 320);
});
actCategoryFilter.addEventListener('change', () => loadActivities(parseInt(addSelectedBtn.dataset.cityId)));

async function loadActivities(cityId) {
    const q   = actSearchInput.value.trim();
    const cat = actCategoryFilter.value;
    let url   = `${API_ACTIVITIES}?city_id=${cityId}&per_page=40`;
    if (q)   url += `&q=${encodeURIComponent(q)}`;
    if (cat) url += `&category=${encodeURIComponent(cat)}`;

    actResultsList.innerHTML = `<div class="ib-modal-empty"><span class="ib-spinner" style="display:block;margin:1rem auto 0.5rem;"></span>Loading…</div>`;

    try {
        const res  = await fetch(url);
        const data = await res.json();
        const acts = data.data?.activities || [];
        if (!acts.length) {
            actResultsList.innerHTML = `<div class="ib-modal-empty"><i class="fa-solid fa-face-frown"></i>No activities found. Try a different search.</div>`;
            return;
        }
        actResultsList.innerHTML = acts.map(a => `
            <div class="ib-activity-result-item" role="listitem">
                <input type="checkbox" id="act-chk-${a.id}" value="${a.id}" aria-label="${esc(a.name)}">
                <label class="ib-act-result-name" for="act-chk-${a.id}">${esc(a.name)}</label>
                <div class="ib-act-result-meta">
                    ${catBadge(a.category)}
                    <span class="ib-act-result-cost">$${parseFloat(a.cost).toFixed(2)}</span>
                    <span class="ib-act-result-duration"><i class="fa-regular fa-clock fa-xs"></i> ${a.duration_hours}h</span>
                </div>
            </div>`).join('');

        actResultsList.querySelectorAll('input[type="checkbox"]').forEach(chk => {
            chk.addEventListener('change', updateSelectedCount);
        });
    } catch {
        actResultsList.innerHTML = `<div class="ib-modal-empty"><i class="fa-solid fa-triangle-exclamation"></i>Failed to load activities.</div>`;
    }
}

function updateSelectedCount() {
    const count = actResultsList.querySelectorAll('input[type="checkbox"]:checked').length;
    selectedCountLbl.textContent = `${count} selected`;
    addSelectedBtn.disabled = count === 0;
}

addSelectedBtn.addEventListener('click', async () => {
    if (!activeModalStopId) return;
    const checked = [...actResultsList.querySelectorAll('input[type="checkbox"]:checked')];
    const ids     = checked.map(c => parseInt(c.value));
    const date    = actScheduledDate.value;
    if (!ids.length) { toast('Select at least one activity', 'error'); return; }
    if (!date)       { toast('Please pick a scheduled date', 'error'); return; }

    addSelectedBtn.disabled = true;
    addSelectedBtn.innerHTML = '<span class="ib-spinner"></span> Adding…';

    try {
        const res = await api('POST', `${API_STOPS}?action=activities&stop_id=${activeModalStopId}`, {
            activity_ids: ids, scheduled_date: date
        });
        if (!res.success) { toast(res.error || 'Failed to add', 'error'); return; }

        const newActivities = Array.isArray(res.data) ? res.data : (res.data ? [res.data] : []);

        // Update local state
        const idx = stopsData.findIndex(s => s.id === activeModalStopId);
        if (idx >= 0) stopsData[idx].activities = [...(stopsData[idx].activities || []), ...newActivities];

        // Update DOM
        const card = document.getElementById(`stop-${activeModalStopId}`);
        if (card) {
            const list     = card.querySelector('.ib-activities-list');
            const emptyMsg = list?.querySelector('p.text-muted');
            if (emptyMsg) emptyMsg.remove();
            newActivities.forEach(act => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = renderActivityRow(act);
                const row = wrapper.firstElementChild;
                list.appendChild(row);
                row.querySelector('.ib-activity-remove')?.addEventListener('click', () => removeActivity(act.id, card));
            });
        }

        activityModal.hide();
        updateBudgetSummary();
        toast(`${newActivities.length} activit${newActivities.length === 1 ? 'y' : 'ies'} added!`, 'success');
    } catch { toast('Network error', 'error'); }
    finally {
        addSelectedBtn.disabled = false;
        addSelectedBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Selected';
    }
});

// ── Drag & Drop ───────────────────────────────────────────────────────────────
function initDragDrop() {
    container.querySelectorAll('.ib-stop-card').forEach(card => {
        card.addEventListener('dragstart', onDragStart);
        card.addEventListener('dragend',   onDragEnd);
        card.addEventListener('dragover',  onDragOver);
        card.addEventListener('dragleave', onDragLeave);
        card.addEventListener('drop',      onDrop);
    });
}

function onDragStart(e) {
    dragSrcEl = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.stopId);
}
function onDragEnd() {
    this.classList.remove('dragging');
    container.querySelectorAll('.ib-stop-card').forEach(c => c.classList.remove('drag-over'));
}
function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (this !== dragSrcEl) this.classList.add('drag-over');
}
function onDragLeave() { this.classList.remove('drag-over'); }
async function onDrop(e) {
    e.stopPropagation();
    this.classList.remove('drag-over');
    if (!dragSrcEl || dragSrcEl === this) return;
    const srcId  = parseInt(dragSrcEl.dataset.stopId);
    const dstId  = parseInt(this.dataset.stopId);
    const srcIdx = stopsData.findIndex(s => s.id === srcId);
    const dstIdx = stopsData.findIndex(s => s.id === dstId);
    if (srcIdx < 0 || dstIdx < 0) return;
    const [moved] = stopsData.splice(srcIdx, 1);
    stopsData.splice(dstIdx, 0, moved);
    stopsData.forEach((s, i) => s.sort_order = i);
    reRenderAllCards();
    await persistStopOrder();
}

// ── Sticky bar ────────────────────────────────────────────────────────────────
function initStickyBar() {
    saveDraftBtn.addEventListener('click', saveDraft);
    continueBtn.addEventListener('click', saveAndContinue);
}

async function saveDraft() {
    saveIndicator.className = 'ib-save-indicator saving';
    saveIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin fa-xs"></i> Saving…';
    saveDraftBtn.disabled = true;

    const promises = [];
    container.querySelectorAll('.ib-stop-card').forEach(card => {
        const sid     = parseInt(card.dataset.stopId);
        const patch   = {};
        const arrival = card.querySelector(`#arrival-${sid}`)?.value;
        const dep     = card.querySelector(`#departure-${sid}`)?.value;
        const trans   = card.querySelector('.stop-transport')?.value;
        const accom   = card.querySelector('.stop-accom')?.value;
        const aCost   = card.querySelector('.stop-accom-cost')?.value;
        const notes   = card.querySelector('.stop-notes')?.value;

        if (arrival) patch.start_date = arrival;
        if (dep)     patch.end_date   = dep;
        if (trans !== undefined) patch.transport_note = trans;
        if (accom !== undefined) patch.accommodation  = accom;
        if (aCost !== undefined) patch.accommodation_cost = aCost === '' ? null : parseFloat(aCost);
        if (notes !== undefined) patch.stop_notes = notes;

        if (Object.keys(patch).length) {
            promises.push(api('PUT', `${API_STOPS}?id=${sid}`, patch));
        }
    });

    try {
        await Promise.all(promises);
        saveIndicator.className = 'ib-save-indicator saved';
        saveIndicator.innerHTML = '<i class="fa-solid fa-circle-check fa-xs"></i> Saved ✓';
        setTimeout(() => {
            saveIndicator.className = 'ib-save-indicator';
            saveIndicator.innerHTML = '<i class="fa-regular fa-circle-dot"></i> All changes auto-saved';
        }, 2500);
        toast('Draft saved!', 'success');
    } catch {
        saveIndicator.className = 'ib-save-indicator error';
        saveIndicator.innerHTML = '<i class="fa-solid fa-xmark fa-xs"></i> Save failed';
        toast('Save failed', 'error');
    } finally {
        saveDraftBtn.disabled = false;
    }
}

async function saveAndContinue() {
    await saveDraft();
    window.location.href = `itinerary-view.php?trip_id=${TRIP_ID}`;
}

// ── Edit Trip Name ────────────────────────────────────────────────────────────
function initEditTripName() {
    document.getElementById('editTripNameBtn')?.addEventListener('click', async () => {
        const display = document.getElementById('tripNameDisplay');
        const current = display.textContent.trim();
        const newName = prompt('Edit trip name:', current);
        if (!newName || newName.trim() === current) return;
        try {
            const res = await api('PUT', `${API_TRIPS}?id=${TRIP_ID}`, { name: newName.trim() });
            if (res.success) {
                display.textContent = newName.trim();
                document.title = `Build Itinerary — ${newName.trim()} — GlobeTrotter`;
                toast('Trip name updated!', 'success');
            } else {
                toast(res.error || 'Failed to update', 'error');
            }
        } catch { toast('Network error', 'error'); }
    });
}

// ── Intersection Observer (sidebar active highlight) ──────────────────────────
function initIntersectionObserver() {
    if (!('IntersectionObserver' in window)) return;
    const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const sid = entry.target.dataset?.stopId;
                document.querySelectorAll('.ib-stop-nav-item').forEach(a => {
                    a.classList.toggle('active', a.dataset.stopId === sid);
                });
            }
        });
    }, { threshold: 0.35, rootMargin: '-60px 0px -40% 0px' });
    container.querySelectorAll('.ib-stop-card').forEach(c => obs.observe(c));
}

// ── Debounce util ─────────────────────────────────────────────────────────────
function debounce(fn, delay) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
