<?php
/**
 * Simple Security Enhancements Migration Script
 * Applies security-related database schema changes one by one
 */

require_once 'config/db_connect.php';

echo "Starting Simple Security Enhancements Migration...\n";

try {
    $pdo = getDbConnection();
    
    // 1. Extend admin_actions table action types
    echo "1. Extending admin_actions action types...\n";
    try {
        $pdo->exec("ALTER TABLE admin_actions 
                    MODIFY COLUMN action_type ENUM('stop_bid', 'end_auction', 'cancel_auction', 'assign_admin', 'remove_admin', 'bulk_stop_bids', 'auto_end_expired') NOT NULL");
        echo "   ✓ Action types extended\n";
    } catch (Exception $e) {
        echo "   - Already extended or error: " . $e->getMessage() . "\n";
    }
    
    // 2. Add additional_data column
    echo "2. Adding additional_data column...\n";
    try {
        $pdo->exec("ALTER TABLE admin_actions ADD COLUMN additional_data JSON NULL AFTER reason");
        echo "   ✓ additional_data column added\n";
    } catch (Exception $e) {
        echo "   - Already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 3. Add ip_address column
    echo "3. Adding ip_address column...\n";
    try {
        $pdo->exec("ALTER TABLE admin_actions ADD COLUMN ip_address VARCHAR(45) NULL AFTER additional_data");
        echo "   ✓ ip_address column added\n";
    } catch (Exception $e) {
        echo "   - Already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 4. Add user_agent column
    echo "4. Adding user_agent column...\n";
    try {
        $pdo->exec("ALTER TABLE admin_actions ADD COLUMN user_agent TEXT NULL AFTER ip_address");
        echo "   ✓ user_agent column added\n";
    } catch (Exception $e) {
        echo "   - Already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 5. Create security_events table
    echo "5. Creating security_events table...\n";
    try {
        $pdo->exec("CREATE TABLE security_events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            context_data JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");
        echo "   ✓ security_events table created\n";
    } catch (Exception $e) {
        echo "   - Already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 6. Create user_sessions table
    echo "6. Creating user_sessions table...\n";
    try {
        $pdo->exec("CREATE TABLE user_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        echo "   ✓ user_sessions table created\n";
    } catch (Exception $e) {
        echo "   - Already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 7. Create failed_login_attempts table
    echo "7. Creating failed_login_attempts table...\n";
    try {
        $pdo->exec("CREATE TABLE failed_login_attempts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "   ✓ failed_login_attempts table created\n";
    } catch (Exception $e) {
        echo "   - Already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 8. Create indexes
    echo "8. Creating security indexes...\n";
    $indexes = [
        "CREATE INDEX idx_security_events_type ON security_events(event_type)",
        "CREATE INDEX idx_security_events_user_id ON security_events(user_id)",
        "CREATE INDEX idx_security_events_created_at ON security_events(created_at)",
        "CREATE INDEX idx_security_events_ip ON security_events(ip_address)",
        "CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id)",
        "CREATE INDEX idx_user_sessions_session_id ON user_sessions(session_id)",
        "CREATE INDEX idx_user_sessions_active ON user_sessions(is_active)",
        "CREATE INDEX idx_user_sessions_last_activity ON user_sessions(last_activity)",
        "CREATE INDEX idx_failed_logins_username ON failed_login_attempts(username)",
        "CREATE INDEX idx_failed_logins_ip ON failed_login_attempts(ip_address)",
        "CREATE INDEX idx_failed_logins_attempted_at ON failed_login_attempts(attempted_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "   ✓ Index created\n";
        } catch (Exception $e) {
            echo "   - Index already exists or error\n";
        }
    }
    
    echo "\nSecurity enhancements migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>