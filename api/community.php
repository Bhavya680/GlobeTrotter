<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$pdo = DB::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT cp.*, u.first_name, u.last_name FROM community_posts cp JOIN users u ON cp.user_id = u.id ORDER BY cp.created_at DESC LIMIT 50");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        requireLogin();
        $userId = $_SESSION['user_id'];
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? 'post';
        
        if ($action === 'post') {
            $title = $input['title'] ?? $_POST['title'] ?? '';
            $content = $input['content'] ?? $_POST['content'] ?? '';
            $tripId = $input['trip_id'] ?? $_POST['trip_id'] ?? null;
            
            if (empty($title) || empty($content)) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and content required']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, trip_id, title, content) VALUES (?, ?, ?, ?) RETURNING id");
            $stmt->execute([$userId, $tripId, $title, $content]);
            echo json_encode(['success' => true, 'message' => 'Post created', 'id' => $stmt->fetchColumn()]);
            
        } elseif ($action === 'like') {
            $postId = $input['post_id'] ?? $_POST['post_id'] ?? null;
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['error' => 'post_id required']);
                exit;
            }
            
            $check = $pdo->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
            $check->execute([$postId, $userId]);
            
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM community_likes WHERE post_id = ? AND user_id = ?")->execute([$postId, $userId]);
                $pdo->prepare("UPDATE community_posts SET likes_count = likes_count - 1 WHERE id = ?")->execute([$postId]);
                echo json_encode(['success' => true, 'message' => 'Unliked']);
            } else {
                $pdo->prepare("INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)")->execute([$postId, $userId]);
                $pdo->prepare("UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$postId]);
                echo json_encode(['success' => true, 'message' => 'Liked']);
            }
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}
?>
