<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Mock session
$_SESSION['user_id'] = 4;

function create_post_test(PDO $pdo, int $userId): void
{
    $title = "Test Post";
    $content = "This is a test post that is exactly 20 characters long.";
    $tripId = null;
    $tags = ["tag1", "tag2"];

    $cleanTags = array_values(array_filter(array_map('clean_str', $tags)));

    $stmt = $pdo->prepare("
        INSERT INTO community_posts (user_id, trip_id, title, content, tags) 
        VALUES (?, ?, ?, ?, ?) RETURNING id
    ");
    
    // Simulate what API does
    try {
        $stmt->execute([$userId, $tripId, $title, $content, json_encode($cleanTags)]);
        $id = $stmt->fetchColumn();
        echo "SUCCESS! Inserted ID: " . $id;
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage();
    }
}

create_post_test($pdo, 4);
