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

    document.getElementById('btnAutoFillStory')?.addEventListener('click', function() {
        const sampleStories = [
            { title: 'Magical Sunset at Montmartre & Local Bistro Crawl', content: 'We started our evening at the Sacré-Cœur steps watching street musicians, followed by an incredible 3-course tasting menu in Le Marais. Highly recommend booking early!' },
            { title: 'Hidden Coffee Shops & Vintage Records in Tokyo', content: 'Explored the quiet alleyways of Shimokitazawa. Found incredible analog jazz cafes and vintage record stores. A must-visit side of Tokyo!' },
            { title: 'Top 5 Tips for First-Time Coastal Hikers in Amalfi', content: 'Take the early morning bus to avoid crowds on Path of the Gods. Carry plenty of water and wear trail shoes with good grip!' }
        ];
        const choice = sampleStories[Math.floor(Math.random() * sampleStories.length)];
        const titleEl = document.getElementById('postTitle');
        const contentEl = document.getElementById('postContent');
        const tripEl = document.getElementById('postTripId');
        if (titleEl) titleEl.value = choice.title;
        if (contentEl) contentEl.value = choice.content;
        if (tripEl && tripEl.options && tripEl.options.length > 1) {
            tripEl.selectedIndex = 1;
        }
        if (typeof toast === 'function') {
            toast('Story details auto-filled!', 'success');
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
