-- Database Performance Optimization
-- Adds indexes and optimizations for bid status and admin queries

-- Add composite indexes for common admin queries
CREATE INDEX idx_bids_status_created_at ON bids(status, created_at);
CREATE INDEX idx_bids_item_status ON bids(item_id, status);
CREATE INDEX idx_bids_user_status ON bids(user_id, status);

-- Add indexes for auction management queries
CREATE INDEX idx_items_status_end_time ON items(status, end_time);
CREATE INDEX idx_items_user_status ON items(user_id, status);
CREATE INDEX idx_items_ended_by ON items(ended_by);

-- Add indexes for admin action queries
CREATE INDEX idx_admin_actions_target_type ON admin_actions(target_id, action_type);
CREATE INDEX idx_admin_actions_admin_created ON admin_actions(admin_id, created_at);

-- Add indexes for bid history pagination
CREATE INDEX idx_bids_user_created_desc ON bids(user_id, created_at DESC);
CREATE INDEX idx_bids_item_created_desc ON bids(item_id, created_at DESC);

-- Add indexes for search functionality
CREATE INDEX idx_items_title ON items(title);
CREATE INDEX idx_users_username ON users(username);

-- Add covering indexes for common queries
CREATE INDEX idx_bids_covering_admin ON bids(status, item_id, user_id, bid_amount, created_at);
CREATE INDEX idx_items_covering_admin ON items(status, end_time, user_id, title, current_bid);

-- Optimize for winner determination queries
CREATE INDEX idx_bids_item_amount_desc ON bids(item_id, bid_amount DESC, status);

-- Add indexes for notification queries (if notifications table exists)
-- CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at);

-- Performance tuning settings (MySQL specific)
-- These would typically be set in my.cnf, but included here for reference

-- Optimize for InnoDB
-- SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB, adjust based on available RAM
-- SET GLOBAL innodb_log_file_size = 268435456; -- 256MB
-- SET GLOBAL innodb_flush_log_at_trx_commit = 2; -- Better performance, slight durability trade-off

-- Query cache settings
-- SET GLOBAL query_cache_size = 67108864; -- 64MB
-- SET GLOBAL query_cache_type = ON;

-- Connection settings
-- SET GLOBAL max_connections = 200;
-- SET GLOBAL thread_cache_size = 16;

-- Table maintenance (run periodically)
-- OPTIMIZE TABLE users;
-- OPTIMIZE TABLE items;
-- OPTIMIZE TABLE bids;
-- OPTIMIZE TABLE admin_actions;