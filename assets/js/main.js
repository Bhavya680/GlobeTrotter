/**
 * GlobeTrotter Global Client Utilities
 */

document.addEventListener('DOMContentLoaded', function() {
    initGlobalToast();
    initNavbarSearch();
    initNavbarFilters();
    initUserDropdown();
    initCurrencyInputs();
});

/**
 * Universal AJAX Request Wrapper with automatic CSRF token injection
 */
async function api(method, url, data) {
    const verb = method.toUpperCase();
    const options = {
        method: verb,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
    };

    // Inject CSRF token for mutating methods
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && csrfMeta.content) {
        options.headers['X-CSRF-Token'] = csrfMeta.content;
    }

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
        console.error('API Request Error (' + url + '):', err);
        throw err;
    }
}

/**
 * Toast Notification System
 */
function initGlobalToast() {
    if (!document.getElementById('gtToastContainer')) {
        const container = document.createElement('div');
        container.id = 'gtToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '99999';
        document.body.appendChild(container);
    }
}

function showToast(message, type) {
    toast(message, type);
}

function toast(message, type) {
    if (!type) type = 'info';
    let container = document.getElementById('gtToastContainer');
    if (!container) {
        initGlobalToast();
        container = document.getElementById('gtToastContainer');
    }
    if (!container) return;

    const toastEl = document.createElement('div');
    let bgClass = 'bg-primary';
    let iconClass = 'fa-circle-info';

    if (type === 'error' || type === 'danger') {
        bgClass = 'bg-danger';
        iconClass = 'fa-triangle-exclamation';
    } else if (type === 'success') {
        bgClass = 'bg-success';
        iconClass = 'fa-circle-check';
    } else if (type === 'warning') {
        bgClass = 'bg-warning text-dark';
        iconClass = 'fa-circle-exclamation';
    }

    toastEl.className = 'toast align-items-center text-white border-0 show shadow-lg mb-2 rounded-3 overflow-hidden ' + bgClass;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    const textColorClass = type === 'warning' ? 'text-dark' : 'text-white';

    toastEl.innerHTML = `
        <div class="d-flex align-items-center justify-content-between p-3 ${textColorClass}">
            <div class="d-flex align-items-center gap-2.5">
                <i class="fa-solid ${iconClass} fs-5"></i>
                <span class="fw-medium">${escapeHtml(message)}</span>
            </div>
            <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'} ms-3" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    container.appendChild(toastEl);

    setTimeout(function() {
        toastEl.classList.remove('show');
        setTimeout(function() { toastEl.remove(); }, 300);
    }, 3200);
}

/**
 * Reusable Loading States for Buttons
 */
function showLoading(element, loadingText) {
    if (!element) return;
    element.dataset.originalHtml = element.innerHTML;
    element.disabled = true;
    const text = loadingText || 'Loading...';
    element.innerHTML = `<span class="spinner-border spinner-border-sm me-1.5" role="status" aria-hidden="true"></span> ${escapeHtml(text)}`;
}

function hideLoading(element, originalHtml) {
    if (!element) return;
    element.disabled = false;
    element.innerHTML = originalHtml || element.dataset.originalHtml || 'Submit';
}

/**
 * Navbar Instant Search & Suggestions
 */
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

function initCurrencyInputs() {
    const currencyInputs = document.querySelectorAll('.currency-input, input[name="budget"], input[name="estimated_cost"], input[name="amount"]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            let val = parseFloat(input.value);
            if (!isNaN(val) && val >= 0) {
                input.value = val.toFixed(2);
            }
        });
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
