<?php
/**
 * Simple Database Setup Page
 * Run this from your browser to set up the database
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'college_auction';

echo "<h1>College Auction Database Setup</h1>";
echo "<p>Setting up the database...</p>";

try {
    // First, connect without specifying database to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<p>✓ Database '$dbname' created successfully or already exists.</p>";
    
    // Switch to the new database
    $pdo->exec("USE $dbname");
    
    // Create tables directly (since we might not have file access)
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(500) DEFAULT 'https://via.placeholder.com/300x200?text=No+Image',
        starting_bid DECIMAL(10,2) NOT NULL,
        current_bid DECIMAL(10,2) NOT NULL,
        highest_bidder_id INT NULL,
        end_time DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (highest_bidder_id) REFERENCES users(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS bids (
        id INT PRIMARY KEY AUTO_INCREMENT,
        item_id INT NOT NULL,
        user_id INT NOT NULL,
        bid_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "<p>✓ Database tables created successfully!</p>";
    echo "<p>✓ Tables created: users, items, bids</p>";
    
    // Test the connection
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✓ Found tables: " . implode(', ', $tables) . "</p>";
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p><a href='index.php'>Go to the auction site</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database setup failed: " . $e->getMessage() . "</p>";
    echo "<p>Make sure MySQL is running in XAMPP and try again.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup error: " . $e->getMessage() . "</p>";
}
?>