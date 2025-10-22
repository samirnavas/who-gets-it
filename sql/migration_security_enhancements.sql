-- Security Enhancements Migration
-- Adds additional audit logging capabilities and security features

-- Extend admin_actions table to support more action types and additional data
ALTER TABLE admin_actions 
MODIFY COLUMN action_type ENUM('stop_bid', 'end_auction', 'cancel_auction', 'assign_admin', 'remove_admin', 'bulk_stop_bids', 'auto_end_expired') NOT NULL;

-- Add additional_data column for storing extra context
ALTER TABLE admin_actions 
ADD COLUMN additional_data JSON NULL AFTER reason;

-- Add IP address tracking for security
ALTER TABLE admin_actions 
ADD COLUMN ip_address VARCHAR(45) NULL AFTER additional_data;

-- Add user agent tracking
ALTER TABLE admin_actions 
ADD COLUMN user_agent TEXT NULL AFTER ip_address;

-- Create security_events table for comprehensive security logging
CREATE TABLE security_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    context_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add session tracking table for enhanced security
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add failed login attempts tracking
CREATE TABLE failed_login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for security and performance
CREATE INDEX idx_security_events_type ON security_events(event_type);
CREATE INDEX idx_security_events_user_id ON security_events(user_id);
CREATE INDEX idx_security_events_created_at ON security_events(created_at);
CREATE INDEX idx_security_events_ip ON security_events(ip_address);

-- Add indexes for session management
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_session_id ON user_sessions(session_id);
CREATE INDEX idx_user_sessions_active ON user_sessions(is_active);
CREATE INDEX idx_user_sessions_last_activity ON user_sessions(last_activity);

-- Add indexes for failed login tracking
CREATE INDEX idx_failed_logins_username ON failed_login_attempts(username);
CREATE INDEX idx_failed_logins_ip ON failed_login_attempts(ip_address);
CREATE INDEX idx_failed_logins_attempted_at ON failed_login_attempts(attempted_at);