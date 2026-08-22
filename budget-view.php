<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if (!$tripId) {
    header('Location: my-trips.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT t.id, t.trip_name AS name, t.start_date, t.end_date
    FROM trips t
    WHERE t.id = ? AND t.user_id = ?
');
$stmt->execute([$tripId, $userId]);
$trip = $stmt->fetch();
if (!$trip) {
    header('Location: my-trips.php');
    exit;
}

// Fetch budget items
$itemsStmt = $pdo->prepare('
    SELECT id, category, description, amount, spent_on, created_at
    FROM budget_items
    WHERE trip_id = ?
    ORDER BY spent_on DESC, created_at DESC
');
$itemsStmt->execute([$tripId]);
$budgetItems = $itemsStmt->fetchAll();

// Total activities cost
$actCostStmt = $pdo->prepare('
    SELECT COALESCE(SUM(COALESCE(sa.custom_cost, a.cost)), 0) AS total
    FROM trip_activities sa
    JOIN activities a ON a.id = sa.activity_id
    JOIN trip_stops s ON s.id = sa.trip_stop_id
    WHERE s.trip_id = ?
');
$actCostStmt->execute([$tripId]);
$activitiesTotal = (float)$actCostStmt->fetchColumn();

$manualTotal = 0;
foreach ($budgetItems as $item) {
    $manualTotal += (float)$item['amount'];
}
$grandTotal = $manualTotal + $activitiesTotal;

$daysCount = max(1, (strtotime($trip['end_date']) - strtotime($trip['start_date'])) / 86400 + 1);
$avgPerDay = round($grandTotal / $daysCount, 2);

$extraHead = '<script>const TRIP_ID = ' . $trip['id'] . ';</script><script src="' . SITE_URL . '/assets/js/budget.js" defer></script>';
$pageTitle = 'Budget Breakdown — ' . htmlspecialchars($trip['name']) . ' — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <h1 class="h2 fw-bold mb-0"><i class="fa-solid fa-wallet text-success me-2"></i>Trip Budget & Financial Highlights</h1>
            <p class="text-muted mb-0 mt-1"><?= htmlspecialchars($trip['name']) ?> (<?= date('M j', strtotime($trip['start_date'])) ?> – <?= date('M j, Y', strtotime($trip['end_date'])) ?>)</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fa-solid fa-plus me-1"></i> Add Expense
            </button>
            <a href="itinerary-view.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Itinerary
            </a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-white-50 text-uppercase fw-bold">Total Expenses</span>
                        <h2 class="fw-bold mb-0 mt-1">$<?= number_format($grandTotal, 2) ?></h2>
                    </div>
                    <i class="fa-solid fa-money-bill-wave fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info text-dark p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-dark-50 text-uppercase fw-bold">Average / Day</span>
                        <h2 class="fw-bold mb-0 mt-1">$<?= number_format($avgPerDay, 2) ?></h2>
                    </div>
                    <i class="fa-solid fa-chart-line fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-white-50 text-uppercase fw-bold">Scheduled Activities</span>
                        <h2 class="fw-bold mb-0 mt-1">$<?= number_format($activitiesTotal, 2) ?></h2>
                    </div>
                    <i class="fa-solid fa-ticket fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Donut Chart -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-bold">
                    <i class="fa-solid fa-chart-pie me-2 text-primary"></i>Category Breakdown
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="budgetChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list me-2 text-primary"></i>Logged Expenses</span>
                    <span class="badge bg-light text-dark border"><?= count($budgetItems) ?> items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($budgetItems)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No custom expenses logged yet. Click "Add Expense" to start tracking.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($budgetItems as $item): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary border"><?= ucfirst($item['category']) ?></span></td>
                                            <td><?= htmlspecialchars($item['description'] ?: '-') ?></td>
                                            <td class="small text-muted"><?= $item['spent_on'] ? date('M j, Y', strtotime($item['spent_on'])) : '-' ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($item['amount'], 2) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger delete-expense-btn" data-id="<?= $item['id'] ?>">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Expense -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus me-1 text-success"></i>Log New Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addExpenseForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expCategory" class="form-label fw-semibold small">Category *</label>
                        <select class="form-select" id="expCategory" required>
                            <option value="transport">Transport (Flights, Trains, Cabs)</option>
                            <option value="stay">Stay & Hotel Accommodation</option>
                            <option value="meals">Meals & Dining</option>
                            <option value="other">Misc & Shopping</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="expAmount" class="form-label fw-semibold small">Amount ($) *</label>
                        <input type="number" class="form-control" id="expAmount" min="0.01" step="0.01" placeholder="e.g. 150.00" required>
                    </div>
                    <div class="mb-3">
                        <label for="expDescription" class="form-label fw-semibold small">Description</label>
                        <input type="text" class="form-control" id="expDescription" placeholder="e.g. Dinner at bistro">
                    </div>
                    <div class="mb-3">
                        <label for="expSpentOn" class="form-label fw-semibold small">Date</label>
                        <input type="date" class="form-control" id="expSpentOn" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-info btn-sm me-auto" id="btnAutoFillExpense"><i class="fa-solid fa-magic me-1"></i> Auto-Fill Expense</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check me-1"></i>Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
