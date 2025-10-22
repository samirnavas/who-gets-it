<?php
/**
 * Database Connection Test
 * Simple script to verify database connection is working
 */

require_once 'config/db_connect.php';

try {
    // Test basic connection
    $pdo = getDbConnection();
    echo "✓ Database connection successful!\n";
    
    // Test if tables exist
    $tables = ['users', 'items', 'bids'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
    
    // Test helper functions
    $result = fetchAll("SHOW TABLES");
    echo "✓ Helper functions working - Found " . count($result) . " tables\n";
    
    echo "\nDatabase setup verification complete!\n";
    
} catch (Exception $e) {
    echo "✗ Connection test failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>