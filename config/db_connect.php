<?php
/**
 * Database Connection Configuration
 * Provides PDO connection with error handling for the auction system
 */

// Database configuration
$host = 'localhost';
$dbname = 'college_auction';
$username = 'root';
$password = '';

// PDO options for security and error handling
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    
    // Connection successful - no output needed for include files
    
} catch (PDOException $e) {
    // Log error for debugging (in production, log to file instead of displaying)
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly error message
    die("Database connection failed. Please try again later.");
}

/**
 * Function to get database connection
 * @return PDO Database connection object
 */
function getDbConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Function to execute prepared statements safely
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for the query
 * @return PDOStatement Executed statement
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Database operation failed. Please try again.");
    }
}

/**
 * Function to get a single record
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false Single record or false if not found
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Function to get multiple records
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array Array of records
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}
?>