<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();

// Fetch Trips
$tripsStmt = $pdo->prepare("
    SELECT id, trip_name, start_date, end_date, status, visibility, cover_photo 
    FROM trips 
    WHERE user_id = ? 
    ORDER BY start_date ASC
");
$tripsStmt->execute([$userId]);
$trips = $tripsStmt->fetchAll();

// Fetch Activities
$actStmt = $pdo->prepare("
    SELECT ta.id, ta.trip_stop_id, ta.activity_id, ta.scheduled_date, ta.scheduled_time, 
           a.name AS activity_name, a.duration_hours, a.category, c.name AS city_name, 
           t.id AS trip_id, t.trip_name
    FROM trip_activities ta
    JOIN activities a ON a.id = ta.activity_id
    JOIN trip_stops s ON s.id = ta.trip_stop_id
    JOIN cities c ON c.id = s.city_id
    JOIN trips t ON t.id = s.trip_id
    WHERE t.user_id = ?
    ORDER BY ta.scheduled_date ASC, ta.scheduled_time ASC
");
$actStmt->execute([$userId]);
$activities = $actStmt->fetchAll();

$pageTitle = 'Trip Calendar — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<script>
    window.TRIP_DATA = <?= json_encode($trips) ?>;
    window.ACTIVITY_DATA = <?= json_encode($activities) ?>;
</script>

<style>
/* CSS Grid Calendar */
.calendar-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-weight: bold;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.calendar-header > div {
    padding: 10px 0;
    border-right: 1px solid #dee2e6;
}
.calendar-header > div:last-child {
    border-right: none;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: minmax(120px, auto);
    background: #dee2e6;
    gap: 1px;
    border: 1px solid #dee2e6;
}
.calendar-cell {
    background: #fff;
    padding: 5px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    cursor: pointer;
    transition: background-color 0.2s;
}
.calendar-cell:hover {
    background: #f8f9fa;
}
.calendar-cell.muted {
    background: #fdfdfd;
    color: #adb5bd;
}
.calendar-cell .date-badge {
    align-self: flex-end;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 4px;
}
.calendar-cell.today .date-badge {
    background: var(--bs-primary);
    color: white;
}
.event-bar {
    font-size: 0.75rem;
    padding: 2px 5px;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    position: relative;
    z-index: 10;
}
.event-bar:hover {
    filter: brightness(0.9);
}
.event-bar.start {
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
    margin-left: 2px;
}
.event-bar.end {
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
    margin-right: 2px;
}

/* Week View */
.week-grid {
    display: grid;
    grid-template-columns: 60px repeat(7, 1fr);
    border: 1px solid #dee2e6;
    background: #dee2e6;
    gap: 1px;
    overflow-y: auto;
    max-height: 600px;
}
.week-header {
    display: grid;
    grid-template-columns: 60px repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 20;
}
.week-header > div {
    text-align: center;
    padding: 10px 0;
    border-right: 1px solid #dee2e6;
    font-weight: bold;
}
.week-cell {
    background: #fff;
    min-height: 40px; /* 30 min slot */
    position: relative;
}
.week-time-label {
    background: #f8f9fa;
    text-align: right;
    padding-right: 5px;
    font-size: 0.75rem;
    color: #6c757d;
    transform: translateY(-50%);
    border-right: 1px solid #dee2e6;
}
.week-event {
    position: absolute;
    left: 2px;
    right: 2px;
    background: var(--bs-primary);
    color: white;
    border-radius: 4px;
    font-size: 0.75rem;
    padding: 2px 4px;
    overflow: hidden;
    z-index: 10;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

/* Mini Calendar */
.mini-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    gap: 2px;
}
.mini-calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-size: 0.7rem;
    font-weight: bold;
    color: #6c757d;
    margin-bottom: 5px;
}
.mini-day {
    padding: 5px 0;
    font-size: 0.8rem;
    border-radius: 50%;
    cursor: pointer;
    position: relative;
}
.mini-day:hover {
    background: #e9ecef;
}
.mini-day.today {
    background: var(--bs-primary);
    color: white;
}
.mini-day.muted {
    color: #adb5bd;
}
.mini-dot {
    width: 4px;
    height: 4px;
    background: var(--bs-danger);
    border-radius: 50%;
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
}

/* Print Styles */
@media print {
    body { background: white !important; }
    #navbar, .sidebar-col, .print-hide, .btn { display: none !important; }
    .calendar-grid { border: 1px solid #000; gap: 0; }
    .calendar-header > div, .calendar-cell { border: 1px solid #ccc !important; }
    .event-bar { 
        color: black !important; 
        background: none !important; 
        border: 1px solid #000 !important; 
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 sidebar-col">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button class="btn btn-sm btn-light" id="miniPrevBtn"><i class="fa-solid fa-chevron-left"></i></button>
                        <strong id="miniMonthYear">Jan 2024</strong>
                        <button class="btn btn-sm btn-light" id="miniNextBtn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                    <div class="mini-calendar-header">
                        <div>S</div><div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div>
                    </div>
                    <div id="miniCalendarGrid" class="mini-calendar-grid"></div>
                </div>
            </div>
            
            <button class="btn btn-outline-secondary w-100 mb-4" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print Calendar</button>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 print-hide">
                <h3 class="fw-bold mb-0"><i class="fa-solid fa-calendar-alt text-primary me-2"></i>Trip Calendar</h3>
                <div class="btn-group shadow-sm">
                    <button class="btn btn-primary" id="btnViewMonth">Month</button>
                    <button class="btn btn-outline-primary" id="btnViewWeek">Week</button>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="card border-0 shadow-sm mb-5">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <button class="btn btn-light rounded-circle print-hide" id="mainPrevBtn"><i class="fa-solid fa-chevron-left"></i></button>
                    <h4 class="fw-bold mb-0" id="mainMonthYear">January 2024</h4>
                    <button class="btn btn-light rounded-circle print-hide" id="mainNextBtn"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="card-body p-0" id="mainCalendarContainer">
                    <!-- Month View Grid -->
                    <div id="monthViewWrapper">
                        <div class="calendar-header">
                            <div>SUN</div><div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div>
                        </div>
                        <div class="calendar-grid" id="monthGrid"></div>
                    </div>

                    <!-- Week View Grid -->
                    <div id="weekViewWrapper" class="d-none">
                        <div class="week-header" id="weekHeader"></div>
                        <div class="week-grid" id="weekGrid"></div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-list text-success me-2"></i>Upcoming Events</h4>
            <div class="card border-0 shadow-sm">
                <div class="list-group list-group-flush" id="upcomingList">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Plan Trip Modal -->
<div class="modal fade" id="planTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plane me-2"></i>Plan a Trip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fs-5 mb-4">Start planning a trip on <strong id="planTripDateStr"></strong>?</p>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-primary px-4" id="btnCreateTripYes">Yes, Create Trip</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Quick View Modal -->
<div class="modal fade" id="eventViewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="evTitle">Trip Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="evDates"></p>
                <div class="mb-3">
                    <span class="badge" id="evBadge"></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-primary w-100" id="evViewLink">View Full Itinerary</a>
                    <a href="#" class="btn btn-outline-secondary w-100" id="evEditLink">Edit</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trips = window.TRIP_DATA || [];
    const activities = window.ACTIVITY_DATA || [];
    const colors = ['bg-primary', 'bg-success', 'bg-danger', 'bg-info text-dark', 'bg-warning text-dark', 'bg-secondary'];
    
    // Assign a fixed color to each trip
    trips.forEach((t, i) => { t.colorClass = colors[i % colors.length]; });

    let currentDate = new Date();
    let currentView = 'month'; // 'month' or 'week'

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

    // DOM Elements
    const monthGrid = document.getElementById('monthGrid');
    const weekGrid = document.getElementById('weekGrid');
    const weekHeader = document.getElementById('weekHeader');
    const miniGrid = document.getElementById('miniCalendarGrid');
    const upcomingList = document.getElementById('upcomingList');
    const mainMonthYear = document.getElementById('mainMonthYear');
    const miniMonthYear = document.getElementById('miniMonthYear');
    
    // Modals
    const planTripModal = new bootstrap.Modal(document.getElementById('planTripModal'));
    const eventViewModal = new bootstrap.Modal(document.getElementById('eventViewModal'));

    function renderAll() {
        if (currentView === 'month') {
            renderMonth(currentDate);
        } else {
            renderWeek(currentDate);
        }
        renderMiniCalendar(currentDate);
        renderUpcomingEvents();
    }

    function renderMonth(dateObj) {
        monthGrid.innerHTML = '';
        const year = dateObj.getFullYear();
        const month = dateObj.getMonth();
        mainMonthYear.textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // Prepare grid array
        const gridDays = [];
        // Prev month days
        for (let i = firstDay - 1; i >= 0; i--) {
            gridDays.push({ d: new Date(year, month - 1, daysInPrevMonth - i), isCurrent: false });
        }
        // Current month days
        for (let i = 1; i <= daysInMonth; i++) {
            gridDays.push({ d: new Date(year, month, i), isCurrent: true });
        }
        // Next month days to complete rows of 7
        const remainder = gridDays.length % 7;
        if (remainder !== 0) {
            for (let i = 1; i <= (7 - remainder); i++) {
                gridDays.push({ d: new Date(year, month + 1, i), isCurrent: false });
            }
        }

        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

        // Prepare events by day
        // We need to figure out the slot for each event so they align horizontally
        const dayEvents = {};
        gridDays.forEach(gd => dayEvents[toISODate(gd.d)] = []);

        // Pre-calculate which trips land on which grid days
        trips.forEach(trip => {
            const start = new Date(trip.start_date);
            const end = new Date(trip.end_date);
            start.setHours(0,0,0,0);
            end.setHours(23,59,59,999);
            
            // Find overlap
            gridDays.forEach(gd => {
                if (gd.d >= start && gd.d <= end) {
                    dayEvents[toISODate(gd.d)].push(trip);
                }
            });
        });

        gridDays.forEach(gd => {
            const iso = toISODate(gd.d);
            const cell = document.createElement('div');
            cell.className = 'calendar-cell' + (gd.isCurrent ? '' : ' muted') + (iso === todayStr ? ' today' : '');
            
            const badge = document.createElement('div');
            badge.className = 'date-badge';
            badge.textContent = gd.d.getDate();
            cell.appendChild(badge);

            const cellTrips = dayEvents[iso] || [];
            let renderedCount = 0;
            cellTrips.slice(0, 3).forEach(trip => {
                const bar = document.createElement('div');
                const isStart = iso === trip.start_date;
                const isEnd = iso === trip.end_date;
                
                bar.className = `event-bar ${trip.colorClass}`;
                if (isStart) bar.classList.add('start');
                if (isEnd) bar.classList.add('end');
                
                bar.textContent = isStart || gd.d.getDay() === 0 ? trip.trip_name : '\u00A0'; // show name on start or Sunday
                bar.dataset.tripId = trip.id;
                
                bar.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showTripModal(trip);
                });
                
                cell.appendChild(bar);
                renderedCount++;
            });

            if (cellTrips.length > 3) {
                const more = document.createElement('div');
                more.className = 'text-muted small text-center mt-auto';
                more.style.fontSize = '0.7rem';
                more.textContent = `+${cellTrips.length - 3} more`;
                cell.appendChild(more);
            }

            cell.addEventListener('click', () => {
                const dateStr = gd.d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                document.getElementById('planTripDateStr').textContent = dateStr;
                document.getElementById('btnCreateTripYes').href = `create-trip.php?start_date=${iso}`;
                planTripModal.show();
            });

            monthGrid.appendChild(cell);
        });
    }

    function renderWeek(dateObj) {
        weekHeader.innerHTML = '';
        weekGrid.innerHTML = '';
        
        // Find Sunday of current week
        const startOfWeek = new Date(dateObj);
        startOfWeek.setDate(dateObj.getDate() - dateObj.getDay());
        
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);

        const monthStr = (startOfWeek.getMonth() === endOfWeek.getMonth()) ? 
            `${monthNames[startOfWeek.getMonth()]} ${startOfWeek.getFullYear()}` :
            `${monthNames[startOfWeek.getMonth()]} / ${monthNames[endOfWeek.getMonth()]} ${startOfWeek.getFullYear()}`;
        mainMonthYear.textContent = monthStr;

        // Header Time blank
        const blank = document.createElement('div');
        weekHeader.appendChild(blank);

        const weekDays = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(startOfWeek);
            d.setDate(d.getDate() + i);
            weekDays.push(d);
            
            const hd = document.createElement('div');
            hd.innerHTML = `<span class="small text-muted d-block">${["Sun","Mon","Tue","Wed","Thu","Fri","Sat"][i]}</span>
                            <span class="fs-5">${d.getDate()}</span>`;
            weekHeader.appendChild(hd);
        }

        // Filter activities for this week
        const startIso = toISODate(startOfWeek);
        const endIso = toISODate(endOfWeek);
        const weekActs = activities.filter(a => a.scheduled_date >= startIso && a.scheduled_date <= endIso);

        // Generate grid rows (8:00 AM to 10:00 PM in 30min slots = 28 slots)
        for (let h = 8; h <= 22; h++) {
            for (let m of [0, 30]) {
                if (h === 22 && m === 30) continue; // stop at 10:00 PM

                // Time label
                const timeLabel = document.createElement('div');
                timeLabel.className = 'week-cell week-time-label';
                timeLabel.textContent = (m === 0) ? `${h%12||12} ${h>=12?'PM':'AM'}` : '';
                weekGrid.appendChild(timeLabel);

                // 7 day cells
                for (let d = 0; d < 7; d++) {
                    const cell = document.createElement('div');
                    cell.className = 'week-cell';
                    
                    // Place activities if they start exactly at this slot
                    const currDayIso = toISODate(weekDays[d]);
                    const timeStr = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:00`;
                    
                    weekActs.forEach(act => {
                        if (act.scheduled_date === currDayIso && act.scheduled_time === timeStr) {
                            const evt = document.createElement('div');
                            evt.className = 'week-event';
                            // height = duration_hours * 2 * 40px (each slot is 40px)
                            const dur = parseFloat(act.duration_hours) || 1;
                            evt.style.height = `${dur * 2 * 40 - 2}px`; 
                            evt.innerHTML = `<strong>${act.activity_name}</strong><br><small>${act.city_name}</small>`;
                            
                            // Color by parent trip color
                            const trip = trips.find(t => t.id === act.trip_id);
                            if (trip) evt.className = `week-event ${trip.colorClass}`;

                            evt.addEventListener('click', (e) => {
                                e.stopPropagation();
                                if(trip) showTripModal(trip);
                            });

                            cell.appendChild(evt);
                        }
                    });

                    weekGrid.appendChild(cell);
                }
            }
        }
    }

    function renderMiniCalendar(dateObj) {
        miniGrid.innerHTML = '';
        const year = dateObj.getFullYear();
        const month = dateObj.getMonth();
        miniMonthYear.textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        const todayStr = toISODate(new Date());

        // Fast set of dates that have trips
        const tripDates = new Set();
        trips.forEach(t => {
            let d = new Date(t.start_date);
            const end = new Date(t.end_date);
            while (d <= end) {
                tripDates.add(toISODate(d));
                d.setDate(d.getDate() + 1);
            }
        });

        const appendDay = (d, isCurrent) => {
            const iso = toISODate(d);
            const cell = document.createElement('div');
            cell.className = 'mini-day' + (isCurrent ? '' : ' muted') + (iso === todayStr ? ' today' : '');
            cell.textContent = d.getDate();
            
            if (tripDates.has(iso)) {
                const dot = document.createElement('div');
                dot.className = 'mini-dot';
                cell.appendChild(dot);
            }

            cell.addEventListener('click', () => {
                currentDate = new Date(d);
                renderAll();
            });

            miniGrid.appendChild(cell);
        };

        for (let i = firstDay - 1; i >= 0; i--) appendDay(new Date(year, month - 1, daysInPrevMonth - i), false);
        for (let i = 1; i <= daysInMonth; i++) appendDay(new Date(year, month, i), true);
        const remainder = (firstDay + daysInMonth) % 7;
        if (remainder !== 0) {
            for (let i = 1; i <= (7 - remainder); i++) appendDay(new Date(year, month + 1, i), false);
        }
    }

    function renderUpcomingEvents() {
        upcomingList.innerHTML = '';
        const todayStr = toISODate(new Date());
        
        let allEvents = [];
        trips.forEach(t => {
            if (t.start_date >= todayStr || (t.start_date <= todayStr && t.end_date >= todayStr)) {
                allEvents.push({ type: 'trip', date: Math.max(new Date(t.start_date), new Date(todayStr).getTime()), originalObj: t });
            }
        });
        activities.forEach(a => {
            if (a.scheduled_date >= todayStr) {
                allEvents.push({ type: 'activity', date: new Date(a.scheduled_date + 'T' + (a.scheduled_time||'00:00:00')).getTime(), originalObj: a });
            }
        });

        allEvents.sort((a,b) => a.date - b.date);
        
        if (allEvents.length === 0) {
            upcomingList.innerHTML = '<div class="list-group-item text-muted text-center py-4">No upcoming events found.</div>';
            return;
        }

        allEvents.slice(0, 10).forEach(ev => {
            const item = document.createElement('a');
            item.href = ev.type === 'trip' ? `itinerary-view.php?trip_id=${ev.originalObj.id}` : `itinerary-view.php?trip_id=${ev.originalObj.trip_id}`;
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3';
            
            const dateObj = new Date(ev.date);
            const dateStr = dateObj.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            
            if (ev.type === 'trip') {
                const t = ev.originalObj;
                item.innerHTML = `
                    <div>
                        <span class="badge bg-primary me-2 w-px-50 text-center">${dateStr}</span>
                        <strong class="text-dark">${t.trip_name}</strong>
                    </div>
                    <span class="text-muted small">Trip</span>
                `;
            } else {
                const a = ev.originalObj;
                item.innerHTML = `
                    <div>
                        <span class="badge bg-secondary me-2 w-px-50 text-center">${dateStr}</span>
                        <span class="text-dark">${a.activity_name}</span>
                        <small class="text-muted ms-2"><i class="fa-solid fa-location-dot me-1"></i>${a.city_name}</small>
                    </div>
                    <span class="text-muted small">${(a.duration_hours||1)}h Activity</span>
                `;
            }
            upcomingList.appendChild(item);
        });
    }

    function showTripModal(trip) {
        document.getElementById('evTitle').textContent = trip.trip_name;
        document.getElementById('evDates').textContent = `${trip.start_date} to ${trip.end_date}`;
        
        const badge = document.getElementById('evBadge');
        badge.className = 'badge';
        if (trip.status === 'upcoming') { badge.classList.add('bg-warning', 'text-dark'); badge.textContent = 'Upcoming'; }
        else if (trip.status === 'ongoing') { badge.classList.add('bg-success'); badge.textContent = 'Ongoing'; }
        else { badge.classList.add('bg-secondary'); badge.textContent = 'Completed'; }

        document.getElementById('evViewLink').href = `itinerary-view.php?trip_id=${trip.id}`;
        document.getElementById('evEditLink').href = `itinerary-builder.php?trip_id=${trip.id}`;
        
        eventViewModal.show();
    }

    function toISODate(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    // Event Listeners
    document.getElementById('mainPrevBtn').addEventListener('click', () => {
        if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() - 1);
        else currentDate.setDate(currentDate.getDate() - 7);
        renderAll();
    });
    document.getElementById('mainNextBtn').addEventListener('click', () => {
        if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() + 1);
        else currentDate.setDate(currentDate.getDate() + 7);
        renderAll();
    });

    document.getElementById('miniPrevBtn').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderAll();
    });
    document.getElementById('miniNextBtn').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderAll();
    });

    const btnMonth = document.getElementById('btnViewMonth');
    const btnWeek = document.getElementById('btnViewWeek');
    const wMonth = document.getElementById('monthViewWrapper');
    const wWeek = document.getElementById('weekViewWrapper');

    btnMonth.addEventListener('click', () => {
        currentView = 'month';
        btnMonth.className = 'btn btn-primary';
        btnWeek.className = 'btn btn-outline-primary';
        wMonth.classList.remove('d-none');
        wWeek.classList.add('d-none');
        renderAll();
    });

    btnWeek.addEventListener('click', () => {
        currentView = 'week';
        btnWeek.className = 'btn btn-primary';
        btnMonth.className = 'btn btn-outline-primary';
        wWeek.classList.remove('d-none');
        wMonth.classList.add('d-none');
        renderAll();
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (planTripModal._isShown || eventViewModal._isShown) return; // don't navigate if modal is open
        if (e.key === 'ArrowLeft') document.getElementById('mainPrevBtn').click();
        else if (e.key === 'ArrowRight') document.getElementById('mainNextBtn').click();
    });

    // Initial render
    renderAll();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
