<?php
require_once __DIR__ . '/includes/auth.php';

$currentUserId = isLoggedIn() ? current_user_id() : null;

// Fetch posts
$postsStmt = $pdo->prepare('
    SELECT p.id, p.user_id, p.trip_id, p.title, p.content, p.likes_count, p.created_at,
           u.first_name, u.last_name, u.profile_photo,
           t.trip_name,
           CASE WHEN l.id IS NOT NULL THEN TRUE ELSE FALSE END AS user_liked
    FROM community_posts p
    JOIN users u ON u.id = p.user_id
    LEFT JOIN trips t ON t.id = p.trip_id
    LEFT JOIN community_likes l ON l.post_id = p.id AND l.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 50
');
$postsStmt->execute([$currentUserId ?: 0]);
$posts = $postsStmt->fetchAll();

// Fetch user's trips for linking to post
$userTrips = [];
if ($currentUserId) {
    $tStmt = $pdo->prepare('SELECT id, trip_name FROM trips WHERE user_id = ? ORDER BY created_at DESC');
    $tStmt->execute([$currentUserId]);
    $userTrips = $tStmt->fetchAll();
}

$extraHead = '<script src="' . SITE_URL . '/assets/js/community.js" defer></script>';
$pageTitle = 'Community Travel Stories — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <h1 class="h2 fw-bold mb-1"><i class="fa-solid fa-users-viewfinder text-primary me-2"></i>Community & Traveler Hub</h1>
            <p class="text-muted mb-0">Share travel experiences, ask for recommendations, and discover public trip plans from fellow travelers.</p>
        </div>
        <?php if ($currentUserId): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
                <i class="fa-solid fa-plus me-1"></i> Share Story / Post
            </button>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Login to Post
            </a>
        <?php endif; ?>
    </div>

    <!-- Posts Stream -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if (empty($posts)): ?>
                <div class="text-center py-5 bg-light rounded border">
                    <i class="fa-solid fa-comments fa-3x text-muted mb-3"></i>
                    <h5>No community posts yet</h5>
                    <p class="text-muted">Be the first traveler to share your journey or ask a question!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $initial = strtoupper(substr($post['first_name'] ?: 'T', 0, 1));
                    $userPhoto = !empty($post['profile_photo']) ? SITE_URL . '/assets/uploads/profiles/' . htmlspecialchars($post['profile_photo']) : null;
                    ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                                    <?php if ($userPhoto): ?>
                                        <img src="<?= $userPhoto ?>" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;" alt="Avatar">
                                    <?php else: ?>
                                        <?= $initial ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></h6>
                                    <span class="small text-muted"><?= date('M j, Y • g:i a', strtotime($post['created_at'])) ?></span>
                                </div>
                            </div>
                            <?php if ($post['trip_name']): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                    <i class="fa-solid fa-suitcase me-1"></i><?= htmlspecialchars($post['trip_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3">
                            <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($post['title']) ?></h5>
                            <p class="text-secondary mb-0" style="white-space: pre-line;"><?= htmlspecialchars($post['content']) ?></p>
                        </div>
                        <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                            <?php if ($currentUserId): ?>
                                <button class="btn btn-sm <?= $post['user_liked'] ? 'btn-primary' : 'btn-outline-primary' ?> like-post-btn" data-post-id="<?= $post['id'] ?>">
                                    <i class="fa-solid fa-thumbs-up me-1"></i> Like (<span class="like-count"><?= $post['likes_count'] ?></span>)
                                </button>
                            <?php else: ?>
                                <span class="small text-muted"><i class="fa-solid fa-thumbs-up me-1"></i><?= $post['likes_count'] ?> likes</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Create Community Post -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Create Community Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPostForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="postTitle" class="form-label fw-semibold small">Post Title *</label>
                        <input type="text" class="form-control" id="postTitle" placeholder="e.g. 3 Days in Tokyo: Best Ramen & Hidden Gems" required>
                    </div>
                    <?php if (!empty($userTrips)): ?>
                        <div class="mb-3">
                            <label for="postTripId" class="form-label fw-semibold small">Link to Your Trip (Optional)</label>
                            <select class="form-select" id="postTripId">
                                <option value="">No linked trip</option>
                                <?php foreach ($userTrips as $ut): ?>
                                    <option value="<?= $ut['id'] ?>"><?= htmlspecialchars($ut['trip_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="postTripId" value="">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="postContent" class="form-label fw-semibold small">Story / Content *</label>
                        <textarea class="form-control" id="postContent" rows="5" placeholder="Share your experience, tip, or question..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-info btn-sm me-auto" id="btnAutoFillStory"><i class="fa-solid fa-magic me-1"></i> Auto-Fill Story</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> Publish Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
