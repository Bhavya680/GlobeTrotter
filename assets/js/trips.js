/**
 * GlobeTrotter - Trips JS Engine (create-trip.php & my-trips.php)
 */

document.addEventListener('DOMContentLoaded', function() {
    initCreateTripForm();
    initMyTripsFilters();
    initRandomAutoFill();
});

function initCreateTripForm() {
    const form = document.getElementById('createTripForm');
    const stopsContainer = document.getElementById('stopsContainer');
    const addStopBtn = document.getElementById('addStopBtn');
    if (!form || !stopsContainer) return;

    let stopCounter = stopsContainer.querySelectorAll('.stop-card').length;

    // Attach autocomplete to existing stop city inputs
    stopsContainer.querySelectorAll('.city-search-input').forEach(attachCityAutocomplete);

    if (addStopBtn) {
        addStopBtn.addEventListener('click', function() {
            stopCounter++;
            const newCard = document.createElement('div');
            newCard.className = 'stop-card card mb-3 shadow-sm border-0';
            newCard.setAttribute('data-stop-index', stopCounter);
            newCard.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-location-dot me-2"></i>Stop ${stopCounter}</h6>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-stop-btn"><i class="fa-solid fa-trash"></i> Remove</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 position-relative">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" class="form-control form-control-sm city-search-input" placeholder="Search city..." autocomplete="off" required>
                            <input type="hidden" name="stop_city_id[]" class="city-id-input">
                            <div class="autocomplete-suggestions dropdown-menu w-100 shadow-sm"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Arrival Date</label>
                            <input type="date" name="stop_arrival_date[]" class="form-control form-control-sm stop-arrival">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Departure Date</label>
                            <input type="date" name="stop_departure_date[]" class="form-control form-control-sm stop-departure">
                        </div>
                    </div>
                </div>
            `;
            stopsContainer.appendChild(newCard);
            attachCityAutocomplete(newCard.querySelector('.city-search-input'));
            attachRemoveStop(newCard.querySelector('.remove-stop-btn'));
        });
    }

    stopsContainer.querySelectorAll('.remove-stop-btn').forEach(attachRemoveStop);
}

function attachCityAutocomplete(inputEl) {
    if (!inputEl) return;
    const parent = inputEl.closest('.position-relative');
    const hiddenId = parent.querySelector('.city-id-input');
    const suggestionsBox = parent.querySelector('.autocomplete-suggestions');

    let timer = null;

    inputEl.addEventListener('input', function() {
        clearTimeout(timer);
        const q = inputEl.value.trim();
        if (q.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        timer = setTimeout(async function() {
            try {
                const res = await api('GET', '/api/cities.php?q=' + encodeURIComponent(q));
                const cities = (res && res.data && res.data.cities) ? res.data.cities : [];
                if (cities.length === 0) {
                    suggestionsBox.innerHTML = '<div class="dropdown-item disabled small">No matching cities</div>';
                } else {
                    let html = '';
                    cities.forEach(function(c) {
                        html += '<a href="#" class="dropdown-item small py-2 d-flex justify-content-between" data-id="' + c.id + '" data-name="' + escapeHtml(c.name) + '" data-country="' + escapeHtml(c.country) + '"><span><strong>' + escapeHtml(c.name) + '</strong>, ' + escapeHtml(c.country) + '</span><span class="badge bg-light text-dark border">$' + c.cost_index + '</span></a>';
                    });
                    suggestionsBox.innerHTML = html;

                    suggestionsBox.querySelectorAll('.dropdown-item[data-id]').forEach(function(item) {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            hiddenId.value = this.dataset.id;
                            inputEl.value = this.dataset.name + ', ' + this.dataset.country;
                            suggestionsBox.style.display = 'none';
                        });
                    });
                }
                suggestionsBox.style.display = 'block';
            } catch (err) {
                console.error(err);
            }
        }, 200);
    });

    document.addEventListener('click', function(e) {
        if (!parent.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
}

function attachRemoveStop(btn) {
    if (!btn) return;
    btn.addEventListener('click', function() {
        const card = btn.closest('.stop-card');
        if (card) card.remove();
    });
}

function initMyTripsFilters() {
    const filterTabs = document.querySelectorAll('.gt-trip-filter-tab');
    const searchInput = document.getElementById('tripSearchInput');
    const tripCards = document.querySelectorAll('.gt-trip-card-wrap');
    if (tripCards.length === 0) return;

    function applyFilters() {
        const activeTab = document.querySelector('.gt-trip-filter-tab.active');
        const filterVal = activeTab ? activeTab.dataset.filter : 'all';
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        tripCards.forEach(function(card) {
            const status = card.dataset.status;
            const name = (card.dataset.name || '').toLowerCase();
            const cities = (card.dataset.cities || '').toLowerCase();

            const matchesStatus = (filterVal === 'all' || status === filterVal);
            const matchesQuery = (query === '' || name.includes(query) || cities.includes(query));

            if (matchesStatus && matchesQuery) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    filterTabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            filterTabs.forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    // Attach AJAX Delete Trip handlers
    document.querySelectorAll('.delete-trip-btn').forEach(function(btn) {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const tripId = btn.dataset.tripId;
            const tripName = btn.dataset.tripName || 'this trip';
            if (confirm('Are you sure you want to delete "' + tripName + '"? This action cannot be undone.')) {
                try {
                    const res = await api('DELETE', '/api/trips.php?id=' + tripId);
                    if (res && res.success) {
                        toast('Trip deleted successfully', 'success');
                        const cardWrap = btn.closest('.gt-trip-card-wrap');
                        if (cardWrap) cardWrap.remove();
                    } else {
                        toast('Failed to delete trip', 'error');
                    }
                } catch (err) {
                    toast(err.message || 'Error deleting trip', 'error');
                }
            }
        });
    });
}

function initRandomAutoFill() {
    const btn = document.getElementById('btnAutoFillRandom');
    if (!btn) return;

    const sampleTrips = [
        {
            name: 'European Summer Odyssey 2026',
            description: 'Exploring historic art, world-class gastronomy, and vibrant coastal culture across Paris, Rome, and Barcelona.',
            stops: [
                { city_id: 1, name: 'Paris, France' },
                { city_id: 2, name: 'Rome, Italy' },
                { city_id: 3, name: 'Barcelona, Spain' }
            ]
        },
        {
            name: 'Tokyo & Bali Tropical Escape',
            description: 'A perfect blend of high-tech metropolis neon lights and serene tropical beaches in Southeast Asia.',
            stops: [
                { city_id: 4, name: 'Tokyo, Japan' },
                { city_id: 5, name: 'Bali, Indonesia' }
            ]
        },
        {
            name: 'Grand New York & London Highlights',
            description: 'Experiencing Broadway shows, iconic landmarks, royal parks, and premier dining in top global capitals.',
            stops: [
                { city_id: 6, name: 'New York, USA' },
                { city_id: 7, name: 'London, UK' }
            ]
        }
    ];

    btn.addEventListener('click', function() {
        const choice = sampleTrips[Math.floor(Math.random() * sampleTrips.length)];

        const nameEl = document.getElementById('trip_name');
        const descEl = document.getElementById('description');
        const startEl = document.getElementById('start_date');
        const endEl = document.getElementById('end_date');

        if (nameEl) {
            nameEl.value = choice.name;
            nameEl.dispatchEvent(new Event('input'));
        }
        if (descEl) {
            descEl.value = choice.description;
            descEl.dispatchEvent(new Event('input'));
        }

        // Set realistic dates starting 7 days from today for 10 days
        const today = new Date();
        const start = new Date(today);
        start.setDate(start.getDate() + 7);
        const end = new Date(start);
        end.setDate(end.getDate() + 10);

        const formatDate = d => d.toISOString().split('T')[0];
        if (startEl) startEl.value = formatDate(start);
        if (endEl) endEl.value = formatDate(end);

        // Pre-fill stop inputs if available
        const stopsContainer = document.getElementById('stopsContainer');
        if (stopsContainer && choice.stops) {
            const stopCards = stopsContainer.querySelectorAll('.stop-card');
            choice.stops.forEach((st, idx) => {
                let card = stopCards[idx];
                if (!card) {
                    const addBtn = document.getElementById('addStopBtn');
                    if (addBtn) addBtn.click();
                    const updatedCards = stopsContainer.querySelectorAll('.stop-card');
                    card = updatedCards[updatedCards.length - 1];
                }
                if (card) {
                    const cityInput = card.querySelector('.city-search-input');
                    const hiddenInput = card.querySelector('.city-id-input');
                    if (cityInput && hiddenInput) {
                        cityInput.value = st.name;
                        hiddenInput.value = st.city_id;
                    }
                }
            });
        }

        if (typeof toast === 'function') {
            toast('Auto-filled details for ' + choice.name, 'success');
        }
    });
}
