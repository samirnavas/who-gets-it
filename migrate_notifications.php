<?php
/**
 * Migration Script for Notifications System
 * Adds the notifications table to the database
 */

require_once 'config/db_connect.php';

try {
    echo "Starting notifications migration...\n";
    
    // Read and execute the notifications table SQL
    $sql = file_get_contents('sql/notifications_table.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read notifications_table.sql file");
    }
    
    // Execute the SQL
    $pdo = getDbConnection();
    $pdo->exec($sql);
    
    echo "✅ Notifications table created successfully!\n";
    echo "Migration completed.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>