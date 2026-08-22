<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@globetrotter.dev'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        print_r($user);
        
        // Let's test the password hash against 'Admin@123'
        $hash = $user['password_hash'];
        if (password_verify('Admin@123', $hash)) {
            echo "\nPassword verify SUCCESS!";
        } else {
            echo "\nPassword verify FAILED! Hash in DB: " . $hash;
            
            // Let's update it to the correct hash
            $newHash = password_hash('Admin@123', PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            echo "\nUpdated password hash to: " . $newHash;
        }
    } else {
        echo "User not found!";
        
        // Let's insert the user
        $newHash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES ('Admin', 'User', 'admin@globetrotter.dev', ?, 'admin')")->execute([$newHash]);
        echo "\nInserted admin user.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
