<?php
require 'config/db.php';
try {
    $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL;');
    echo "Added bio. ";
} catch (Exception $e) {}

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50),
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    echo "Added admin_notifications. ";
} catch (Exception $e) {}
