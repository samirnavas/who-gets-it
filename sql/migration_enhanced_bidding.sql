-- Enhanced Bidding System Migration Script
-- This script adds new columns and tables to support bid management and admin functionality
-- Run this script on existing installations to upgrade the database schema

-- Add bid status tracking to existing bids table
ALTER TABLE bids 
ADD COLUMN status ENUM('active', 'stopped') DEFAULT 'active',
ADD COLUMN stopped_at TIMESTAMP NULL,
ADD COLUMN stopped_by INT NULL,
ADD FOREIGN KEY (stopped_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add auction status tracking to existing items table
ALTER TABLE items 
ADD COLUMN status ENUM('active', 'ended', 'cancelled') DEFAULT 'active',
ADD COLUMN ended_at TIMESTAMP NULL,
ADD COLUMN ended_by INT NULL,
ADD FOREIGN KEY (ended_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add admin role to existing users table
ALTER TABLE users 
ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';

-- Create admin actions log table for audit trail
CREATE TABLE admin_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type ENUM('stop_bid', 'end_auction') NOT NULL,
    target_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for performance optimization
CREATE INDEX idx_bids_status ON bids(status);
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_admin_actions_admin_id ON admin_actions(admin_id);
CREATE INDEX idx_admin_actions_type ON admin_actions(action_type);
CREATE INDEX idx_admin_actions_created_at ON admin_actions(created_at);

-- Update existing data to set default values
UPDATE bids SET status = 'active' WHERE status IS NULL;
UPDATE items SET status = 'active' WHERE status IS NULL;
UPDATE users SET role = 'user' WHERE role IS NULL;