<?php
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// For GET requests, we allow non-logged-in users (public view)
$userId = is_logged_in() ? $_SESSION['user_id'] : null;

if ($method === 'GET') {
    if ($action === 'comments') {
        get_comments($pdo);
    } else {
        get_feed($pdo, $userId);
    }
} else {
    // All POST/PUT/DELETE require login and CSRF validation
    $userId = require_login();
    require_csrf(); // Validate CSRF for POST/PUT/DELETE
    
    if ($method === 'POST') {
        if ($action === 'like') {
            toggle_like($pdo, $userId);
        } elseif ($action === 'comment') {
            add_comment($pdo, $userId);
        } else {
            create_post($pdo, $userId);
        }
    } elseif ($method === 'PUT') {
        update_post($pdo, $userId);
    } elseif ($method === 'DELETE') {
        delete_post($pdo, $userId);
    } else {
        json_error('Method not allowed', 405);
    }
}

function get_feed(PDO $pdo, ?int $userId): void
{
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? clean_str($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
    $group = isset($_GET['group']) ? $_GET['group'] : '';
    $tags = isset($_GET['tags']) ? explode(',', clean_str($_GET['tags'])) : [];
    $myPosts = isset($_GET['my_posts']) && $_GET['my_posts'] === 'true';

    $where = [];
    $params = [];

    if ($myPosts && $userId) {
        $where[] = "p.user_id = ?";
        $params[] = $userId;
    }

    if ($search !== '') {
        $where[] = "(p.title ILIKE ? OR p.content ILIKE ? OR u.first_name ILIKE ? OR t.trip_name ILIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    if (!empty($tags) && $tags[0] !== '') {
        // Simple tag matching using JSON containment or text search
        // Since tags are JSON array of strings
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                // Check if the JSON array contains the tag
                $where[] = "p.tags::jsonb ? ?";
                $params[] = $tag;
            }
        }
    }

    // Liked by me filter
    if (isset($_GET['liked']) && $_GET['liked'] === 'true' && $userId) {
        $where[] = "EXISTS (SELECT 1 FROM community_likes cl WHERE cl.post_id = p.id AND cl.user_id = ?)";
        $params[] = $userId;
    }

    // Date range filter
    if (!empty($_GET['date_from'])) {
        $where[] = "p.created_at >= ?";
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where[] = "p.created_at <= ?";
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $orderBy = "p.created_at DESC";
    if ($sort === 'liked') {
        $orderBy = "p.likes_count DESC, p.created_at DESC";
    } elseif ($sort === 'commented') {
        $orderBy = "comments_count DESC, p.created_at DESC";
    }

    // Grouping alters the query slightly or just sorts by the group field
    if ($group === 'trip') {
        $orderBy = "t.trip_name ASC NULLS LAST, " . $orderBy;
    } elseif ($group === 'city') {
        // To group by city we need to join cities through trip_stops... this is complex.
        // We will just order by trip name for now as a proxy.
        $orderBy = "t.trip_name ASC NULLS LAST, " . $orderBy;
    } elseif ($group === 'popularity') {
        $orderBy = "p.likes_count DESC, p.created_at DESC";
    }

    $sql = "
        SELECT p.id, p.title, p.content, p.likes_count, p.created_at, p.tags,
               u.id AS user_id, u.first_name, u.last_name, u.profile_photo,
               t.id AS trip_id, t.trip_name, t.share_slug,
               (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = p.id) AS comments_count,
               " . ($userId ? "(SELECT COUNT(*) FROM community_likes cl WHERE cl.post_id = p.id AND cl.user_id = $userId) > 0" : "false") . " AS is_liked
        FROM community_posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN trips t ON t.id = p.trip_id
        $whereClause
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    // Decode JSON tags
    foreach ($posts as &$post) {
        $post['tags'] = json_decode($post['tags'] ?? '[]', true);
        $post['is_liked'] = (bool)$post['is_liked'];
    }

    // Get total count for metadata
    $countSql = "SELECT COUNT(*) FROM community_posts p JOIN users u ON u.id = p.user_id LEFT JOIN trips t ON t.id = p.trip_id $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    json_success([
        'posts' => $posts,
        'total' => $total,
        'page' => $page,
        'has_more' => ($offset + $limit) < $total
    ]);
}

function create_post(PDO $pdo, int $userId): void
{
    $body = get_request_body();
    
    $title = clean_str($body['title'] ?? '');
    $content = trim(clean_str($body['content'] ?? ''));
    $tripId = !empty($body['trip_id']) ? (int)$body['trip_id'] : null;
    $tags = isset($body['tags']) && is_array($body['tags']) ? $body['tags'] : [];

    if (mb_strlen($title) < 3 || mb_strlen($title) > 100) {
        json_error("Title must be between 3 and 100 characters");
    }
    if (mb_strlen($content) < 20 || mb_strlen($content) > 2000) {
        json_error("Content must be between 20 and 2000 characters");
    }

    // Verify trip ownership if provided
    if ($tripId) {
        if (!user_owns_trip($pdo, $userId, $tripId)) {
            json_error("Invalid trip selected", 403);
        }
    }

    // Clean tags
    $cleanTags = array_values(array_filter(array_map('clean_str', $tags)));

    $stmt = $pdo->prepare("
        INSERT INTO community_posts (user_id, trip_id, title, content, tags) 
        VALUES (?, ?, ?, ?, ?) RETURNING id
    ");
    $stmt->execute([$userId, $tripId, $title, $content, json_encode($cleanTags)]);
    
    json_success(['id' => $stmt->fetchColumn(), 'message' => 'Post created successfully']);
}

function update_post(PDO $pdo, int $userId): void
{
    $body = get_request_body();
    $postId = (int)($body['post_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT user_id FROM community_posts WHERE id = ?");
    $stmt->execute([$postId]);
    if ($stmt->fetchColumn() !== $userId) {
        json_error("Unauthorized", 403);
    }

    $title = clean_str($body['title'] ?? '');
    $content = trim(clean_str($body['content'] ?? ''));
    $tripId = !empty($body['trip_id']) ? (int)$body['trip_id'] : null;
    $tags = isset($body['tags']) && is_array($body['tags']) ? $body['tags'] : [];

    if (mb_strlen($title) < 3 || mb_strlen($title) > 100) {
        json_error("Title must be between 3 and 100 characters");
    }
    if (mb_strlen($content) < 20 || mb_strlen($content) > 2000) {
        json_error("Content must be between 20 and 2000 characters");
    }

    if ($tripId && !user_owns_trip($pdo, $userId, $tripId)) {
        json_error("Invalid trip selected", 403);
    }

    $cleanTags = array_values(array_filter(array_map('clean_str', $tags)));

    $updateStmt = $pdo->prepare("
        UPDATE community_posts 
        SET title = ?, content = ?, trip_id = ?, tags = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$title, $content, $tripId, json_encode($cleanTags), $postId]);
    
    json_success(['message' => 'Post updated successfully']);
}

function delete_post(PDO $pdo, int $userId): void
{
    $postId = (int)($_GET['post_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT user_id FROM community_posts WHERE id = ?");
    $stmt->execute([$postId]);
    if ($stmt->fetchColumn() !== $userId) {
        json_error("Unauthorized", 403);
    }

    $pdo->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$postId]);
    json_success(['message' => 'Post deleted successfully']);
}

function toggle_like(PDO $pdo, int $userId): void
{
    $body = get_request_body();
    $postId = (int)($body['post_id'] ?? 0);

    // Check if post exists
    $stmt = $pdo->prepare("SELECT id FROM community_posts WHERE id = ?");
    $stmt->execute([$postId]);
    if (!$stmt->fetch()) {
        json_error("Post not found", 404);
    }

    $pdo->beginTransaction();
    try {
        // Check if already liked
        $checkStmt = $pdo->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
        $checkStmt->execute([$postId, $userId]);
        
        if ($checkStmt->fetch()) {
            // Unlike
            $pdo->prepare("DELETE FROM community_likes WHERE post_id = ? AND user_id = ?")->execute([$postId, $userId]);
            $pdo->prepare("UPDATE community_posts SET likes_count = likes_count - 1 WHERE id = ?")->execute([$postId]);
            $liked = false;
        } else {
            // Like
            $pdo->prepare("INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)")->execute([$postId, $userId]);
            $pdo->prepare("UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$postId]);
            $liked = true;
        }
        
        $pdo->commit();
        
        // Get new count
        $countStmt = $pdo->prepare("SELECT likes_count FROM community_posts WHERE id = ?");
        $countStmt->execute([$postId]);
        $newCount = $countStmt->fetchColumn();

        json_success(['liked' => $liked, 'likes_count' => $newCount]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_error("Error toggling like");
    }
}

function get_comments(PDO $pdo): void
{
    $postId = (int)($_GET['post_id'] ?? 0);
    
    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, u.first_name, u.last_name, u.profile_photo, u.id AS user_id
        FROM community_comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$postId]);
    json_success($stmt->fetchAll());
}

function add_comment(PDO $pdo, int $userId): void
{
    $body = get_request_body();
    $postId = (int)($body['post_id'] ?? 0);
    $content = trim(clean_str($body['content'] ?? ''));

    if ($content === '') {
        json_error("Comment cannot be empty");
    }

    $stmt = $pdo->prepare("SELECT id FROM community_posts WHERE id = ?");
    $stmt->execute([$postId]);
    if (!$stmt->fetch()) {
        json_error("Post not found", 404);
    }

    $insertStmt = $pdo->prepare("INSERT INTO community_comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $insertStmt->execute([$postId, $userId, $content]);

    json_success(['message' => 'Comment added successfully']);
}
