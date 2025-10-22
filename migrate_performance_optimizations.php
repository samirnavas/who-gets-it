<?php
/**
 * Performance Optimization Migration Script
 * Applies database performance optimizations including indexes
 */

require_once 'config/db_connect.php';

echo "Starting Performance Optimization Migration...\n";

try {
    // Read and execute the performance optimization SQL
    $optimization_sql = file_get_contents('sql/performance_optimization.sql');
    
    if ($optimization_sql === false) {
        throw new Exception("Could not read performance optimization file");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $optimization_sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*SET\s+GLOBAL/i', $stmt); // Skip global settings
        }
    );
    
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    $created_indexes = 0;
    $skipped_indexes = 0;
    
    foreach ($statements as $statement) {
        try {
            if (preg_match('/CREATE\s+INDEX\s+(\w+)/i', $statement, $matches)) {
                $index_name = $matches[1];
                echo "Creating index: {$index_name}...\n";
                $pdo->exec($statement);
                $created_indexes++;
            } else {
                echo "Executing: " . substr($statement, 0, 50) . "...\n";
                $pdo->exec($statement);
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "Index already exists, skipping...\n";
                $skipped_indexes++;
            } else {
                throw $e;
            }
        }
    }
    
    $pdo->commit();
    
    echo "\nPerformance optimization migration completed successfully!\n";
    echo "Created indexes: {$created_indexes}\n";
    echo "Skipped existing indexes: {$skipped_indexes}\n";
    
    // Analyze tables for better query optimization
    echo "\nAnalyzing tables for query optimization...\n";
    $tables = ['users', 'items', 'bids', 'admin_actions'];
    
    foreach ($tables as $table) {
        try {
            echo "Analyzing table: {$table}...\n";
            $pdo->exec("ANALYZE TABLE {$table}");
        } catch (Exception $e) {
            echo "Warning: Could not analyze table {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nPerformance optimization complete!\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>