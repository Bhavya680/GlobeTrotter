<?php
require_once __DIR__ . '/includes/auth.php';

$userId = require_login_page();

$stmt = $pdo->prepare('
    SELECT id, first_name, last_name, email, phone, city, country, profile_photo, additional_info, language_pref, created_at
    FROM users WHERE id = ?
');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$savedStmt = $pdo->prepare('
    SELECT c.id, c.name, c.country, c.image_url, c.cost_index
    FROM saved_destinations sd
    JOIN cities c ON c.id = sd.city_id
    WHERE sd.user_id = ?
    ORDER BY sd.saved_at DESC
');
$savedStmt->execute([$userId]);
$savedDestinations = $savedStmt->fetchAll();

$userPhoto = !empty($user['profile_photo']) ? SITE_URL . '/assets/uploads/profiles/' . htmlspecialchars($user['profile_photo']) : null;

$pageTitle = 'User Profile & Settings — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Profile Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="mb-3 d-flex justify-content-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-2 shadow" style="width: 100px; height: 100px; overflow: hidden;">
                        <?php if ($userPhoto): ?>
                            <img src="<?= $userPhoto ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Profile Photo">
                        <?php else: ?>
                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                <p class="text-muted small mb-2"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($user['email']) ?></p>
                <?php if ($user['city'] || $user['country']): ?>
                    <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars(implode(', ', array_filter([$user['city'], $user['country']]))) ?></p>
                <?php endif; ?>
                <hr class="my-3">
                <div class="text-start">
                    <span class="small text-muted text-uppercase fw-bold d-block mb-1">Member Since</span>
                    <span class="small text-dark fw-semibold"><?= date('F Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Main Settings & Saved Destinations -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom fw-bold py-3">
                    <i class="fa-solid fa-user-gear me-2 text-primary"></i>Profile Settings
                </div>
                <div class="card-body p-4">
                    <form id="profileUpdateForm" enctype="multipart/form-data">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">First Name</label>
                                <input type="text" id="profFirstName" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Last Name</label>
                                <input type="text" id="profLastName" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address</label>
                                <input type="email" id="profEmail" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone Number</label>
                                <input type="tel" id="profPhone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?: '') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">City</label>
                                <input type="text" id="profCity" class="form-control" value="<?= htmlspecialchars($user['city'] ?: '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Country</label>
                                <input type="text" id="profCountry" class="form-control" value="<?= htmlspecialchars($user['country'] ?: '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Additional Information</label>
                            <textarea id="profInfo" class="form-control" rows="2"><?= htmlspecialchars($user['additional_info'] ?: '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Update Profile Photo</label>
                            <input type="file" id="profPhoto" class="form-control" accept="image/jpeg, image/png, image/webp">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Saved Destinations -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-heart me-2 text-danger"></i>Saved Destinations</span>
                    <span class="badge bg-light text-dark border"><?= count($savedDestinations) ?> cities</span>
                </div>
                <div class="card-body">
                    <?php if (empty($savedDestinations)): ?>
                        <p class="text-muted small mb-0">No saved destinations yet. Explore cities and click the heart icon to save them.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($savedDestinations as $sd): ?>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($sd['name']) ?></strong>
                                            <span class="small text-muted"><?= htmlspecialchars($sd['country']) ?> ($<?= $sd['cost_index'] ?> index)</span>
                                        </div>
                                        <a href="city-search.php?q=<?= urlencode($sd['name']) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-arrow-right"></i></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileUpdateForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('first_name', document.getElementById('profFirstName').value.trim());
        formData.append('last_name', document.getElementById('profLastName').value.trim());
        formData.append('email', document.getElementById('profEmail').value.trim());
        formData.append('phone', document.getElementById('profPhone').value.trim());
        formData.append('city', document.getElementById('profCity').value.trim());
        formData.append('country', document.getElementById('profCountry').value.trim());
        formData.append('additional_info', document.getElementById('profInfo').value.trim());

        const photoInput = document.getElementById('profPhoto');
        if (photoInput.files[0]) {
            formData.append('profile_photo', photoInput.files[0]);
        }

        try {
            const res = await api('POST', '/api/profile.php', formData);
            if (res && res.success) {
                toast('Profile updated successfully!', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toast(res.error || 'Failed to update profile', 'error');
            }
        } catch (err) {
            toast(err.message || 'Error updating profile', 'error');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
