<?php
http_response_code(404);
require_once __DIR__ . '/includes/auth.php';

$pageTitle = '404 - Page Not Found | GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center" style="max-width: 720px; min-height: 60vh; display: flex; align-items: center; justify-content: center;">
    <div class="card border-0 shadow-lg rounded-4 p-5 bg-white w-100">
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 90px; height: 90px; font-size: 2.75rem;">
                <i class="fa-solid fa-compass"></i>
            </div>
            <h1 class="display-3 fw-extrabold text-dark font-[Outfit] mb-1">404</h1>
            <h3 class="fw-bold text-slate-800 mb-2">Lost in Transit?</h3>
            <p class="text-secondary mb-4 mx-auto" style="max-width: 460px;">
                The destination you are looking for doesn't exist, has been moved, or is taking a detour around the globe.
            </p>
        </div>

        <!-- Search Bar on 404 -->
        <form action="city-search.php" method="GET" class="mb-4 mx-auto w-100" style="max-width: 440px;">
            <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                <span class="input-group-text bg-white border-0 ps-3 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="q" class="form-control border-0 ps-1" placeholder="Search cities or activities...">
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Search</button>
            </div>
        </form>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>" class="btn btn-primary rounded-pill px-4 py-2.5 fw-semibold shadow-sm">
                <i class="fa-solid fa-house me-1.5"></i> <?= is_logged_in() ? 'Return to Dashboard' : 'Back to Home' ?>
            </a>
            <a href="city-search.php" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-semibold">
                <i class="fa-solid fa-earth-americas me-1.5"></i> Explore Cities
            </a>
            <a href="community.php" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-semibold">
                <i class="fa-solid fa-comments me-1.5"></i> Community Stories
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
