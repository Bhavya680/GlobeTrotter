<?php
require_once __DIR__ . '/../../includes/auth.php';

$errorCode = isset($_GET['code']) ? (int) $_GET['code'] : 500;
$errorMessage = isset($_GET['message']) ? clean_str($_GET['message']) : 'An unexpected error occurred while processing your travel request.';

if ($errorCode >= 400 && $errorCode < 600) {
    http_response_code($errorCode);
}

$pageTitle = "Error $errorCode — GlobeTrotter";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5 text-center" style="max-width: 720px; min-height: 60vh; display: flex; align-items: center; justify-content: center;">
    <div class="card border-0 shadow-lg rounded-4 p-5 bg-white w-100">
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-3" style="width: 80px; height: 80px; font-size: 2.25rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h1 class="display-4 fw-extrabold text-dark font-[Outfit] mb-1"><?= $errorCode ?></h1>
            <h4 class="fw-bold text-slate-800 mb-2">Something Went Wrong</h4>
            <p class="text-secondary mb-4 mx-auto" style="max-width: 480px;">
                <?= htmlspecialchars($errorMessage) ?>
            </p>
        </div>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>" class="btn btn-primary rounded-pill px-4 py-2.5 fw-semibold shadow-sm">
                <i class="fa-solid fa-house me-1.5"></i> <?= is_logged_in() ? 'Return to Dashboard' : 'Back to Home' ?>
            </a>
            <button onclick="window.history.back()" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-semibold">
                <i class="fa-solid fa-arrow-left me-1.5"></i> Go Back
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
