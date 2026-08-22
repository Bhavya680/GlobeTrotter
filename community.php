<?php
require_once __DIR__ . '/includes/auth.php';
$isLoggedIn = is_logged_in();
$userId = current_user_id();

// Fetch user's public trips for the dropdown
$myTrips = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT id, trip_name FROM trips WHERE user_id = ? AND visibility = 'public' ORDER BY trip_name ASC");
    $stmt->execute([$userId]);
    $myTrips = $stmt->fetchAll();
}

$pageTitle = 'Community — GlobeTrotter';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.community-header {
    background: linear-gradient(135deg, var(--bs-primary), #0056b3);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 1rem 1rem;
}
.post-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.post-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.post-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    background-color: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}
.post-content-clamped {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tag-pill {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    border-radius: 1rem;
    padding: 0.2rem 0.6rem;
    font-size: 0.75rem;
    margin-right: 0.3rem;
    margin-bottom: 0.3rem;
}
.tag-input-pill {
    display: inline-flex;
    align-items: center;
    background: var(--bs-primary);
    color: white;
    border-radius: 1rem;
    padding: 0.2rem 0.6rem;
    font-size: 0.8rem;
    margin: 0.2rem;
}
.tag-input-pill i {
    cursor: pointer;
    margin-left: 5px;
}
#tagsContainer {
    min-height: 40px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.375rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    background: #fff;
    cursor: text;
}
#tagInput {
    border: none;
    outline: none;
    flex: 1;
    min-width: 100px;
}
.btn-like {
    color: #6c757d;
    transition: color 0.2s;
}
.btn-like.liked, .btn-like:hover {
    color: #dc3545;
}
.comments-section {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
}
.comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: var(--bs-secondary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
}
.loading-spinner {
    display: none;
    text-align: center;
    padding: 2rem 0;
}
.nav-pills .nav-link.active {
    background-color: white;
    color: var(--bs-primary);
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.nav-pills .nav-link {
    color: rgba(255,255,255,0.8);
}
</style>

<!-- Header -->
<div class="community-header">
    <div class="container text-center">
        <h1 class="fw-bold mb-3"><i class="fa-solid fa-earth-americas me-2"></i>GlobeTrotter Community</h1>
        <p class="lead mb-4">Discover inspiring trips, read travel stories, and share your own experiences with fellow travelers.</p>
        
        <ul class="nav nav-pills justify-content-center" id="communityTabs">
            <li class="nav-item">
                <a class="nav-link active px-4 rounded-pill me-2" href="#" id="tabFeed">Community Feed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-4 rounded-pill" href="#" id="tabMyPosts">My Posts</a>
            </li>
        </ul>
    </div>
</div>

<div class="container pb-5">
    <!-- Utility Bar -->
    <div class="row mb-4 align-items-center">
        <div class="col-lg-4 mb-2 mb-lg-0">
            <div class="input-group shadow-sm rounded">
                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search posts, trips, cities...">
            </div>
        </div>
        <div class="col-lg-6 mb-2 mb-lg-0 d-flex gap-2 justify-content-lg-end">
            <select class="form-select shadow-sm" id="sortSelect" style="max-width: 150px;">
                <option value="recent">Most Recent</option>
                <option value="liked">Most Liked</option>
                <option value="commented">Most Commented</option>
            </select>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle shadow-sm bg-white" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu p-3 shadow" style="min-width: 250px;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="filterLiked">
                        <label class="form-check-label" for="filterLiked">Liked by me</label>
                    </div>
                    <hr>
                    <label class="form-label small fw-bold">Tags</label>
                    <input type="text" class="form-control form-control-sm mb-2" id="filterTags" placeholder="e.g. Budget, Asia (comma separated)">
                    <button class="btn btn-sm btn-primary w-100" id="applyFiltersBtn">Apply Filters</button>
                </div>
            </div>
            <select class="form-select shadow-sm d-none" id="groupSelect" style="max-width: 150px;">
                <option value="">No Grouping</option>
                <option value="trip">By Trip</option>
            </select>
        </div>
        <div class="col-lg-2 text-lg-end">
            <button class="btn btn-primary shadow-sm w-100" id="btnCompose"><i class="fa-solid fa-pen-to-square me-2"></i>Share</button>
        </div>
    </div>

    <!-- Feed Container -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div id="resultsInfo" class="text-muted small mb-3"></div>
            <div id="feedContainer" class="d-flex flex-column gap-3">
                <!-- Posts rendered here -->
            </div>
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div id="loadMoreTrigger" style="height: 20px;"></div>
        </div>
    </div>
</div>

<!-- Compose Modal -->
<div class="modal fade" id="composeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="composeModalTitle">Share Your Experience</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="composeForm">
                    <input type="hidden" id="editPostId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="postTitle" required minlength="3" maxlength="100" placeholder="An amazing week in...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Link to a Public Trip (Optional)</label>
                        <select class="form-select" id="postTripId">
                            <option value="">None</option>
                            <?php foreach ($myTrips as $trip): ?>
                                <option value="<?= $trip['id'] ?>"><?= htmlspecialchars($trip['trip_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only public trips can be linked. You can change visibility in Itinerary Builder.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Your Story <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="postContent" rows="6" required minlength="20" maxlength="2000" placeholder="Tell us about your experience..."></textarea>
                        <div class="d-flex justify-content-between form-text">
                            <small>Minimum 20 characters.</small>
                            <small id="charCount">0 / 2000</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Tags</label>
                        <div id="tagsContainer">
                            <!-- Pills go here -->
                            <input type="text" id="tagInput" placeholder="Add tag (press Enter)">
                        </div>
                        <div class="form-text">Press Enter or comma to add a tag.</div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSubmitPost">Post <i class="fa-solid fa-paper-plane ms-1"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="postTemplate">
    <div class="card post-card mb-2" data-post-id="">
        <div class="card-body p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center">
                    <div class="post-avatar me-3"></div>
                    <div>
                        <h6 class="fw-bold mb-0 post-author"></h6>
                        <small class="text-muted post-date"></small>
                    </div>
                </div>
                <div class="dropdown post-options d-none">
                    <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item btn-edit" href="#"><i class="fa-solid fa-pen text-muted me-2"></i>Edit</a></li>
                        <li><a class="dropdown-item text-danger btn-delete" href="#"><i class="fa-solid fa-trash me-2"></i>Delete</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Linked Trip -->
            <div class="post-trip-link d-none mb-3">
                <a href="#" class="badge bg-light text-primary text-decoration-none border border-primary-subtle">
                    <i class="fa-solid fa-link me-1"></i><span class="trip-name"></span>
                </a>
            </div>

            <!-- Content -->
            <h5 class="fw-bold post-title"></h5>
            <div class="post-content post-content-clamped mb-2" style="white-space: pre-wrap;"></div>
            <a href="#" class="text-decoration-none small fw-bold btn-read-more mb-3 d-inline-block">Read more</a>

            <!-- Tags -->
            <div class="post-tags mb-3"></div>

            <hr class="text-muted opacity-25">

            <!-- Actions -->
            <div class="d-flex gap-4">
                <button class="btn btn-link text-decoration-none p-0 btn-like d-flex align-items-center">
                    <i class="fa-regular fa-heart me-2 fs-5 heart-icon"></i> 
                    <span class="likes-count fw-bold"></span>
                </button>
                <button class="btn btn-link text-decoration-none p-0 text-muted d-flex align-items-center btn-comment-toggle">
                    <i class="fa-regular fa-comment me-2 fs-5"></i>
                    <span class="comments-count fw-bold"></span>
                </button>
                <button class="btn btn-link text-decoration-none p-0 text-muted ms-auto d-flex align-items-center btn-share">
                    <i class="fa-solid fa-share-nodes me-1 fs-5"></i>
                </button>
            </div>
            
            <!-- Comments Section (Hidden by default) -->
            <div class="comments-section d-none">
                <div class="comments-list mb-3 d-flex flex-column gap-3"></div>
                <form class="comment-form d-flex gap-2">
                    <input type="text" class="form-control rounded-pill comment-input" placeholder="Write a comment..." required>
                    <button type="submit" class="btn btn-primary rounded-circle"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</template>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="actionToast" class="toast align-items-center text-white bg-dark border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
    const currentUserId = <?= json_encode($userId) ?>;
    
    // State
    let currentPage = 1;
    let hasMore = true;
    let isLoading = false;
    let myPostsMode = false;
    let currentTags = []; // For compose modal
    
    // DOM
    const feedContainer = document.getElementById('feedContainer');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultsInfo = document.getElementById('resultsInfo');
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const btnCompose = document.getElementById('btnCompose');
    const tabFeed = document.getElementById('tabFeed');
    const tabMyPosts = document.getElementById('tabMyPosts');
    const composeModalEl = document.getElementById('composeModal');
    const composeModal = new bootstrap.Modal(composeModalEl);
    const toast = new bootstrap.Toast(document.getElementById('actionToast'));
    
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const filterLiked = document.getElementById('filterLiked');
    const filterTags = document.getElementById('filterTags');
    
    // Fix for aria-hidden focus warning on modal close
    composeModalEl.addEventListener('hide.bs.modal', () => {
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });
    
    function showToast(msg) {
        document.getElementById('toastMsg').textContent = msg;
        toast.show();
    }
    
    function requireLoginAction() {
        if (!isLoggedIn) {
            window.location.href = 'login.php';
            return false;
        }
        return true;
    }

    async function fetchPosts(reset = false) {
        if (isLoading || (!hasMore && !reset)) return;
        isLoading = true;
        
        if (reset) {
            currentPage = 1;
            feedContainer.innerHTML = '';
            resultsInfo.textContent = '';
        }
        
        loadingSpinner.style.display = 'block';
        
        const params = new URLSearchParams({
            page: currentPage,
            search: searchInput.value,
            sort: sortSelect.value,
            liked: filterLiked.checked,
            tags: filterTags.value,
            my_posts: myPostsMode
        });
        
        try {
            const res = await fetch(`api/community.php?${params.toString()}`);
            const data = await res.json();
            
            if (data.status === 'success') {
                data.data.posts.forEach(post => {
                    feedContainer.appendChild(createPostElement(post));
                });
                
                hasMore = data.data.has_more;
                currentPage++;
                
                if (reset) {
                    resultsInfo.textContent = `Showing ${data.data.total} result(s)`;
                    if (data.data.posts.length === 0) {
                        feedContainer.innerHTML = '<div class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fs-1 mb-3 d-block"></i>No posts found.</div>';
                    }
                }
            }
        } catch (e) {
            console.error('Fetch error:', e);
        } finally {
            isLoading = false;
            loadingSpinner.style.display = 'none';
        }
    }

    function getInitials(first, last) {
        return (first.charAt(0) + last.charAt(0)).toUpperCase();
    }

    function createPostElement(post) {
        const tpl = document.getElementById('postTemplate').content.cloneNode(true);
        const card = tpl.querySelector('.post-card');
        card.dataset.postId = post.id;
        
        // Author
        const avatar = card.querySelector('.post-avatar');
        if (post.profile_photo) {
            avatar.style.backgroundImage = `url(${post.profile_photo})`;
            avatar.style.backgroundSize = 'cover';
        } else {
            avatar.textContent = getInitials(post.first_name, post.last_name);
        }
        card.querySelector('.post-author').textContent = `${post.first_name} ${post.last_name}`;
        
        // Date
        const d = new Date(post.created_at);
        card.querySelector('.post-date').textContent = d.toLocaleDateString(undefined, {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
        
        // Trip Link
        if (post.trip_id) {
            const linkArea = card.querySelector('.post-trip-link');
            linkArea.classList.remove('d-none');
            linkArea.querySelector('.trip-name').textContent = post.trip_name;
            if (post.share_slug) {
                linkArea.querySelector('a').href = `public-itinerary.php?slug=${post.share_slug}`;
            }
        }
        
        // Options (Edit/Delete)
        if (isLoggedIn && post.user_id === currentUserId) {
            card.querySelector('.post-options').classList.remove('d-none');
            
            card.querySelector('.btn-delete').addEventListener('click', async (e) => {
                e.preventDefault();
                if(confirm('Are you sure you want to delete this post?')) {
                    const res = await fetch(`api/community.php?post_id=${post.id}`, { method: 'DELETE' });
                    if (res.ok) {
                        card.remove();
                        showToast('Post deleted');
                    }
                }
            });
            
            card.querySelector('.btn-edit').addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('editPostId').value = post.id;
                document.getElementById('postTitle').value = post.title;
                document.getElementById('postContent').value = post.content;
                document.getElementById('postTripId').value = post.trip_id || '';
                currentTags = post.tags || [];
                renderTags();
                updateCharCount();
                document.getElementById('composeModalTitle').textContent = 'Edit Post';
                composeModal.show();
            });
        }
        
        // Content
        card.querySelector('.post-title').textContent = post.title;
        const contentEl = card.querySelector('.post-content');
        contentEl.textContent = post.content;
        
        const readMoreBtn = card.querySelector('.btn-read-more');
        // A simple check if text is long to show 'Read more'
        if (post.content.split('\n').length <= 3 && post.content.length < 250) {
            readMoreBtn.classList.add('d-none');
            contentEl.classList.remove('post-content-clamped');
        } else {
            readMoreBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (contentEl.classList.contains('post-content-clamped')) {
                    contentEl.classList.remove('post-content-clamped');
                    readMoreBtn.textContent = 'Show less';
                } else {
                    contentEl.classList.add('post-content-clamped');
                    readMoreBtn.textContent = 'Read more';
                }
            });
        }

        // Tags
        const tagsContainer = card.querySelector('.post-tags');
        if (post.tags && post.tags.length > 0) {
            post.tags.forEach(tag => {
                const sp = document.createElement('span');
                sp.className = 'tag-pill';
                sp.textContent = tag;
                tagsContainer.appendChild(sp);
            });
        }
        
        // Like Button
        const likeBtn = card.querySelector('.btn-like');
        const heartIcon = likeBtn.querySelector('.heart-icon');
        const likesCount = card.querySelector('.likes-count');
        likesCount.textContent = post.likes_count || '0';
        
        if (post.is_liked) {
            likeBtn.classList.add('liked');
            heartIcon.classList.remove('fa-regular');
            heartIcon.classList.add('fa-solid');
        }
        
        likeBtn.addEventListener('click', async () => {
            if (!requireLoginAction()) return;
            
            // Optimistic UI
            const isCurrentlyLiked = likeBtn.classList.contains('liked');
            let currentCount = parseInt(likesCount.textContent);
            
            if (isCurrentlyLiked) {
                likeBtn.classList.remove('liked');
                heartIcon.classList.remove('fa-solid');
                heartIcon.classList.add('fa-regular');
                likesCount.textContent = Math.max(0, currentCount - 1);
            } else {
                likeBtn.classList.add('liked');
                heartIcon.classList.remove('fa-regular');
                heartIcon.classList.add('fa-solid');
                likesCount.textContent = currentCount + 1;
            }
            
            // API Call
            try {
                const res = await fetch('api/community.php?action=like', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({post_id: post.id})
                });
                const data = await res.json();
                if (data.status !== 'success') throw new Error();
                // Sync exact count from server
                likesCount.textContent = data.data.likes_count;
            } catch (e) {
                // Rollback on fail
                if (isCurrentlyLiked) {
                    likeBtn.classList.add('liked');
                    heartIcon.classList.add('fa-solid');
                    heartIcon.classList.remove('fa-regular');
                    likesCount.textContent = currentCount;
                } else {
                    likeBtn.classList.remove('liked');
                    heartIcon.classList.remove('fa-solid');
                    heartIcon.classList.add('fa-regular');
                    likesCount.textContent = currentCount;
                }
                showToast('Failed to update like');
            }
        });
        
        // Comments toggle
        const commentToggle = card.querySelector('.btn-comment-toggle');
        const commentCount = card.querySelector('.comments-count');
        commentCount.textContent = post.comments_count || '0';
        const commentsSec = card.querySelector('.comments-section');
        const commentsList = card.querySelector('.comments-list');
        const commentForm = card.querySelector('.comment-form');
        let commentsLoaded = false;
        
        commentToggle.addEventListener('click', async () => {
            commentsSec.classList.toggle('d-none');
            if (!commentsSec.classList.contains('d-none') && !commentsLoaded) {
                // Fetch comments
                commentsList.innerHTML = '<div class="text-center small text-muted">Loading...</div>';
                const res = await fetch(`api/community.php?action=comments&post_id=${post.id}`);
                const data = await res.json();
                commentsList.innerHTML = '';
                if(data.data.length === 0) {
                    commentsList.innerHTML = '<div class="small text-muted">No comments yet. Be the first!</div>';
                } else {
                    data.data.forEach(c => appendComment(commentsList, c));
                }
                commentsLoaded = true;
            }
        });
        
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!requireLoginAction()) return;
            
            const input = commentForm.querySelector('input');
            const val = input.value.trim();
            if(!val) return;
            
            const res = await fetch('api/community.php?action=comment', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({post_id: post.id, content: val})
            });
            const data = await res.json();
            
            if(data.status === 'success') {
                input.value = '';
                // Optimistically add comment to UI (minimal mock)
                appendComment(commentsList, {
                    content: val,
                    first_name: 'You',
                    last_name: '',
                    created_at: new Date().toISOString()
                });
                commentCount.textContent = parseInt(commentCount.textContent) + 1;
                // Remove empty msg if present
                if (commentsList.innerHTML.includes('No comments')) commentsList.innerHTML = '';
            } else {
                showToast(data.message || 'Error adding comment');
            }
        });
        
        // Share
        card.querySelector('.btn-share').addEventListener('click', () => {
            const url = window.location.origin + window.location.pathname + `?post_id=${post.id}`;
            navigator.clipboard.writeText(url);
            showToast('Link copied to clipboard!');
        });
        
        return card;
    }

    function appendComment(container, c) {
        const div = document.createElement('div');
        div.className = 'd-flex';
        
        const init = getInitials(c.first_name||'', c.last_name||'');
        let avatarHtml = `<div class="comment-avatar me-2 flex-shrink-0">${init}</div>`;
        if (c.profile_photo) {
            avatarHtml = `<div class="comment-avatar me-2 flex-shrink-0" style="background-image:url(${c.profile_photo}); background-size:cover;"></div>`;
        }
        
        const d = new Date(c.created_at);
        const dStr = d.toLocaleDateString(undefined, {month:'short', day:'numeric'});
        
        div.innerHTML = `
            ${avatarHtml}
            <div class="bg-white p-2 rounded w-100 border">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="small">${c.first_name} ${c.last_name}</strong>
                    <span class="text-muted" style="font-size:0.7rem;">${dStr}</span>
                </div>
                <div class="small" style="white-space: pre-wrap;">${c.content}</div>
            </div>
        `;
        // Remove 'no comments' text if exists
        const noC = container.querySelector('.text-muted');
        if(noC && noC.textContent.includes('No comments')) noC.remove();
        
        container.appendChild(div);
    }

    // Compose Modal / Tag Input
    const tagInput = document.getElementById('tagInput');
    const tagsContainer = document.getElementById('tagsContainer');
    
    function renderTags() {
        // Remove existing pills
        document.querySelectorAll('.tag-input-pill').forEach(e => e.remove());
        currentTags.forEach((tag, idx) => {
            const pill = document.createElement('span');
            pill.className = 'tag-input-pill';
            pill.innerHTML = `${tag} <i class="fa-solid fa-xmark" data-idx="${idx}"></i>`;
            tagsContainer.insertBefore(pill, tagInput);
        });
        
        document.querySelectorAll('.tag-input-pill i').forEach(el => {
            el.addEventListener('click', (e) => {
                currentTags.splice(e.target.dataset.idx, 1);
                renderTags();
            });
        });
    }

    tagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const val = tagInput.value.trim().replace(/,/g, '');
            if (val && !currentTags.includes(val) && currentTags.length < 5) {
                currentTags.push(val);
                tagInput.value = '';
                renderTags();
            }
        } else if (e.key === 'Backspace' && tagInput.value === '' && currentTags.length > 0) {
            currentTags.pop();
            renderTags();
        }
    });
    
    tagsContainer.addEventListener('click', () => tagInput.focus());

    // Compose Form Submit
    const composeForm = document.getElementById('composeForm');
    const contentTextarea = document.getElementById('postContent');
    const charCount = document.getElementById('charCount');
    
    function updateCharCount() {
        const len = contentTextarea.value.length;
        charCount.textContent = `${len} / 2000`;
        charCount.className = len < 20 || len > 2000 ? 'text-danger' : 'text-muted';
    }
    contentTextarea.addEventListener('input', updateCharCount);

    btnCompose.addEventListener('click', () => {
        if (requireLoginAction()) {
            document.getElementById('editPostId').value = '';
            composeForm.reset();
            currentTags = [];
            renderTags();
            updateCharCount();
            document.getElementById('composeModalTitle').textContent = 'Share Your Experience';
            composeModal.show();
        }
    });

    composeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('editPostId').value;
        const method = id ? 'PUT' : 'POST';
        
        const payload = {
            post_id: id,
            title: document.getElementById('postTitle').value,
            content: document.getElementById('postContent').value,
            trip_id: document.getElementById('postTripId').value,
            tags: currentTags
        };
        
        const btn = document.getElementById('btnSubmitPost');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Posting...';
        
        try {
            const res = await fetch('api/community.php', {
                method: method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                composeModal.hide();
                showToast(id ? 'Post updated successfully' : 'Post created successfully');
                fetchPosts(true); // refresh
            } else {
                showToast(data.message);
            }
        } catch(err) {
            showToast('Network error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Post <i class="fa-solid fa-paper-plane ms-1"></i>';
        }
    });

    // Filtering, Search, Sorting
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchPosts(true), 400);
    });
    
    sortSelect.addEventListener('change', () => fetchPosts(true));
    applyFiltersBtn.addEventListener('click', () => {
        // hide dropdown
        bootstrap.Dropdown.getInstance(applyFiltersBtn.closest('.dropdown').querySelector('.dropdown-toggle')).hide();
        fetchPosts(true);
    });

    // Tabs
    tabFeed.addEventListener('click', (e) => {
        e.preventDefault();
        myPostsMode = false;
        tabFeed.classList.add('active');
        tabMyPosts.classList.remove('active');
        btnCompose.classList.remove('d-none');
        fetchPosts(true);
    });
    
    tabMyPosts.addEventListener('click', (e) => {
        e.preventDefault();
        if (!requireLoginAction()) return;
        myPostsMode = true;
        tabMyPosts.classList.add('active');
        tabFeed.classList.remove('active');
        btnCompose.classList.add('d-none'); // Hide compose on my posts tab to save space or leave it, wireframe doesn't forbid it
        fetchPosts(true);
    });

    // Infinite Scroll
    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) {
            fetchPosts();
        }
    }, { rootMargin: '100px' });
    observer.observe(document.getElementById('loadMoreTrigger'));

    // Initial load will be triggered by intersection observer if loadMoreTrigger is in view
    // But just in case:
    fetchPosts(true);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
