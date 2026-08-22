/**
 * GlobeTrotter - Community JS Engine
 */

document.addEventListener('DOMContentLoaded', function() {
    initCreatePostForm();
    initLikeButtons();
});

function initCreatePostForm() {
    const form = document.getElementById('createPostForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const title = document.getElementById('postTitle').value.trim();
        const content = document.getElementById('postContent').value.trim();
        const tripId = document.getElementById('postTripId').value;

        if (!title || !content) {
            toast('Please enter a title and content', 'error');
            return;
        }

        try {
            const res = await api('POST', '/api/community.php?action=create_post', {
                title: title,
                content: content,
                trip_id: tripId || null
            });

            if (res && res.success) {
                toast('Post created successfully!', 'success');
                location.reload();
            } else {
                toast(res.error || 'Failed to create post', 'error');
            }
        } catch (err) {
            toast(err.message || 'Error creating post', 'error');
        }
    });
}

function initLikeButtons() {
    document.querySelectorAll('.like-post-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const postId = btn.dataset.postId;
            try {
                const res = await api('POST', '/api/community.php?action=like', { post_id: postId });
                if (res && res.success) {
                    const countSpan = btn.querySelector('.like-count');
                    let current = parseInt(countSpan.textContent || '0');
                    if (res.data.liked) {
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-primary');
                        countSpan.textContent = current + 1;
                        toast('Liked post!', 'success');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                        countSpan.textContent = Math.max(0, current - 1);
                        toast('Unliked post', 'info');
                    }
                }
            } catch (err) {
                toast(err.message || 'Error liking post', 'error');
            }
        });
    });
}
