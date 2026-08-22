/**
 * GlobeTrotter — Admin Dashboard JavaScript Controller
 */

document.addEventListener('DOMContentLoaded', function () {
    initAdminTabs();
    initMetricCountUp();
    initUserManagement();
    initCharts();
    initMobileSidebar();
});

// ── Tab Management ─────────────────────────────────────────────────────────────
function initAdminTabs() {
    const navLinks = document.querySelectorAll('.admin-nav-link[data-tab]');
    const tabPanes = document.querySelectorAll('.admin-tab-pane');

    function switchTab(tabId) {
        navLinks.forEach(link => {
            if (link.dataset.tab === tabId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        tabPanes.forEach(pane => {
            if (pane.id === 'tab-' + tabId) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });

        // Store hash
        if (history.replaceState) {
            history.replaceState(null, null, '#' + tabId);
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const tabId = this.dataset.tab;
            switchTab(tabId);

            // Close mobile sidebar if open
            closeMobileSidebar();
        });
    });

    // Check URL hash on load
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('tab-' + hash)) {
        switchTab(hash);
    }
}

// ── Metric Numbers Count-Up Animation ──────────────────────────────────────────
function initMetricCountUp() {
    document.querySelectorAll('.count-up').forEach(el => {
        const target = parseInt(el.dataset.target || el.textContent, 10);
        if (isNaN(target)) return;

        let current = 0;
        const duration = 1000; // ms
        const steps = 30;
        const increment = Math.ceil(target / steps);
        const interval = duration / steps;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                el.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                el.textContent = current.toLocaleString();
            }
        }, interval);
    });
}

// ── Tab 1: User Management (Search, Sort, Pagination, Modals, AJAX) ─────────────
let usersData = [];
let currentPage = 1;
const pageSize = 20;
let sortColumn = 'id';
let sortDirection = 'desc';

function initUserManagement() {
    const tableBody = document.getElementById('usersTableBody');
    const searchInput = document.getElementById('userSearchInput');
    const roleFilter = document.getElementById('userRoleFilter');
    const paginationContainer = document.getElementById('usersPagination');
    const totalCounter = document.getElementById('totalUsersCount');

    // Read initial data embedded in window.USERS_LIST
    if (window.USERS_LIST && Array.isArray(window.USERS_LIST)) {
        usersData = [...window.USERS_LIST];
    }

    function renderUsers() {
        if (!tableBody) return;

        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const role = roleFilter ? roleFilter.value : '';

        // Filter
        let filtered = usersData.filter(u => {
            const fullName = ((u.first_name || '') + ' ' + (u.last_name || '')).toLowerCase();
            const email = (u.email || '').toLowerCase();
            const location = ((u.city || '') + ' ' + (u.country || '')).toLowerCase();

            const matchesQuery = !query || fullName.includes(query) || email.includes(query) || location.includes(query);
            const matchesRole = !role || u.role === role;

            return matchesQuery && matchesRole;
        });

        // Update Total Count
        if (totalCounter) {
            totalCounter.textContent = filtered.length;
        }

        // Sort
        filtered.sort((a, b) => {
            let valA = a[sortColumn];
            let valB = b[sortColumn];

            if (sortColumn === 'name') {
                valA = ((a.first_name || '') + ' ' + (a.last_name || '')).toLowerCase();
                valB = ((b.first_name || '') + ' ' + (b.last_name || '')).toLowerCase();
            } else if (sortColumn === 'location') {
                valA = ((a.city || '') + ' ' + (a.country || '')).toLowerCase();
                valB = ((b.city || '') + ' ' + (b.country || '')).toLowerCase();
            } else if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = (valB || '').toLowerCase();
            }

            if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // Paginate
        const totalPages = Math.ceil(filtered.length / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * pageSize;
        const pageItems = filtered.slice(startIndex, startIndex + pageSize);

        if (pageItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-user-slash fa-2x mb-3 opacity-40"></i>
                        <p class="mb-0 fw-medium">No users match your criteria.</p>
                    </td>
                </tr>
            `;
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        let html = '';
        pageItems.forEach((u, idx) => {
            const fullName = escapeHtml((u.first_name || '') + ' ' + (u.last_name || ''));
            const initials = ((u.first_name ? u.first_name[0] : '') + (u.last_name ? u.last_name[0] : '')).toUpperCase() || 'U';
            const joinedDate = u.created_at ? new Date(u.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
            const location = [u.city, u.country].filter(Boolean).join(', ') || '<span class="text-muted fst-italic">Not specified</span>';
            const isCurrentAdmin = window.CURRENT_ADMIN_ID && u.id == window.CURRENT_ADMIN_ID;

            html += `
                <tr id="user-row-${u.id}">
                    <td class="fw-bold text-muted small">#${u.id}</td>
                    <td>
                        <div class="user-avatar-sm">
                            ${u.profile_photo ? `<img src="${escapeHtml(u.profile_photo)}" alt="${fullName}" class="w-100 h-100 rounded-circle object-fit-cover">` : initials}
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${fullName}</div>
                        ${isCurrentAdmin ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle small">You</span>' : ''}
                    </td>
                    <td class="text-secondary">${escapeHtml(u.email)}</td>
                    <td>${location}</td>
                    <td class="small text-muted">${joinedDate}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold">${u.trips_count || 0}</span>
                    </td>
                    <td>
                        <span class="role-badge ${u.role === 'admin' ? 'role-badge-admin' : 'role-badge-user'}" id="user-role-badge-${u.id}">
                            <i class="fa-solid fa-${u.role === 'admin' ? 'shield-halved' : 'user'}"></i>
                            ${u.role === 'admin' ? 'Admin' : 'User'}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1.5">
                            <button class="btn btn-sm btn-outline-primary px-2.5 py-1 view-user-btn" data-id="${u.id}" title="View Details">
                                <i class="fa-solid fa-eye"></i> View
                            </button>
                            ${!isCurrentAdmin ? `
                                <button class="btn btn-sm ${u.role === 'admin' ? 'btn-outline-warning' : 'btn-outline-success'} px-2.5 py-1 toggle-role-btn" 
                                        data-id="${u.id}" data-role="${u.role}" title="${u.role === 'admin' ? 'Demote to User' : 'Promote to Admin'}">
                                    <i class="fa-solid fa-${u.role === 'admin' ? 'user-minus' : 'user-shield'}"></i>
                                    ${u.role === 'admin' ? 'Remove Admin' : 'Make Admin'}
                                </button>
                                <button class="btn btn-sm btn-outline-danger px-2.5 py-1 delete-user-btn" data-id="${u.id}" data-name="${fullName}" title="Delete User">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        renderPagination(totalPages);
        attachUserActionListeners();
    }

    function renderPagination(totalPages) {
        if (!paginationContainer || totalPages <= 1) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        if (typeof createWatermelonPagination === 'function') {
            createWatermelonPagination(paginationContainer, {
                currentPage: currentPage,
                totalPages: totalPages,
                totalItems: filtered.length,
                onPageChange: (page) => {
                    currentPage = page;
                    renderUsers();
                }
            });
        }
    }

    // Search and Filter Listeners
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            renderUsers();
        });
    }

    if (roleFilter) {
        roleFilter.addEventListener('change', () => {
            currentPage = 1;
            renderUsers();
        });
    }

    // Sorting Table Headers
    document.querySelectorAll('.admin-table th.sortable').forEach(th => {
        th.addEventListener('click', function () {
            const col = this.dataset.sort;
            if (sortColumn === col) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = col;
                sortDirection = 'asc';
            }

            // Update UI icons
            document.querySelectorAll('.admin-table th.sortable').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
                const icon = h.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-sort';
            });

            this.classList.add(sortDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
            const icon = this.querySelector('i');
            if (icon) icon.className = `fa-solid fa-sort-${sortDirection === 'asc' ? 'up' : 'down'}`;

            renderUsers();
        });
    });

    renderUsers();
}

// ── User Action Listeners (View Modal, Toggle Role, Delete Modal) ──────────────
function attachUserActionListeners() {
    // 1. View User Details Modal
    document.querySelectorAll('.view-user-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const userId = this.dataset.id;
            const modalEl = document.getElementById('viewUserModal');
            if (!modalEl) return;

            const modal = new bootstrap.Modal(modalEl);
            const contentEl = document.getElementById('viewUserModalContent');
            contentEl.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="mb-0">Loading user profile and history...</p>
                </div>
            `;
            modal.show();

            try {
                const res = await fetch(`../api/admin.php?action=get_user&id=${userId}`);
                const data = await res.json();

                if (data.success && data.data) {
                    const u = data.data.user;
                    const trips = data.data.trips || [];
                    const stats = data.data.stats || {};
                    const fullName = escapeHtml((u.first_name || '') + ' ' + (u.last_name || ''));
                    const initials = ((u.first_name ? u.first_name[0] : '') + (u.last_name ? u.last_name[0] : '')).toUpperCase() || 'U';
                    const joined = u.created_at ? new Date(u.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'N/A';

                    let tripsHtml = '';
                    if (trips.length === 0) {
                        tripsHtml = '<div class="text-center py-4 text-muted small">No trips created by this user yet.</div>';
                    } else {
                        tripsHtml = '<div class="list-group list-group-flush">';
                        trips.forEach(t => {
                            const dateStr = t.start_date && t.end_date ? `${t.start_date} to ${t.end_date}` : 'Dates not set';
                            tripsHtml += `
                                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark mb-0.5">${escapeHtml(t.trip_name)}</div>
                                        <div class="small text-muted">${dateStr} &bull; ${t.stops_count || 0} stops</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-${t.status === 'completed' ? 'secondary' : (t.status === 'ongoing' ? 'success' : 'primary')} text-capitalize mb-1">${t.status}</span>
                                        <div class="small text-muted">${t.visibility}</div>
                                    </div>
                                </div>
                            `;
                        });
                        tripsHtml += '</div>';
                    }

                    contentEl.innerHTML = `
                        <div class="d-flex align-items-center gap-3.5 mb-4 pb-3 border-bottom">
                            <div class="user-avatar-sm" style="width: 60px; height: 60px; font-size: 1.25rem;">
                                ${u.profile_photo ? `<img src="${escapeHtml(u.profile_photo)}" alt="${fullName}" class="w-100 h-100 rounded-circle object-fit-cover">` : initials}
                            </div>
                            <div>
                                <h4 class="fw-bold text-dark mb-1">${fullName}</h4>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="role-badge ${u.role === 'admin' ? 'role-badge-admin' : 'role-badge-user'}">
                                        <i class="fa-solid fa-${u.role === 'admin' ? 'shield-halved' : 'user'}"></i>
                                        ${u.role === 'admin' ? 'Admin' : 'User'}
                                    </span>
                                    <span class="text-muted small">&bull; Member since ${joined}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="small text-muted text-uppercase fw-bold mb-1">Email Address</div>
                                    <div class="fw-medium text-dark">${escapeHtml(u.email)}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="small text-muted text-uppercase fw-bold mb-1">Phone</div>
                                    <div class="fw-medium text-dark">${escapeHtml(u.phone || 'Not provided')}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="small text-muted text-uppercase fw-bold mb-1">Location</div>
                                    <div class="fw-medium text-dark">${[u.city, u.country].filter(Boolean).join(', ') || 'Not specified'}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="small text-muted text-uppercase fw-bold mb-1">Language Preference</div>
                                    <div class="fw-medium text-dark text-uppercase">${escapeHtml(u.language_pref || 'en')}</div>
                                </div>
                            </div>
                        </div>

                        ${u.additional_info ? `
                            <div class="mb-4">
                                <h6 class="fw-bold text-dark mb-2">Bio / Travel Interests</h6>
                                <p class="text-secondary small p-3 bg-light rounded-3 mb-0">${escapeHtml(u.additional_info)}</p>
                            </div>
                        ` : ''}

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">Trips History (${stats.total_trips || 0})</h6>
                                <span class="badge bg-primary-subtle text-primary">${stats.total_activities || 0} activities planned</span>
                            </div>
                            ${tripsHtml}
                        </div>
                    `;
                } else {
                    contentEl.innerHTML = `<div class="alert alert-danger mb-0">${data.error || 'Failed to load user details.'}</div>`;
                }
            } catch (err) {
                contentEl.innerHTML = `<div class="alert alert-danger mb-0">Network error fetching user details.</div>`;
            }
        });
    });

    // 2. Toggle Role (Make Admin / Remove Admin)
    document.querySelectorAll('.toggle-role-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const userId = parseInt(this.dataset.id, 10);
            const currentRole = this.dataset.role;
            const targetRole = currentRole === 'admin' ? 'user' : 'admin';

            if (!confirm(`Are you sure you want to change this user's role to ${targetRole.toUpperCase()}?`)) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

            try {
                const res = await fetch('../api/admin.php?action=toggle_role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const data = await res.json();

                if (data.success && data.data) {
                    const newRole = data.data.new_role;

                    // Update memory array
                    const userObj = usersData.find(u => u.id === userId);
                    if (userObj) userObj.role = newRole;

                    // Update UI row elements
                    const badge = document.getElementById(`user-role-badge-${userId}`);
                    if (badge) {
                        badge.className = `role-badge ${newRole === 'admin' ? 'role-badge-admin' : 'role-badge-user'}`;
                        badge.innerHTML = `<i class="fa-solid fa-${newRole === 'admin' ? 'shield-halved' : 'user'}"></i> ${newRole === 'admin' ? 'Admin' : 'User'}`;
                    }

                    btn.dataset.role = newRole;
                    btn.className = `btn btn-sm ${newRole === 'admin' ? 'btn-outline-warning' : 'btn-outline-success'} px-2.5 py-1 toggle-role-btn`;
                    btn.innerHTML = `<i class="fa-solid fa-${newRole === 'admin' ? 'user-minus' : 'user-shield'}"></i> ${newRole === 'admin' ? 'Remove Admin' : 'Make Admin'}`;

                    showAdminToast(data.data.message || 'User role updated successfully!', 'success');
                } else {
                    showAdminToast(data.error || 'Failed to update user role.', 'danger');
                    btn.innerHTML = `<i class="fa-solid fa-${currentRole === 'admin' ? 'user-minus' : 'user-shield'}"></i> ${currentRole === 'admin' ? 'Remove Admin' : 'Make Admin'}`;
                }
            } catch (e) {
                showAdminToast('Network error updating role.', 'danger');
                btn.innerHTML = `<i class="fa-solid fa-${currentRole === 'admin' ? 'user-minus' : 'user-shield'}"></i> ${currentRole === 'admin' ? 'Remove Admin' : 'Make Admin'}`;
            } finally {
                btn.disabled = false;
            }
        });
    });

    // 3. Delete User Confirmation
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const userId = parseInt(this.dataset.id, 10);
            const userName = this.dataset.name;

            const modalEl = document.getElementById('deleteUserConfirmModal');
            if (!modalEl) return;

            document.getElementById('deleteUserNameSpan').textContent = userName;
            const confirmBtn = document.getElementById('confirmDeleteUserBtn');

            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            confirmBtn.onclick = async function () {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';

                try {
                    const res = await fetch('../api/admin.php?action=delete_user', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId })
                    });
                    const data = await res.json();

                    if (data.success) {
                        modal.hide();

                        // Remove from usersData array
                        usersData = usersData.filter(u => u.id !== userId);

                        // Remove row from DOM with fade animation
                        const row = document.getElementById(`user-row-${userId}`);
                        if (row) {
                            row.style.transition = 'all 0.3s ease';
                            row.style.opacity = '0';
                            row.style.transform = 'scale(0.95)';
                            setTimeout(() => row.remove(), 300);
                        }

                        // Update counters
                        const totalCounter = document.getElementById('totalUsersCount');
                        if (totalCounter) totalCounter.textContent = usersData.length;

                        showAdminToast(data.data.message || 'User deleted successfully.', 'success');
                    } else {
                        showAdminToast(data.error || 'Failed to delete user.', 'danger');
                    }
                } catch (e) {
                    showAdminToast('Network error deleting user.', 'danger');
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = 'Delete User';
                }
            };
        });
    });
}

// ── Chart.js Initializations ───────────────────────────────────────────────────
function initCharts() {
    initCitiesHorizontalBar();
    initActivitiesCategoryDonut();
    initRegistrationLineChart();
    initTripsBarChart();
}

// Tab 2: Top 8 Cities Horizontal Bar Chart
function initCitiesHorizontalBar() {
    const canvas = document.getElementById('citiesHorizontalBarChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = window.TOP_CITIES_LABELS || [];
    const data = window.TOP_CITIES_DATA || [];

    if (labels.length === 0) return;

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 400, 0);
    gradient.addColorStop(0, '#2563eb');
    gradient.addColorStop(1, '#06b6d4');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Times Added to Trips',
                data: data,
                backgroundColor: gradient,
                borderRadius: 6,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ` ${ctx.raw} ${ctx.raw === 1 ? 'trip' : 'trips'} planned`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { family: 'Inter', size: 11 } },
                    grid: { color: 'rgba(226, 232, 240, 0.6)', borderDash: [4, 4] }
                },
                y: {
                    ticks: { font: { family: 'Inter', weight: '600', size: 12 }, color: '#334155' },
                    grid: { display: false }
                }
            }
        }
    });
}

// Tab 3: Activities Category Donut Chart
function initActivitiesCategoryDonut() {
    const canvas = document.getElementById('activitiesCategoryChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = window.ACTIVITY_CATS_LABELS || [];
    const data = window.ACTIVITY_CATS_DATA || [];

    if (labels.length === 0) return;

    const ctx = canvas.getContext('2d');
    const categoryColors = {
        'Sightseeing': '#3b82f6',
        'Food': '#f97316',
        'Adventure': '#10b981',
        'Culture': '#8b5cf6',
        'Relaxation': '#6366f1',
        'Other': '#64748b'
    };

    const colors = labels.map(l => categoryColors[l] || '#3b82f6');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: { family: 'Inter', size: 12, weight: '500' },
                        boxWidth: 14,
                        padding: 12,
                        color: '#334155'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 10,
                    cornerRadius: 8
                }
            }
        }
    });
}

// Tab 4: Chart A — New User Registrations Over Time (12 Months Line Chart)
function initRegistrationLineChart() {
    const canvas = document.getElementById('userRegistrationLineChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = window.USER_TRENDS_LABELS || [];
    const data = window.USER_TRENDS_DATA || [];

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'New Registrations',
                data: data,
                borderColor: '#2563eb',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ` ${ctx.raw} new ${ctx.raw === 1 ? 'user' : 'users'}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { family: 'Inter', size: 11 } },
                    grid: { color: 'rgba(226, 232, 240, 0.6)', borderDash: [4, 4] }
                },
                x: {
                    ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' },
                    grid: { display: false }
                }
            }
        }
    });
}

// Tab 4: Chart C — Trips Created Per Month (6 Months Public vs Private Bar Chart)
function initTripsBarChart() {
    const canvas = document.getElementById('tripsPublicPrivateBarChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = window.TRIPS_MONTHLY_LABELS || [];
    const publicData = window.TRIPS_MONTHLY_PUBLIC || [];
    const privateData = window.TRIPS_MONTHLY_PRIVATE || [];

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Public Trips',
                    data: publicData,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.6
                },
                {
                    label: 'Private Trips',
                    data: privateData,
                    backgroundColor: '#6366f1',
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: { font: { family: 'Inter', size: 12, weight: '600' }, boxWidth: 12, color: '#334155' }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 10,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { family: 'Inter', size: 11 } },
                    grid: { color: 'rgba(226, 232, 240, 0.6)', borderDash: [4, 4] }
                },
                x: {
                    ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' },
                    grid: { display: false }
                }
            }
        }
    });
}

// ── Mobile Sidebar Drawer ──────────────────────────────────────────────────────
function initMobileSidebar() {
    const toggleBtn = document.getElementById('adminSidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('adminSidebarBackdrop');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileSidebar);
    }
}

function closeMobileSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
}

// ── Utilities & Toast ──────────────────────────────────────────────────────────
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showAdminToast(message, type = 'success') {
    let container = document.getElementById('adminToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'adminToastContainer';
        container.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 1100; display: flex; flex-direction: column; gap: 8px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} shadow-lg py-2.5 px-4 mb-0 rounded-3 d-flex align-items-center gap-2.5`;
    toast.style.cssText = 'min-width: 280px; animation: slideIn 0.3s ease;';
    toast.innerHTML = `
        <i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'triangle-exclamation'}"></i>
        <div class="fw-medium">${escapeHtml(message)}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
