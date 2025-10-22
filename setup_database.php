<?php
/**
 * Database Setup Script
 * Run this file once to create the database and tables
 */

// Database configuration for setup
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'college_auction';

try {
    // First, connect without specifying database to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database '$dbname' created successfully or already exists.\n";
    
    // Switch to the new database
    $pdo->exec("USE $dbname");
    
    // Read and execute the schema file
    $schemaFile = __DIR__ . '/sql/database_schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !str_starts_with($stmt, '--');
        }
    );
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database schema created successfully!\n";
    echo "Tables created: users, items, bids\n";
    echo "Indexes created for better performance\n";
    
} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Setup error: " . $e->getMessage() . "\n";
    exit(1);
}
?>