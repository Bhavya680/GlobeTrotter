<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['trip_id'])) {
    die("Trip ID is required.");
}

$tripId = (int)$_GET['trip_id'];
$userId = $_SESSION['user_id'] ?? null;

// Fetch trip details
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
$stmt->execute([$tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Trip not found.");
}

$isOwner = ($userId === $trip['user_id']);
$isPublic = ($trip['visibility'] === 'public');

if (!$isOwner && !$isPublic) {
    if (!$userId) {
        header('Location: login.php');
        exit;
    }
    die("You do not have permission to view this trip.");
}

// Fetch stops and their associated activities, order by date and time
$stmt = $pdo->prepare("
    SELECT s.id as stop_id, s.city_id, s.arrival_date, s.departure_date, c.name as city_name, 
           a.id as activity_record_id, a.scheduled_date, a.scheduled_time, a.custom_cost,
           act.name as activity_name, act.category, act.cost as default_cost
    FROM trip_stops s
    JOIN cities c ON s.city_id = c.id
    LEFT JOIN trip_activities a ON s.id = a.trip_stop_id
    LEFT JOIN activities act ON a.activity_id = act.id
    WHERE s.trip_id = ?
    ORDER BY s.arrival_date ASC, a.scheduled_date ASC, a.scheduled_time ASC
");
$stmt->execute([$tripId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days = [];
$totalActualSpent = 0;
$tripStartDate = $trip['start_date'];
$tripEndDate = $trip['end_date'];

$datetime1 = new DateTime($tripStartDate);
$datetime2 = new DateTime($tripEndDate);
$durationDays = $datetime1->diff($datetime2)->days + 1;

foreach ($rows as $row) {
    if ($row['activity_record_id']) {
        $date = $row['scheduled_date'] ?: $row['arrival_date'];
        $cost = $row['custom_cost'] !== null ? (float)$row['custom_cost'] : (float)$row['default_cost'];
        
        if (!isset($days[$date])) {
            $days[$date] = [
                'date' => $date,
                'city' => $row['city_name'],
                'activities' => [],
                'subtotal' => 0
            ];
        }
        
        $days[$date]['activities'][] = [
            'id' => $row['activity_record_id'],
            'name' => $row['activity_name'],
            'category' => $row['category'],
            'time' => $row['scheduled_time'],
            'cost' => $cost
        ];
        $days[$date]['subtotal'] += $cost;
        $totalActualSpent += $cost;
    }
}

ksort($days);

// Fetch accommodation cost from budget_for_stop
$stmt = $pdo->prepare("SELECT SUM(budget_for_stop) FROM trip_stops WHERE trip_id = ?");
$stmt->execute([$tripId]);
$totalAccommodation = (float)$stmt->fetchColumn();
$totalActualSpent += $totalAccommodation;

// Fetch budget
$stmt = $pdo->prepare("SELECT * FROM trip_budget WHERE trip_id = ?");
$stmt->execute([$tripId]);
$budget = $stmt->fetch(PDO::FETCH_ASSOC);
$totalBudget = $budget ? (float)$budget['total_budget'] : 0;

$coverPhoto = $trip['cover_photo'] ? "uploads/covers/" . htmlspecialchars($trip['cover_photo']) : "https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&h=400&fit=crop";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($trip['trip_name']) ?> - Itinerary | GlobeTrotter</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS (CDN for UI) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- React & Recharts -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/recharts/umd/Recharts.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="assets/css/itinerary-view.css">
</head>
<body class="bg-slate-50 font-[Inter] text-slate-800">

<!-- TOP BAR -->
<div class="iv-topbar sticky top-0 bg-white shadow-sm z-50 px-6 py-3 flex justify-between items-center print:hidden">
    <div class="flex items-center gap-4">
        <a href="dashboard.php" class="text-slate-500 hover:text-blue-600 transition">
            <i class="fa-solid fa-arrow-left text-lg"></i>
        </a>
        <div class="iv-search-wrap">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchInput" class="iv-search-input" placeholder="Search itinerary...">
        </div>
    </div>
    
    <div class="iv-topbar-actions">
        <?php if ($isOwner): ?>
            <a href="itinerary-builder.php?trip_id=<?= $tripId ?>" class="bg-slate-100 text-slate-700 hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <i class="fa-solid fa-edit"></i> Edit Itinerary
            </a>
        <?php endif; ?>
        
        <?php if ($isPublic): ?>
            <button onclick="copyShareLink()" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <i class="fa-solid fa-share-nodes"></i> Share
            </button>
        <?php endif; ?>
        
        <button onclick="window.print()" class="bg-slate-800 text-white hover:bg-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Print
        </button>
        <button onclick="alert('Coming soon!')" class="bg-slate-100 text-slate-700 hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <i class="fa-solid fa-file-pdf"></i> Download PDF
        </button>
    </div>
</div>

<!-- TRIP HEADER -->
<div class="iv-header" style="background-image: url('<?= $coverPhoto ?>');">
    <div class="iv-header-content text-white pt-20 pb-8">
        <h1 class="text-4xl md:text-5xl font-[Outfit] font-extrabold mb-3 drop-shadow-md">
            <?= htmlspecialchars($trip['trip_name']) ?>
        </h1>
        <div class="iv-header-meta flex items-center gap-6">
            <span class="flex items-center gap-2">
                <i class="fa-regular fa-calendar text-blue-300"></i>
                <?= date('M j, Y', strtotime($tripStartDate)) ?> - <?= date('M j, Y', strtotime($tripEndDate)) ?>
            </span>
            <span class="flex items-center gap-2">
                <i class="fa-solid fa-clock text-blue-300"></i>
                <?= $durationDays ?> Days
            </span>
            <span class="iv-badge border border-white/30 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase">
                <?= htmlspecialchars($trip['status']) ?>
            </span>
        </div>
    </div>
</div>

<div class="iv-container">
    <!-- LEFT COLUMN: Itinerary -->
    <div class="iv-main-content">
        
        <?php if (empty($days)): ?>
            <div class="bg-white p-8 rounded-2xl border border-dashed border-slate-300 text-center text-slate-500">
                <i class="fa-solid fa-map-location-dot text-4xl mb-4 text-slate-300"></i>
                <p class="text-lg">No activities scheduled yet.</p>
            </div>
        <?php else: ?>
            <?php 
            $dayIndex = 1;
            foreach ($days as $date => $dayData): 
            ?>
            <div class="iv-day mb-10 itinerary-item">
                <div class="iv-day-header flex items-baseline gap-4 border-b-2 border-blue-500 pb-2 mb-4">
                    <h2 class="text-2xl font-bold text-slate-800">Day <?= $dayIndex++ ?></h2>
                    <span class="text-slate-500 font-medium"><?= date('l, M j', strtotime($date)) ?> &bull; <?= htmlspecialchars($dayData['city']) ?></span>
                </div>
                
                <table class="iv-activity-table w-full border-separate" style="border-spacing: 0 0.5rem;">
                    <thead>
                        <tr>
                            <th class="text-left text-xs uppercase tracking-wider text-slate-400 px-4 py-2">Activity</th>
                            <th class="text-right text-xs uppercase tracking-wider text-slate-400 px-4 py-2 w-32">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dayData['activities'] as $act): ?>
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow rounded-xl">
                            <td class="p-4 rounded-l-xl border-y border-l border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex justify-center items-center flex-shrink-0">
                                        <i class="fa-solid fa-<?= $act['category'] == 'food' ? 'utensils' : ($act['category'] == 'sightseeing' ? 'camera' : 'map-pin') ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 searchable-text"><?= htmlspecialchars($act['name']) ?></p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            <?= $act['time'] ? date('h:i A', strtotime($act['time'])) : 'Any time' ?> &bull; <span class="capitalize"><?= $act['category'] ?></span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 rounded-r-xl border-y border-r border-slate-100 text-right">
                                <span class="font-bold text-emerald-600">$<?= number_format($act['cost'], 2) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Subtotal Row -->
                        <tr>
                            <td class="text-right py-3 px-4 text-sm font-medium text-slate-500 uppercase tracking-wide">
                                Subtotal for Day <?= $dayIndex - 1 ?>
                            </td>
                            <td class="text-right py-3 px-4 font-bold text-slate-800">
                                $<?= number_format($dayData['subtotal'], 2) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Budget breakdown full section -->
        <div class="mt-12 bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-slate-200 print:hidden" id="budgetSection">
            <h3 class="text-xl font-bold mb-6 text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-blue-500"></i> Budget Breakdown
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <?php if ($isOwner): ?>
                <!-- Form -->
                <div>
                    <form id="budgetForm" class="space-y-4">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-car text-slate-400 w-5"></i>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Transport</label>
                                <input type="number" name="transport" class="w-full border-b-2 border-slate-200 focus:border-blue-500 py-1 outline-none font-medium" value="<?= $budget ? $budget['transport_budget'] : '' ?>" step="0.01">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-hotel text-slate-400 w-5"></i>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Accommodation</label>
                                <input type="number" name="stay" class="w-full border-b-2 border-slate-200 focus:border-blue-500 py-1 outline-none font-medium" value="<?= $budget ? $budget['stay_budget'] : '' ?>" step="0.01">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-ticket text-slate-400 w-5"></i>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Activities</label>
                                <input type="number" name="activities" class="w-full border-b-2 border-slate-200 focus:border-blue-500 py-1 outline-none font-medium" value="<?= $budget ? $budget['activities_budget'] : '' ?>" step="0.01">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-utensils text-slate-400 w-5"></i>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Meals</label>
                                <input type="number" name="meals" class="w-full border-b-2 border-slate-200 focus:border-blue-500 py-1 outline-none font-medium" value="<?= $budget ? $budget['meals_budget'] : '' ?>" step="0.01">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-wallet text-slate-400 w-5"></i>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Miscellaneous</label>
                                <input type="number" name="misc" class="w-full border-b-2 border-slate-200 focus:border-blue-500 py-1 outline-none font-medium" value="<?= $budget ? $budget['misc_budget'] : '' ?>" step="0.01">
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                            <div class="text-sm font-semibold text-slate-500">Total: <span id="budgetTotalPreview" class="text-lg text-slate-800">$0.00</span></div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold text-sm transition shadow-sm hover:shadow-md">
                                Save Budget
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="<?= !$isOwner ? 'col-span-2 flex flex-col md:flex-row gap-8' : '' ?>">
                    <!-- React Donut Chart -->
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 text-center">Budget Allocation</h4>
                        <div id="budget-donut-root"></div>
                    </div>
                    
                    <!-- Chart.js Bar Chart -->
                    <div class="flex-1 mt-8 lg:mt-0">
                        <h4 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 text-center">Budget vs Actual</h4>
                        <canvas id="barChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Alerts Container -->
            <div id="alertsContainer" class="mt-6 space-y-2"></div>
            
        </div>
        
    </div>
    
    <!-- RIGHT COLUMN: Sidebar (Running Total) -->
    <div class="iv-sidebar print:hidden">
        <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-6 sticky top-24">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Trip Overview</h3>
            
            <div class="mb-6">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Spent</div>
                <div class="text-3xl font-extrabold text-emerald-600">$<span id="sidebarActual">0.00</span></div>
            </div>
            
            <div class="mb-6">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Budget</div>
                <div class="text-xl font-bold text-slate-700">$<span id="sidebarBudget">0.00</span></div>
            </div>
            
            <div class="mb-8">
                <div class="flex justify-between text-sm mb-2 font-medium">
                    <span class="text-slate-600">Budget Used</span>
                    <span id="sidebarPercent" class="text-slate-800">0%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div id="sidebarProgress" class="bg-blue-500 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Cost / Day</div>
                    <div class="text-lg font-bold text-slate-800">$<span id="avgCostPerDay">0.00</span></div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
    const tripId = <?= $tripId ?>;
    const durationDays = <?= $durationDays ?>;
    
    // Copy link
    function copyShareLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Public link copied to clipboard!');
        });
    }

    // Search filter
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.itinerary-item').forEach(day => {
            let match = false;
            day.querySelectorAll('.searchable-text').forEach(textEl => {
                if(textEl.textContent.toLowerCase().includes(term)) match = true;
            });
            day.style.display = (match || term === '') ? 'block' : 'none';
        });
    });

    // Form logic
    const budgetForm = document.getElementById('budgetForm');
    if (budgetForm) {
        const inputs = budgetForm.querySelectorAll('input[type="number"]');
        const preview = document.getElementById('budgetTotalPreview');
        
        const updateTotal = () => {
            let total = 0;
            inputs.forEach(inp => total += parseFloat(inp.value || 0));
            preview.textContent = '$' + total.toFixed(2);
        };
        
        inputs.forEach(inp => inp.addEventListener('input', updateTotal));
        updateTotal();

        budgetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = { trip_id: tripId };
            inputs.forEach(inp => payload[inp.name] = parseFloat(inp.value || 0));
            
            try {
                const res = await fetch('api/budget.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    fetchBudgetData();
                    alert('Budget saved successfully!');
                } else {
                    alert(data.error || 'Failed to save budget');
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    let barChartInstance = null;

    async function fetchBudgetData() {
        try {
            const res = await fetch(`api/budget.php?trip_id=${tripId}`);
            const data = await res.json();
            
            if (data.error) {
                console.error(data.error);
                return;
            }

            const { budget, actuals } = data;
            
            // Set global data for React
            window.BUDGET_DATA = data;
            // Dispatch event to force React to re-render if it was already mounted
            window.dispatchEvent(new Event('budgetDataLoaded'));

            // Update sidebar
            const totalActual = actuals.transport + actuals.stay + actuals.activities + actuals.meals + actuals.misc;
            const totalBudget = budget.total;
            
            document.getElementById('sidebarActual').textContent = totalActual.toFixed(2);
            document.getElementById('sidebarBudget').textContent = totalBudget.toFixed(2);
            document.getElementById('avgCostPerDay').textContent = (totalActual / durationDays).toFixed(2);
            
            let percent = totalBudget > 0 ? (totalActual / totalBudget) * 100 : (totalActual > 0 ? 100 : 0);
            const pBar = document.getElementById('sidebarProgress');
            document.getElementById('sidebarPercent').textContent = percent.toFixed(1) + '%';
            
            pBar.style.width = Math.min(percent, 100) + '%';
            if (percent < 75) pBar.className = 'bg-emerald-500 h-2.5 rounded-full transition-all duration-500';
            else if (percent < 100) pBar.className = 'bg-amber-500 h-2.5 rounded-full transition-all duration-500';
            else pBar.className = 'bg-rose-500 h-2.5 rounded-full transition-all duration-500';

            // Chart.js Bar Chart
            renderBarChart(budget, actuals);
            
            // Alerts
            renderAlerts(budget, actuals);
            
        } catch (e) {
            console.error(e);
        }
    }

    function renderBarChart(budget, actuals) {
        const ctx = document.getElementById('barChart').getContext('2d');
        
        const labels = ['Transport', 'Stay', 'Activities', 'Meals', 'Misc'];
        const bData = [budget.transport, budget.stay, budget.activities, budget.meals, budget.misc];
        const aData = [actuals.transport, actuals.stay, actuals.activities, actuals.meals, actuals.misc];
        
        const actualColors = aData.map((val, i) => val > bData[i] && bData[i] > 0 ? '#ef4444' : '#f97316'); // red if over, orange normally

        if (barChartInstance) barChartInstance.destroy();

        barChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Budgeted',
                        data: bData,
                        backgroundColor: '#3b82f6', // blue
                        borderRadius: 4
                    },
                    {
                        label: 'Actual Spent',
                        data: aData,
                        backgroundColor: actualColors,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [4, 4] } }
                }
            }
        });
    }

    function renderAlerts(budget, actuals) {
        const container = document.getElementById('alertsContainer');
        container.innerHTML = '';
        
        const categories = ['transport', 'stay', 'activities', 'meals', 'misc'];
        categories.forEach(cat => {
            if (budget[cat] > 0 && actuals[cat] > budget[cat]) {
                const div = document.createElement('div');
                div.className = 'bg-rose-50 text-rose-700 border border-rose-200 px-4 py-3 rounded-lg text-sm flex items-start gap-3';
                div.innerHTML = `<i class="fa-solid fa-triangle-exclamation mt-0.5"></i> 
                                 <div><strong>Over Budget!</strong> You are over budget on <span class="capitalize font-semibold">${cat}</span>. Consider adjusting your plan.</div>`;
                container.appendChild(div);
            }
        });
    }

    // Init
    fetchBudgetData();
</script>

<!-- Load React Component -->
<script type="text/babel" src="assets/js/budget-chart.js"></script>

</body>
</html>
