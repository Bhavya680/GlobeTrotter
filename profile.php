<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();

$stmt = $pdo->prepare('
    SELECT id, first_name, last_name, email, phone, city, country, profile_photo, additional_info, language_pref, preferences, created_at
    FROM users WHERE id = ?
');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$prefs = json_decode($user['preferences'] ?? '{}', true) ?: [];
$isPublicDefault = !empty($prefs['public_by_default']);

// Fetch Trips
$tripsStmt = $pdo->prepare("
    SELECT id, trip_name, start_date, end_date, cover_photo, status, visibility, share_slug
    FROM trips WHERE user_id = ? ORDER BY start_date ASC
");
$tripsStmt->execute([$userId]);
$allTrips = $tripsStmt->fetchAll();

$upcomingTrips = array_filter($allTrips, fn($t) => $t['status'] === 'upcoming' || $t['status'] === 'ongoing');
$completedTrips = array_filter($allTrips, fn($t) => $t['status'] === 'completed');

// Fetch Saved Destinations
$savedStmt = $pdo->prepare('
    SELECT c.id, c.name, c.country, c.image_url, c.cost_index
    FROM saved_destinations sd
    JOIN cities c ON c.id = sd.city_id
    WHERE sd.user_id = ?
    ORDER BY sd.saved_at DESC
');
$savedStmt->execute([$userId]);
$savedDestinations = $savedStmt->fetchAll();

// Fetch Stats
$stats = [
    'trips' => count($allTrips),
    'countries' => 0,
    'activities' => 0
];
$countriesStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT c.country)
    FROM trip_stops s
    JOIN cities c ON c.id = s.city_id
    JOIN trips t ON t.id = s.trip_id
    WHERE t.user_id = ? AND t.status = 'completed'
");
$countriesStmt->execute([$userId]);
$stats['countries'] = (int)$countriesStmt->fetchColumn();

$actStmt = $pdo->prepare("
    SELECT COUNT(sa.id)
    FROM trip_activities sa
    JOIN trip_stops s ON s.id = sa.trip_stop_id
    JOIN trips t ON t.id = s.trip_id
    WHERE t.user_id = ? AND t.status = 'completed'
");
$actStmt->execute([$userId]);
$stats['activities'] = (int)$actStmt->fetchColumn();

$userPhoto = !empty($user['profile_photo']) ? SITE_URL . '/assets/uploads/profiles/' . htmlspecialchars($user['profile_photo']) : null;
$pageTitle = 'User Profile & Settings — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.profile-photo-container {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    position: relative;
    margin: 0 auto;
    background-color: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.profile-photo-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    cursor: pointer;
    font-size: 0.9rem;
}
.profile-photo-container:hover .profile-photo-overlay {
    opacity: 1;
}
.scroll-row {
    display: flex;
    overflow-x: auto;
    gap: 1rem;
    padding-bottom: 1rem;
    scroll-snap-type: x mandatory;
}
.scroll-row::-webkit-scrollbar {
    height: 8px;
}
.scroll-row::-webkit-scrollbar-track {
    background: #f1f1f1; 
    border-radius: 4px;
}
.scroll-row::-webkit-scrollbar-thumb {
    background: #ccc; 
    border-radius: 4px;
}
.scroll-row::-webkit-scrollbar-thumb:hover {
    background: #bbb; 
}
.mini-card {
    min-width: 260px;
    scroll-snap-align: start;
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}
.mini-card:hover {
    transform: translateY(-3px);
}
.mini-card-img {
    height: 120px;
    background-size: cover;
    background-position: center;
}
.city-card {
    transition: all 0.3s ease;
}
.city-card.removing {
    opacity: 0;
    transform: scale(0.9);
}
</style>

<div class="container py-4">
    <!-- Profile Header -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <div class="profile-photo-container mb-3" onclick="document.getElementById('profPhotoUpload').click();">
                <?php if ($userPhoto): ?>
                    <img src="<?= $userPhoto ?>" style="width: 100%; height: 100%; object-fit: cover;" id="profileImgPreview">
                <?php else: ?>
                    <span id="profileImgInitial"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></span>
                <?php endif; ?>
                <div class="profile-photo-overlay flex-column">
                    <i class="fa-solid fa-camera mb-1"></i>
                    <span>Change</span>
                </div>
            </div>
            <h2 class="fw-bold mb-1" id="displayFullName"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
            <div class="d-flex justify-content-center gap-4 text-muted mb-3">
                <span><i class="fa-solid fa-envelope me-1"></i><span id="displayEmail"><?= htmlspecialchars($user['email']) ?></span></span>
                <?php if ($user['city'] || $user['country']): ?>
                    <span><i class="fa-solid fa-location-dot me-1 text-danger"></i><span id="displayLocation"><?= htmlspecialchars(implode(', ', array_filter([$user['city'], $user['country']]))) ?></span></span>
                <?php endif; ?>
            </div>
            <button class="btn btn-outline-primary rounded-pill px-4" id="btnToggleEdit"><i class="fa-solid fa-pen me-2"></i>Edit Profile</button>
        </div>
    </div>

    <!-- Edit Profile Form (Hidden) -->
    <div class="card border-0 shadow-sm mb-4 d-none" id="editProfileSection">
        <div class="card-header bg-white border-bottom fw-bold py-3">
            <i class="fa-solid fa-user-pen me-2 text-primary"></i>Edit Profile Details
        </div>
        <div class="card-body p-4">
            <form id="profileUpdateForm">
                <input type="file" id="profPhotoUpload" class="d-none" accept="image/jpeg, image/png, image/webp">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        <div class="form-text text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>Changing email requires re-verification. Just save for now.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?: '') ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?: '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($user['country'] ?: '') ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Additional Information</label>
                        <textarea name="additional_info" class="form-control" rows="2"><?= htmlspecialchars($user['additional_info'] ?: '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Language</label>
                        <select name="language_pref" class="form-select" disabled>
                            <option value="en" <?= $user['language_pref'] === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="fr" <?= $user['language_pref'] === 'fr' ? 'selected' : '' ?>>French</option>
                            <option value="es" <?= $user['language_pref'] === 'es' ? 'selected' : '' ?>>Spanish</option>
                            <option value="ja" <?= $user['language_pref'] === 'ja' ? 'selected' : '' ?>>Japanese</option>
                        </select>
                        <div class="form-text">Display only.</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-light" id="btnCancelEdit">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pre-planned Trips -->
    <div class="mb-5">
        <h4 class="fw-bold mb-3"><i class="fa-solid fa-calendar-alt text-primary me-2"></i>Pre-planned Trips</h4>
        <?php if (empty($upcomingTrips)): ?>
            <div class="card border-0 bg-light text-center py-4 rounded-3">
                <p class="text-muted mb-0">No upcoming trips. <a href="create-trip.php" class="text-decoration-none fw-bold">Start planning one!</a></p>
            </div>
        <?php else: ?>
            <div class="scroll-row">
                <?php foreach ($upcomingTrips as $t): 
                    $img = $t['cover_photo'] ? SITE_URL . '/assets/uploads/covers/' . htmlspecialchars($t['cover_photo']) : 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=600&q=80';
                ?>
                <div class="card mini-card">
                    <div class="mini-card-img" style="background-image: url('<?= $img ?>')"></div>
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-truncate mb-1"><?= htmlspecialchars($t['trip_name']) ?></h6>
                        <p class="text-muted small mb-2"><i class="fa-regular fa-clock me-1"></i><?= $t['start_date'] ?> - <?= $t['end_date'] ?></p>
                        <a href="itinerary-view.php?trip_id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary w-100">View Itinerary</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Previous Trips -->
    <div class="mb-5">
        <h4 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Previous Trips</h4>
        <?php if (empty($completedTrips)): ?>
            <div class="card border-0 bg-light text-center py-4 rounded-3">
                <p class="text-muted mb-0">You haven't completed any trips yet.</p>
            </div>
        <?php else: ?>
            <div class="scroll-row">
                <?php foreach ($completedTrips as $t): 
                    $img = $t['cover_photo'] ? SITE_URL . '/assets/uploads/covers/' . htmlspecialchars($t['cover_photo']) : 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=600&q=80';
                    $isPublic = $t['visibility'] === 'public';
                ?>
                <div class="card mini-card">
                    <div class="mini-card-img" style="background-image: url('<?= $img ?>')">
                        <div class="p-2 text-end">
                            <?php if ($isPublic): ?>
                                <a href="public-itinerary.php?slug=<?= $t['share_slug'] ?>" target="_blank" class="btn btn-sm btn-light rounded-circle shadow-sm text-primary" title="Share Link"><i class="fa-solid fa-share-nodes"></i></a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-light rounded-circle shadow-sm text-muted" title="Make trip public to share" disabled><i class="fa-solid fa-share-nodes"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-truncate mb-1"><?= htmlspecialchars($t['trip_name']) ?></h6>
                        <p class="text-muted small mb-2"><i class="fa-regular fa-clock me-1"></i><?= $t['start_date'] ?> - <?= $t['end_date'] ?></p>
                        <a href="itinerary-view.php?trip_id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success w-100">View Trip</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Saved Destinations -->
    <div class="mb-5">
        <h4 class="fw-bold mb-3"><i class="fa-solid fa-heart text-danger me-2"></i>Saved Destinations</h4>
        <?php if (empty($savedDestinations)): ?>
            <div class="card border-0 bg-light text-center py-4 rounded-3">
                <p class="text-muted mb-0">No saved destinations yet. <a href="city-search.php" class="text-decoration-none fw-bold">Explore cities!</a></p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($savedDestinations as $sd): ?>
                    <div class="col-md-6 col-lg-4 city-card" id="cityCard_<?= $sd['id'] ?>">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle me-3 flex-shrink-0" style="width:50px; height:50px; background-image:url('<?= $sd['image_url'] ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=100' ?>'); background-size:cover;"></div>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($sd['name']) ?></h6>
                                    <span class="text-muted small d-block text-truncate"><?= htmlspecialchars($sd['country']) ?> &bull; Cost: <?= $sd['cost_index'] ?></span>
                                </div>
                                <div class="d-flex flex-column gap-1 ms-2">
                                    <button class="btn btn-sm btn-light text-danger btn-remove-city" data-id="<?= $sd['id'] ?>" title="Remove"><i class="fa-solid fa-xmark"></i></button>
                                    <a href="city-search.php?city_id=<?= $sd['id'] ?>" class="btn btn-sm btn-light text-primary" title="Explore"><i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Account Settings (Collapsible) -->
    <div class="accordion mb-5 shadow-sm rounded-3 overflow-hidden border-0" id="settingsAccordion">
        <div class="accordion-item border-0">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-bold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#settingsCollapse">
                    <i class="fa-solid fa-gear text-secondary me-2"></i> Account Settings
                </button>
            </h2>
            <div id="settingsCollapse" class="accordion-collapse collapse bg-light" data-bs-parent="#settingsAccordion">
                <div class="accordion-body p-4">
                    <div class="row g-4">
                        <!-- Password & Privacy -->
                        <div class="col-md-6 border-end">
                            <h5 class="fw-bold mb-3">Change Password</h5>
                            <form id="passwordForm">
                                <div class="mb-2">
                                    <input type="password" name="current_password" class="form-control" placeholder="Current Password" required>
                                </div>
                                <div class="mb-2">
                                    <input type="password" name="new_password" id="newPwd" class="form-control" placeholder="New Password" required minlength="8">
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div id="pwdStrength" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required minlength="8">
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-sm">Update Password</button>
                            </form>

                            <hr class="my-4">

                            <h5 class="fw-bold mb-3">Privacy Settings</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="publicDefaultToggle" <?= $isPublicDefault ? 'checked' : '' ?>>
                                <label class="form-check-label" for="publicDefaultToggle">Make all new trips public by default</label>
                            </div>
                        </div>
                        
                        <!-- Danger Zone -->
                        <div class="col-md-6">
                            <h5 class="fw-bold text-danger mb-3">Danger Zone</h5>
                            <p class="text-muted small">Deleting your account is permanent. All trips, stops, activities, and saved destinations will be instantly removed.</p>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Summary Stats -->
    <div class="card border-0 bg-primary text-white shadow rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4"><i class="fa-solid fa-chart-pie me-2"></i>Your Globetrotter Summary</h5>
            <div class="row text-center g-4">
                <div class="col-4 border-end border-light border-opacity-25">
                    <i class="fa-solid fa-plane fa-2x mb-2 opacity-75"></i>
                    <h2 class="fw-bold mb-0 stat-counter" data-target="<?= $stats['trips'] ?>">0</h2>
                    <span class="small opacity-75">Trips Created</span>
                </div>
                <div class="col-4 border-end border-light border-opacity-25">
                    <i class="fa-solid fa-earth-americas fa-2x mb-2 opacity-75"></i>
                    <h2 class="fw-bold mb-0 stat-counter" data-target="<?= $stats['countries'] ?>">0</h2>
                    <span class="small opacity-75">Countries Visited</span>
                </div>
                <div class="col-4">
                    <i class="fa-solid fa-person-hiking fa-2x mb-2 opacity-75"></i>
                    <h2 class="fw-bold mb-0 stat-counter" data-target="<?= $stats['activities'] ?>">0</h2>
                    <span class="small opacity-75">Activities Completed</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Delete Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p>Are you sure you want to delete your account? This action <strong>cannot</strong> be undone.</p>
                <p>Please type your email address <strong><?= htmlspecialchars($user['email']) ?></strong> to confirm.</p>
                <form id="deleteAccountForm">
                    <input type="email" name="email_confirm" class="form-control mb-3" placeholder="Email address" required>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // Toggle Edit Mode
    const btnToggleEdit = document.getElementById('btnToggleEdit');
    const btnCancelEdit = document.getElementById('btnCancelEdit');
    const editSection = document.getElementById('editProfileSection');
    
    btnToggleEdit.addEventListener('click', () => {
        editSection.classList.remove('d-none');
        btnToggleEdit.classList.add('d-none');
        document.querySelector('input[name="first_name"]').focus();
    });
    
    btnCancelEdit.addEventListener('click', () => {
        editSection.classList.add('d-none');
        btnToggleEdit.classList.remove('d-none');
    });

    // Profile Photo Preview & Upload attached to main form
    const photoInput = document.getElementById('profPhotoUpload');
    let selectedPhoto = null;
    
    photoInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) {
            selectedPhoto = e.target.files[0];
            const reader = new FileReader();
            reader.onload = function(ev) {
                const container = document.querySelector('.profile-photo-container');
                let img = document.getElementById('profileImgPreview');
                if (!img) {
                    const span = document.getElementById('profileImgInitial');
                    if (span) span.remove();
                    img = document.createElement('img');
                    img.id = 'profileImgPreview';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    container.insertBefore(img, container.firstChild);
                }
                img.src = ev.target.result;
            }
            reader.readAsDataURL(selectedPhoto);
            // Open edit section if hidden so they can save
            editSection.classList.remove('d-none');
            btnToggleEdit.classList.add('d-none');
        }
    });

    // Handle Profile Update
    document.getElementById('profileUpdateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const origBtn = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        const formData = new FormData(e.target);
        if (selectedPhoto) {
            formData.append('profile_photo', selectedPhoto);
        }

        try {
            const res = await api('POST', '/api/profile.php', formData);
            if (res && res.success) {
                toast('Profile updated successfully!', 'success');
                // Update display values
                document.getElementById('displayFullName').textContent = formData.get('first_name') + ' ' + formData.get('last_name');
                document.getElementById('displayEmail').textContent = formData.get('email');
                const loc = [formData.get('city'), formData.get('country')].filter(Boolean).join(', ');
                const locSpan = document.getElementById('displayLocation');
                if (locSpan) locSpan.textContent = loc;
                
                selectedPhoto = null;
                btnCancelEdit.click();
            } else {
                toast(res.error || 'Update failed', 'error');
            }
        } catch (err) {
            toast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origBtn;
        }
    });

    // Remove Saved Destination
    document.querySelectorAll('.btn-remove-city').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const cityId = btn.dataset.id;
            try {
                const res = await api('POST', '/api/profile.php?action=toggle_saved', { city_id: cityId });
                if (res && res.success && res.data.saved === false) {
                    const card = document.getElementById(`cityCard_${cityId}`);
                    card.classList.add('removing');
                    setTimeout(() => card.remove(), 300);
                    toast('Destination removed', 'info');
                }
            } catch (err) {
                toast('Error removing destination', 'error');
            }
        });
    });

    // Password Strength
    const newPwd = document.getElementById('newPwd');
    const pwdStrength = document.getElementById('pwdStrength');
    if (newPwd) {
        newPwd.addEventListener('input', (e) => {
            const val = e.target.value;
            let strength = 0;
            if (val.length >= 8) strength += 25;
            if (val.match(/[A-Z]/)) strength += 25;
            if (val.match(/[0-9]/)) strength += 25;
            if (val.match(/[^A-Za-z0-9]/)) strength += 25;
            
            pwdStrength.style.width = strength + '%';
            if (strength <= 25) { pwdStrength.className = 'progress-bar bg-danger'; }
            else if (strength <= 50) { pwdStrength.className = 'progress-bar bg-warning'; }
            else if (strength <= 75) { pwdStrength.className = 'progress-bar bg-info'; }
            else { pwdStrength.className = 'progress-bar bg-success'; }
        });
    }

    // Change Password Form
    document.getElementById('passwordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        if (data.new_password !== data.confirm_password) {
            toast('Passwords do not match', 'error');
            return;
        }
        
        const btn = e.target.querySelector('button');
        btn.disabled = true;
        try {
            const res = await api('POST', '/api/profile.php?action=change_password', data);
            if (res && res.success) {
                toast(res.data.message || 'Password changed', 'success');
                e.target.reset();
                pwdStrength.style.width = '0%';
            } else {
                toast(res.error || 'Failed to change password', 'error');
            }
        } catch (err) {
            toast('Error changing password', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Privacy Settings Toggle
    document.getElementById('publicDefaultToggle').addEventListener('change', async (e) => {
        const isChecked = e.target.checked;
        try {
            const res = await api('POST', '/api/profile.php', {
                preferences: { public_by_default: isChecked }
            });
            if (res && res.success) {
                toast('Privacy settings updated', 'success');
            } else {
                toast('Failed to update privacy settings', 'error');
                e.target.checked = !isChecked; // revert
            }
        } catch (err) {
            toast('Error updating settings', 'error');
            e.target.checked = !isChecked;
        }
    });

    // Delete Account
    document.getElementById('deleteAccountForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = e.target.elements['email_confirm'].value;
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        
        try {
            const res = await api('DELETE', '/api/profile.php', { email: email });
            if (res && res.success) {
                window.location.href = 'register.php?deleted=1';
            } else {
                toast(res.error || 'Verification failed', 'error');
                btn.disabled = false;
            }
        } catch (err) {
            toast('An error occurred', 'error');
            btn.disabled = false;
        }
    });

    // Animated Counters
    const counters = document.querySelectorAll('.stat-counter');
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const duration = 1000; 
        const increment = target / (duration / 20); 
        
        if (target === 0) return;
        
        let current = 0;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.innerText = target;
                clearInterval(timer);
            } else {
                counter.innerText = Math.ceil(current);
            }
        }, 20);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
