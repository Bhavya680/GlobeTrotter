document.addEventListener('DOMContentLoaded', function() {
    initGlobalToast();
    initNavbarSearch();
    initNavbarFilters();
    initUserDropdown();
});

async function api(method, url, data) {
    const options = {
        method: method.toUpperCase(),
        headers: {},
    };

    if (data) {
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();
        if (!response.ok && result && result.error) {
            throw new Error(result.error);
        }
        return result;
    } catch (err) {
        console.error('API Request Error:', err);
        throw err;
    }
}

function initGlobalToast() {
    if (!document.getElementById('gtToastContainer')) {
        const container = document.createElement('div');
        container.id = 'gtToastContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
}

function toast(message, type) {
    if (!type) type = 'info';
    const container = document.getElementById('gtToastContainer');
    if (!container) return;

    const toastEl = document.createElement('div');
    const bgClass = type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'primary');
    toastEl.className = 'toast align-items-center text-white bg-' + bgClass + ' border-0 show shadow-lg mb-2';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    const iconClass = type === 'success' ? 'fa-circle-check' : (type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info');

    toastEl.innerHTML = '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2"><i class="fa-solid ' + iconClass + '"></i><span>' + escapeHtml(message) + '</span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';

    container.appendChild(toastEl);
    setTimeout(function() {
        toastEl.classList.remove('show');
        setTimeout(function() { toastEl.remove(); }, 300);
    }, 4000);
}

function initNavbarSearch() {
    const searchInput = document.getElementById('navSearchInput');
    const autocompleteBox = document.getElementById('navAutocomplete');
    if (!searchInput || !autocompleteBox) return;

    let timer = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            autocompleteBox.style.display = 'none';
            autocompleteBox.innerHTML = '';
            return;
        }

        timer = setTimeout(async function() {
            try {
                const citiesRes = await api('GET', '/api/cities.php?q=' + encodeURIComponent(q) + '&per_page=4');
                const tripsRes = await api('GET', '/api/trips.php');

                const cities = (citiesRes && citiesRes.data && citiesRes.data.cities) ? citiesRes.data.cities : [];
                const allTrips = (tripsRes && tripsRes.data) ? tripsRes.data : [];
                const matchingTrips = allTrips.filter(function(t) {
                    return (t.name || t.trip_name || '').toLowerCase().includes(q.toLowerCase());
                }).slice(0, 4);

                if (cities.length === 0 && matchingTrips.length === 0) {
                    autocompleteBox.innerHTML = '<div class="p-3 text-muted text-center small">No destinations or trips found.</div>';
                    autocompleteBox.style.display = 'block';
                    return;
                }

                let html = '';
                if (cities.length > 0) {
                    html += '<div class="px-3 pt-2 pb-1 text-uppercase fw-bold text-muted small border-bottom">Cities</div>';
                    cities.forEach(function(c) {
                        html += '<a href="city-search.php?q=' + encodeURIComponent(c.name) + '" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between text-decoration-none"><div><strong class="text-dark">' + escapeHtml(c.name) + '</strong>, <span class="text-muted small">' + escapeHtml(c.country) + '</span></div><span class="badge bg-light text-dark border">$' + c.cost_index + '</span></a>';
                    });
                }

                if (matchingTrips.length > 0) {
                    html += '<div class="px-3 pt-2 pb-1 text-uppercase fw-bold text-muted small border-bottom border-top">My Trips</div>';
                    matchingTrips.forEach(function(t) {
                        const tripName = t.name || t.trip_name || 'Untitled Trip';
                        html += '<a href="itinerary-builder.php?trip_id=' + t.id + '" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between text-decoration-none"><div><i class="fa-solid fa-suitcase me-2 text-primary"></i><strong class="text-dark">' + escapeHtml(tripName) + '</strong></div><span class="text-muted small">' + (t.start_date ? t.start_date : '') + '</span></a>';
                    });
                }

                autocompleteBox.innerHTML = html;
                autocompleteBox.style.display = 'block';

            } catch (e) {
                console.error('Navbar autocomplete error:', e);
            }
        }, 250);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteBox.contains(e.target)) {
            autocompleteBox.style.display = 'none';
        }
    });
}

function initNavbarFilters() {
    const navSortBy = document.getElementById('navSortBy');
    const navGroupBy = document.getElementById('navGroupBy');

    if (navSortBy) {
        navSortBy.addEventListener('change', function(e) {
            window.dispatchEvent(new CustomEvent('gt-sort-changed', { detail: { sort: e.target.value } }));
        });
    }

    if (navGroupBy) {
        navGroupBy.addEventListener('change', function(e) {
            window.dispatchEvent(new CustomEvent('gt-group-changed', { detail: { group: e.target.value } }));
        });
    }
}

function initUserDropdown() {
    const btn = document.getElementById('navAvatarBtn');
    const menu = document.getElementById('navUserDropdown');
    if (!btn || !menu) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.classList.toggle('active');
    });

    document.addEventListener('click', function() {
        menu.classList.remove('active');
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
