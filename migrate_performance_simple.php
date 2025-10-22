<?php
/**
 * Simple Performance Optimization Migration Script
 * Applies database performance optimizations including indexes
 */

require_once 'config/db_connect.php';

echo "Starting Simple Performance Optimization Migration...\n";

try {
    $pdo = getDbConnection();
    
    // Performance indexes to create
    $indexes = [
        // Composite indexes for common admin queries
        "CREATE INDEX idx_bids_status_created_at ON bids(status, created_at)",
        "CREATE INDEX idx_bids_item_status ON bids(item_id, status)",
        "CREATE INDEX idx_bids_user_status ON bids(user_id, status)",
        
        // Indexes for auction management queries
        "CREATE INDEX idx_items_status_end_time ON items(status, end_time)",
        "CREATE INDEX idx_items_user_status ON items(user_id, status)",
        "CREATE INDEX idx_items_ended_by ON items(ended_by)",
        
        // Indexes for admin action queries
        "CREATE INDEX idx_admin_actions_target_type ON admin_actions(target_id, action_type)",
        "CREATE INDEX idx_admin_actions_admin_created ON admin_actions(admin_id, created_at)",
        
        // Indexes for bid history pagination
        "CREATE INDEX idx_bids_user_created_desc ON bids(user_id, created_at DESC)",
        "CREATE INDEX idx_bids_item_created_desc ON bids(item_id, created_at DESC)",
        
        // Indexes for search functionality
        "CREATE INDEX idx_items_title ON items(title)",
        "CREATE INDEX idx_users_username ON users(username)",
        
        // Covering indexes for common queries
        "CREATE INDEX idx_bids_covering_admin ON bids(status, item_id, user_id, bid_amount, created_at)",
        "CREATE INDEX idx_items_covering_admin ON items(status, end_time, user_id, title, current_bid)",
        
        // Optimize for winner determination queries
        "CREATE INDEX idx_bids_item_amount_desc ON bids(item_id, bid_amount DESC, status)"
    ];
    
    $created_count = 0;
    $skipped_count = 0;
    
    foreach ($indexes as $index_sql) {
        try {
            // Extract index name for display
            if (preg_match('/CREATE INDEX (\w+)/', $index_sql, $matches)) {
                $index_name = $matches[1];
                echo "Creating index: {$index_name}...\n";
            }
            
            $pdo->exec($index_sql);
            $created_count++;
            echo "   ✓ Created successfully\n";
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "   - Already exists, skipping\n";
                $skipped_count++;
            } else {
                echo "   - Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nAnalyzing tables for query optimization...\n";
    $tables = ['users', 'items', 'bids', 'admin_actions'];
    
    foreach ($tables as $table) {
        try {
            echo "Analyzing table: {$table}...\n";
            $pdo->exec("ANALYZE TABLE {$table}");
            echo "   ✓ Analyzed\n";
        } catch (Exception $e) {
            echo "   - Warning: Could not analyze table {$table}\n";
        }
    }
    
    echo "\nPerformance optimization migration completed successfully!\n";
    echo "Created indexes: {$created_count}\n";
    echo "Skipped existing indexes: {$skipped_count}\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>