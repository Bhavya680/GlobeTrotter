/**
 * GlobeTrotter - Itinerary Builder JS Engine
 */

document.addEventListener('DOMContentLoaded', function() {
    if (typeof TRIP_DATA === 'undefined') return;

    initActivityModal();
    initStopActions();
    initStickyBar();
    initTripNameEdit();
});

const TRIP_ID = typeof TRIP_DATA !== 'undefined' ? TRIP_DATA.id : 0;
const API_STOPS = '/api/stops.php';
const API_TRIPS = '/api/trips.php';
const API_ACTIVITIES = '/api/activities.php';

let activeStopForModal = null;

function initActivityModal() {
    const modalEl = document.getElementById('addActivityModal');
    if (!modalEl) return;
    const bsModal = new bootstrap.Modal(modalEl);

    const searchInput = document.getElementById('actSearchInput');
    const categorySelect = document.getElementById('actCategorySelect');
    const resultsContainer = document.getElementById('actSearchResults');

    document.querySelectorAll('.ib-add-activity-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            activeStopForModal = {
                stopId: btn.dataset.stopId,
                cityId: btn.dataset.cityId,
                cityName: btn.dataset.cityName,
                startDate: btn.dataset.start
            };

            const titleEl = document.getElementById('actModalTitle');
            if (titleEl) titleEl.textContent = 'Add Activity for ' + (activeStopForModal.cityName || 'Stop');

            loadModalActivities();
            bsModal.show();
        });
    });

    if (searchInput) searchInput.addEventListener('input', debounce(loadModalActivities, 300));
    if (categorySelect) categorySelect.addEventListener('change', loadModalActivities);

    async function loadModalActivities() {
        if (!activeStopForModal || !resultsContainer) return;
        resultsContainer.innerHTML = '<div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading activities...</div>';

        const q = searchInput ? searchInput.value.trim() : '';
        const cat = categorySelect ? categorySelect.value : '';

        let url = API_ACTIVITIES + '?city_id=' + activeStopForModal.cityId;
        if (q) url += '&q=' + encodeURIComponent(q);
        if (cat) url += '&category=' + encodeURIComponent(cat);

        try {
            const res = await api('GET', url);
            const activities = (res && res.data && res.data.activities) ? res.data.activities : [];

            if (activities.length === 0) {
                resultsContainer.innerHTML = '<div class="text-center py-4 text-muted">No activities found for this filter.</div>';
                return;
            }

            let html = '<div class="row g-3">';
            activities.forEach(function(act) {
                html += `
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm border">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0 text-dark">${escapeHtml(act.name)}</h6>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">${escapeHtml(act.category)}</span>
                                    </div>
                                    <p class="text-muted small mb-2">${escapeHtml(act.description || '')}</p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                    <span class="fw-bold text-success">$${parseFloat(act.cost).toFixed(2)}</span>
                                    <button class="btn btn-sm btn-outline-primary select-act-btn"
                                            data-act-id="${act.id}" data-act-name="${escapeHtml(act.name)}"
                                            data-act-cost="${act.cost}" data-act-cat="${act.category}">
                                        <i class="fa-solid fa-plus me-1"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            resultsContainer.innerHTML = html;

            resultsContainer.querySelectorAll('.select-act-btn').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Adding';
                    try {
                        const actId = btn.dataset.actId;
                        const postData = {
                            activity_id: actId,
                            scheduled_date: activeStopForModal.startDate
                        };
                        const res = await api('POST', API_STOPS + '?action=activities&stop_id=' + activeStopForModal.stopId, postData);
                        if (res && res.success) {
                            toast('Activity added to stop!', 'success');
                            bsModal.hide();
                            location.reload(); // Refresh itinerary builder state cleanly
                        } else {
                            toast(res.error || 'Failed to add activity', 'error');
                        }
                    } catch (e) {
                        toast(e.message || 'Error adding activity', 'error');
                    } finally {
                        btn.disabled = false;
                    }
                });
            });

        } catch (err) {
            resultsContainer.innerHTML = '<div class="text-center py-4 text-danger">Failed to load activities.</div>';
        }
    }
}

function initStopActions() {
    // Remove Activity Handler
    document.querySelectorAll('.ib-activity-remove').forEach(function(btn) {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const saId = btn.dataset.saId;
            if (confirm('Remove this activity from the stop?')) {
                try {
                    const res = await api('DELETE', API_STOPS + '?action=activities&id=' + saId);
                    if (res && res.success) {
                        toast('Activity removed', 'success');
                        const row = btn.closest('.ib-activity-row');
                        if (row) row.remove();
                    } else {
                        toast('Failed to remove activity', 'error');
                    }
                } catch (err) {
                    toast(err.message || 'Error removing activity', 'error');
                }
            }
        });
    });

    // Remove Stop Handler
    document.querySelectorAll('[data-action="remove-stop"]').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const sid = btn.dataset.stopId;
            if (confirm('Are you sure you want to remove this stop from your itinerary?')) {
                try {
                    const res = await api('DELETE', API_STOPS + '?id=' + sid);
                    if (res && res.success) {
                        toast('Stop removed', 'success');
                        const card = document.getElementById('stop-' + sid);
                        if (card) card.remove();
                    } else {
                        toast('Failed to remove stop', 'error');
                    }
                } catch (err) {
                    toast(err.message || 'Error deleting stop', 'error');
                }
            }
        });
    });

    // Move Stop Up / Down
    document.querySelectorAll('[data-action="move-up"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const sid = btn.dataset.stopId;
            const card = document.getElementById('stop-' + sid);
            if (card && card.previousElementSibling) {
                card.parentNode.insertBefore(card, card.previousElementSibling);
                persistStopOrder();
            }
        });
    });

    document.querySelectorAll('[data-action="move-down"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const sid = btn.dataset.stopId;
            const card = document.getElementById('stop-' + sid);
            if (card && card.nextElementSibling) {
                card.parentNode.insertBefore(card.nextElementSibling, card);
                persistStopOrder();
            }
        });
    });
}

async function persistStopOrder() {
    const container = document.getElementById('stopsContainer');
    if (!container) return;

    const stops = [];
    container.querySelectorAll('.ib-stop-card').forEach(function(card, idx) {
        stops.push({
            id: parseInt(card.dataset.stopId),
            sort_order: idx
        });
    });

    if (stops.length > 0) {
        try {
            await api('POST', API_STOPS + '?action=reorder', { stops: stops });
            toast('Stop order updated', 'info');
        } catch (err) {
            console.error('Reorder error:', err);
        }
    }
}

function initStickyBar() {
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const continueBtn = document.getElementById('continueBtn');
    const saveIndicator = document.getElementById('saveIndicator');

    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', async function() {
            if (saveIndicator) {
                saveIndicator.className = 'ib-save-indicator saving';
                saveIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving draft...';
            }
            saveDraftBtn.disabled = true;

            const promises = [];
            document.querySelectorAll('.ib-stop-card').forEach(function(card) {
                const sid = parseInt(card.dataset.stopId);
                const patch = {};
                const arrival = card.querySelector('#arrival-' + sid)?.value;
                const dep = card.querySelector('#departure-' + sid)?.value;
                const trans = card.querySelector('.stop-transport')?.value;
                const accom = card.querySelector('.stop-accom')?.value;
                const aCost = card.querySelector('.stop-budget')?.value;
                const notes = card.querySelector('.stop-notes')?.value;

                if (arrival) patch.start_date = arrival;
                if (dep) patch.end_date = dep;
                if (trans !== undefined) patch.transport_note = trans;
                if (accom !== undefined) patch.accommodation = accom;
                if (aCost !== undefined) patch.accommodation_cost = aCost === '' ? null : parseFloat(aCost);
                if (notes !== undefined) patch.stop_notes = notes;

                if (Object.keys(patch).length) {
                    promises.push(api('PUT', API_STOPS + '?id=' + sid, patch));
                }
            });

            try {
                await Promise.all(promises);
                if (saveIndicator) {
                    saveIndicator.className = 'ib-save-indicator saved';
                    saveIndicator.innerHTML = '<i class="fa-solid fa-circle-check me-1 text-success"></i> Draft Saved';
                }
                toast('Draft saved successfully!', 'success');
            } catch (err) {
                if (saveIndicator) {
                    saveIndicator.className = 'ib-save-indicator error';
                    saveIndicator.innerHTML = '<i class="fa-solid fa-xmark me-1 text-danger"></i> Save failed';
                }
                toast('Failed to save draft', 'error');
            } finally {
                saveDraftBtn.disabled = false;
            }
        });
    }

    if (continueBtn) {
        continueBtn.addEventListener('click', function() {
            window.location.href = 'itinerary-view.php?trip_id=' + TRIP_ID;
        });
    }
}

function initTripNameEdit() {
    const editBtn = document.getElementById('editTripNameBtn');
    if (!editBtn) return;

    editBtn.addEventListener('click', async function() {
        const display = document.getElementById('tripNameDisplay');
        const current = display ? display.textContent.trim() : '';
        const newName = prompt('Edit trip name:', current);
        if (!newName || newName.trim() === current) return;

        try {
            const res = await api('PUT', API_TRIPS + '?id=' + TRIP_ID, { name: newName.trim() });
            if (res && res.success) {
                if (display) display.textContent = newName.trim();
                document.title = 'Build Itinerary - ' + newName.trim() + ' - GlobeTrotter';
                toast('Trip name updated!', 'success');
            } else {
                toast(res.error || 'Failed to update trip name', 'error');
            }
        } catch (err) {
            toast(err.message || 'Network error', 'error');
        }
    });
}

function debounce(fn, delay) {
    let t;
    return function(...args) {
        clearTimeout(t);
        t = setTimeout(function() { fn(...args); }, delay);
    };
}
