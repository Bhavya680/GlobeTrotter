<?php
require_once __DIR__ . '/../includes/auth.php';

try {
    // Add tags column
    $pdo->exec("ALTER TABLE community_posts ADD COLUMN IF NOT EXISTS tags JSON DEFAULT '[]'");
    echo "Added tags column to community_posts.\n";
    
    // Create community_comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_comments (
            id SERIAL PRIMARY KEY,
            post_id INT NOT NULL REFERENCES community_posts(id) ON DELETE CASCADE,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            content TEXT NOT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )
    ");
    
    // Create an index for faster lookups
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_community_comments_post ON community_comments(post_id)");
    
    echo "Created community_comments table.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
