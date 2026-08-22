<?php
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = clean_str($_GET['action'] ?? '');

switch ($method) {
    case 'GET':
        if ($action === 'posts') {
            get_community_posts($pdo);
        } else {
            $slug = clean_str($_GET['slug'] ?? '');
            if ($slug !== '') {
                get_public_trip($pdo, $slug);
            } else {
                get_community_posts($pdo);
            }
        }
        break;
    case 'POST':
        if ($action === 'copy') {
            copy_trip($pdo);
        } elseif ($action === 'create_post') {
            create_post($pdo);
        } elseif ($action === 'like') {
            toggle_like($pdo);
        } else {
            json_error('Unknown action', 400);
        }
        break;
    default:
        json_error('Method not allowed', 405);
}

function get_community_posts(PDO $pdo): void {
    $currentUserId = current_user_id();
    $stmt = $pdo->prepare('
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
    $stmt->execute([$currentUserId ?: 0]);
    json_success($stmt->fetchAll());
}

function create_post(PDO $pdo): void {
    $userId = require_login();
    $body = get_request_body();

    $title = clean_str($body['title'] ?? '');
    $content = clean_str($body['content'] ?? '');
    $tripId = !empty($body['trip_id']) ? (int)$body['trip_id'] : null;

    if ($title === '' || $content === '') {
        json_error('Title and content are required');
    }

    $stmt = $pdo->prepare('
        INSERT INTO community_posts (user_id, trip_id, title, content)
        VALUES (?, ?, ?, ?)
        RETURNING id
    ');
    $stmt->execute([$userId, $tripId, $title, $content]);
    $newId = (int)$stmt->fetchColumn();

    json_success(['id' => $newId, 'message' => 'Post created successfully'], 201);
}

function toggle_like(PDO $pdo): void {
    $userId = require_login();
    $body = get_request_body();

    $postId = (int)($body['post_id'] ?? $_GET['post_id'] ?? 0);
    if (!$postId) json_error('post_id is required');

    $check = $pdo->prepare('SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?');
    $check->execute([$postId, $userId]);
    $like = $check->fetch();

    if ($like) {
        $pdo->prepare('DELETE FROM community_likes WHERE id = ?')->execute([$like['id']]);
        $pdo->prepare('UPDATE community_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?')->execute([$postId]);
        json_success(['liked' => false]);
    } else {
        $pdo->prepare('INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)')->execute([$postId, $userId]);
        $pdo->prepare('UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?')->execute([$postId]);
        json_success(['liked' => true]);
    }
}

function get_public_trip(PDO $pdo, string $slug): void {
    $stmt = $pdo->prepare('
        SELECT t.id, t.trip_name AS name, t.trip_name, t.start_date, t.end_date, t.description, t.cover_photo,
               u.first_name, u.last_name, (u.first_name || \' \' || u.last_name) AS owner_name
        FROM trips t
        JOIN users u ON u.id = t.user_id
        WHERE t.share_slug = ? AND t.visibility = \'public\'
    ');
    $stmt->execute([$slug]);
    $trip = $stmt->fetch();

    if (!$trip) {
        json_error('This trip is not public or does not exist', 404);
    }

    $stopsStmt = $pdo->prepare('
        SELECT s.id, s.city_id, s.arrival_date AS start_date, s.departure_date AS end_date, s.order_index AS sort_order,
               c.name AS city_name, c.country AS city_country
        FROM trip_stops s
        JOIN cities c ON c.id = s.city_id
        WHERE s.trip_id = ?
        ORDER BY s.order_index ASC
    ');
    $stopsStmt->execute([$trip['id']]);
    $stops = $stopsStmt->fetchAll();

    foreach ($stops as &$stop) {
        $actStmt = $pdo->prepare('
            SELECT a.name, a.category, a.duration_hours, sa.scheduled_date, sa.scheduled_time
            FROM trip_activities sa
            JOIN activities a ON a.id = sa.activity_id
            WHERE sa.trip_stop_id = ?
            ORDER BY sa.scheduled_date, sa.scheduled_time NULLS LAST
        ');
        $actStmt->execute([$stop['id']]);
        $stop['activities'] = $actStmt->fetchAll();
    }
    unset($stop);

    $trip['stops'] = $stops;
    json_success($trip);
}

function copy_trip(PDO $pdo): void {
    $userId = require_login();
    $body = get_request_body();

    $slug = clean_str($body['slug'] ?? '');
    if ($slug === '') {
        json_error('slug is required');
    }

    $source = $pdo->prepare("SELECT * FROM trips WHERE share_slug = ? AND visibility = 'public'");
    $source->execute([$slug]);
    $sourceTrip = $source->fetch();

    if (!$sourceTrip) {
        json_error('This trip is not public or does not exist', 404);
    }

    $pdo->beginTransaction();
    try {
        $newTripStmt = $pdo->prepare("
            INSERT INTO trips (user_id, trip_name, start_date, end_date, description, cover_photo, visibility)
            VALUES (?, ?, ?, ?, ?, ?, 'private')
            RETURNING id
        ");
        $newTripStmt->execute([
            $userId,
            $sourceTrip['trip_name'] . ' (copy)',
            $sourceTrip['start_date'],
            $sourceTrip['end_date'],
            $sourceTrip['description'],
            $sourceTrip['cover_photo'],
        ]);
        $newTripId = (int) $newTripStmt->fetchColumn();

        $stopsStmt = $pdo->prepare('SELECT id, city_id, arrival_date AS start_date, departure_date AS end_date, order_index AS sort_order FROM trip_stops WHERE trip_id = ? ORDER BY order_index');
        $stopsStmt->execute([$sourceTrip['id']]);

        $insertStop = $pdo->prepare('
            INSERT INTO trip_stops (trip_id, city_id, arrival_date, departure_date, order_index)
            VALUES (?, ?, ?, ?, ?)
            RETURNING id
        ');
        $insertActivity = $pdo->prepare('
            INSERT INTO trip_activities (trip_stop_id, activity_id, scheduled_date, scheduled_time, notes)
            VALUES (?, ?, ?, ?, ?)
        ');
        $sourceActivities = $pdo->prepare('SELECT activity_id, scheduled_date, scheduled_time, notes FROM trip_activities WHERE trip_stop_id = ?');

        foreach ($stopsStmt->fetchAll() as $stop) {
            $insertStop->execute([$newTripId, $stop['city_id'], $stop['start_date'], $stop['end_date'], $stop['sort_order']]);
            $newStopId = (int) $insertStop->fetchColumn();

            $sourceActivities->execute([$stop['id']]);
            foreach ($sourceActivities->fetchAll() as $act) {
                $insertActivity->execute([
                    $newStopId, $act['activity_id'], $act['scheduled_date'], $act['scheduled_time'], $act['notes'],
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[GlobeTrotter] copy_trip failed: ' . $e->getMessage());
        json_error('Could not copy trip', 500);
    }

    json_success(['trip_id' => $newTripId], 201);
}
