<?php
/**
 * Enhanced Bidding System Migration Script
 * 
 * This script safely applies database schema changes for the enhanced bidding system.
 * It checks for existing columns/tables before attempting to create them.
 */

require_once 'config/db_connect.php';

function checkColumnExists($table, $column, $pdo) {
    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $stmt = $pdo->query($sql);
    return $stmt->rowCount() > 0;
}

function checkTableExists($table, $pdo) {
    $sql = "SHOW TABLES LIKE '$table'";
    $stmt = $pdo->query($sql);
    return $stmt->rowCount() > 0;
}

function runMigration($pdo) {
    echo "Starting Enhanced Bidding System Migration...\n";
    
    // Check and add bid status columns
    if (!checkColumnExists('bids', 'status', $pdo)) {
        echo "Adding status column to bids table...\n";
        $sql = "ALTER TABLE bids ADD COLUMN status ENUM('active', 'stopped') DEFAULT 'active'";
        $pdo->exec($sql);
    } else {
        echo "Bids status column already exists.\n";
    }
    
    if (!checkColumnExists('bids', 'stopped_at', $pdo)) {
        echo "Adding stopped_at column to bids table...\n";
        $sql = "ALTER TABLE bids ADD COLUMN stopped_at TIMESTAMP NULL";
        $pdo->exec($sql);
    } else {
        echo "Bids stopped_at column already exists.\n";
    }
    
    if (!checkColumnExists('bids', 'stopped_by', $pdo)) {
        echo "Adding stopped_by column to bids table...\n";
        $sql = "ALTER TABLE bids ADD COLUMN stopped_by INT NULL, ADD FOREIGN KEY (stopped_by) REFERENCES users(id) ON DELETE SET NULL";
        $pdo->exec($sql);
    } else {
        echo "Bids stopped_by column already exists.\n";
    }
    
    // Check and add item status columns
    if (!checkColumnExists('items', 'status', $pdo)) {
        echo "Adding status column to items table...\n";
        $sql = "ALTER TABLE items ADD COLUMN status ENUM('active', 'ended', 'cancelled') DEFAULT 'active'";
        $pdo->exec($sql);
    } else {
        echo "Items status column already exists.\n";
    }
    
    if (!checkColumnExists('items', 'ended_at', $pdo)) {
        echo "Adding ended_at column to items table...\n";
        $sql = "ALTER TABLE items ADD COLUMN ended_at TIMESTAMP NULL";
        $pdo->exec($sql);
    } else {
        echo "Items ended_at column already exists.\n";
    }
    
    if (!checkColumnExists('items', 'ended_by', $pdo)) {
        echo "Adding ended_by column to items table...\n";
        $sql = "ALTER TABLE items ADD COLUMN ended_by INT NULL, ADD FOREIGN KEY (ended_by) REFERENCES users(id) ON DELETE SET NULL";
        $pdo->exec($sql);
    } else {
        echo "Items ended_by column already exists.\n";
    }
    
    // Check and add user role column
    if (!checkColumnExists('users', 'role', $pdo)) {
        echo "Adding role column to users table...\n";
        $sql = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'";
        $pdo->exec($sql);
    } else {
        echo "Users role column already exists.\n";
    }
    
    // Check and create admin_actions table
    if (!checkTableExists('admin_actions', $pdo)) {
        echo "Creating admin_actions table...\n";
        $sql = "CREATE TABLE admin_actions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT NOT NULL,
            action_type ENUM('stop_bid', 'end_auction') NOT NULL,
            target_id INT NOT NULL,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
    } else {
        echo "Admin_actions table already exists.\n";
    }
    
    // Add indexes for performance
    echo "Adding performance indexes...\n";
    $indexes = [
        "CREATE INDEX idx_bids_status ON bids(status)",
        "CREATE INDEX idx_items_status ON items(status)",
        "CREATE INDEX idx_users_role ON users(role)",
        "CREATE INDEX idx_admin_actions_admin_id ON admin_actions(admin_id)",
        "CREATE INDEX idx_admin_actions_type ON admin_actions(action_type)",
        "CREATE INDEX idx_admin_actions_created_at ON admin_actions(created_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            // Ignore errors for indexes that might already exist
            $pdo->exec($index_sql);
        } catch (PDOException $e) {
            // Index might already exist, continue
        }
    }
    
    // Update existing data
    echo "Updating existing data with default values...\n";
    $pdo->exec("UPDATE bids SET status = 'active' WHERE status IS NULL");
    $pdo->exec("UPDATE items SET status = 'active' WHERE status IS NULL");
    $pdo->exec("UPDATE users SET role = 'user' WHERE role IS NULL");
    
    echo "Migration completed successfully!\n";
}

// Run migration if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        runMigration($pdo);
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>