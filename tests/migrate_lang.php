<?php
require 'includes/auth.php';
$pdo->exec("ALTER TABLE users ADD COLUMN language_pref VARCHAR(10) NOT NULL DEFAULT 'en'");
echo "Language preference column added.";
