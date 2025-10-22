<?php
/**
 * Admin Setup Script
 * Run this to update the database schema and create an admin user
 */

require_once 'config/db_connect.php';

echo "<h1>Admin Setup Script</h1>";
echo "<p>Updating database schema and creating admin user...</p>";

try {
    // Check if role column exists in users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($stmt->rowCount() == 0) {
        // Add role column to users table
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'");
        echo "<p>✓ Added 'role' column to users table.</p>";
    } else {
        echo "<p>✓ 'role' column already exists in users table.</p>";
    }
    
    // Check if status column exists in items table
    $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        // Add status columns to items table
        $pdo->exec("
            ALTER TABLE items 
            ADD COLUMN status ENUM('active', 'ended', 'cancelled') DEFAULT 'active',
            ADD COLUMN ended_at TIMESTAMP NULL,
            ADD COLUMN ended_by INT NULL,
            ADD FOREIGN KEY (ended_by) REFERENCES users(id)
        ");
        echo "<p>✓ Added status columns to items table.</p>";
    } else {
        echo "<p>✓ Status columns already exist in items table.</p>";
    }
    
    // Check if status column exists in bids table
    $stmt = $pdo->query("SHOW COLUMNS FROM bids LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        // Add status columns to bids table
        $pdo->exec("
            ALTER TABLE bids 
            ADD COLUMN status ENUM('active', 'stopped') DEFAULT 'active',
            ADD COLUMN stopped_at TIMESTAMP NULL,
            ADD COLUMN stopped_by INT NULL,
            ADD FOREIGN KEY (stopped_by) REFERENCES users(id)
        ");
        echo "<p>✓ Added status columns to bids table.</p>";
    } else {
        echo "<p>✓ Status columns already exist in bids table.</p>";
    }
    
    // Check if admin_actions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_actions'");
    if ($stmt->rowCount() == 0) {
        // Create admin_actions table
        $pdo->exec("
            CREATE TABLE admin_actions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_id INT NOT NULL,
                action_type ENUM('stop_bid', 'end_auction') NOT NULL,
                target_id INT NOT NULL,
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "<p>✓ Created admin_actions table.</p>";
    } else {
        echo "<p>✓ admin_actions table already exists.</p>";
    }
    
    // Create indexes for better performance
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_items_status ON items(status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bids_status ON bids(status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_admin_actions_admin_id ON admin_actions(admin_id)");
        echo "<p>✓ Created database indexes.</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Some indexes may already exist: " . $e->getMessage() . "</p>";
    }
    
    // Create admin user if it doesn't exist
    $admin_username = 'admin';
    $admin_password = 'admin123'; // Change this in production!
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$admin_username]);
    
    if ($stmt->rowCount() == 0) {
        // Create admin user
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$admin_username, $password_hash]);
        echo "<p>✓ Created admin user: <strong>$admin_username</strong> / <strong>$admin_password</strong></p>";
        echo "<p style='color: orange;'>⚠ <strong>IMPORTANT:</strong> Change the admin password after first login!</p>";
    } else {
        // Update existing user to admin
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = ?");
        $stmt->execute([$admin_username]);
        echo "<p>✓ Updated existing user '$admin_username' to admin role.</p>";
    }
    
    // Create a test regular user if it doesn't exist
    $test_username = 'testuser';
    $test_password = 'test123';
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$test_username]);
    
    if ($stmt->rowCount() == 0) {
        $password_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
        $stmt->execute([$test_username, $password_hash]);
        echo "<p>✓ Created test user: <strong>$test_username</strong> / <strong>$test_password</strong></p>";
    } else {
        echo "<p>✓ Test user '$test_username' already exists.</p>";
    }
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p><strong>Admin Login:</strong> $admin_username / $admin_password</p>";
    echo "<p><strong>Test User Login:</strong> $test_username / $test_password</p>";
    echo "<p><a href='index.php'>Go to the auction site</a></p>";
    echo "<p><a href='admin/index.php'>Go to admin panel</a> (login as admin first)</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database setup failed: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup error: " . $e->getMessage() . "</p>";
}
?>