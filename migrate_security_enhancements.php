<?php
/**
 * Security Enhancements Migration Script
 * Applies security-related database schema changes
 */

require_once 'config/db_connect.php';

echo "Starting Security Enhancements Migration...\n";

try {
    // Read and execute the migration SQL
    $migration_sql = file_get_contents('sql/migration_security_enhancements.sql');
    
    if ($migration_sql === false) {
        throw new Exception("Could not read migration file");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $migration_sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    // Separate CREATE TABLE and CREATE INDEX statements
    $table_statements = [];
    $index_statements = [];
    $other_statements = [];
    
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        echo "Statement starts with: " . substr($trimmed, 0, 20) . "\n";
        
        if (stripos($trimmed, 'CREATE TABLE') === 0) {
            $table_statements[] = $statement;
            echo "  -> Added to table_statements\n";
        } elseif (stripos($trimmed, 'CREATE INDEX') === 0) {
            $index_statements[] = $statement;
            echo "  -> Added to index_statements\n";
        } else {
            $other_statements[] = $statement;
            echo "  -> Added to other_statements\n";
        }
    }
    
    // Debug: show what statements we have
    echo "ALTER statements: " . count($other_statements) . "\n";
    echo "CREATE TABLE statements: " . count($table_statements) . "\n";
    echo "CREATE INDEX statements: " . count($index_statements) . "\n";
    
    // Execute in order: ALTER statements first, then CREATE TABLE, then CREATE INDEX
    $all_statements = array_merge($other_statements, $table_statements, $index_statements);
    
    foreach ($all_statements as $statement) {
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "Already exists, skipping...\n";
            } else {
                throw $e;
            }
        }
    }
    
    $pdo->commit();
    echo "Security enhancements migration completed successfully!\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>