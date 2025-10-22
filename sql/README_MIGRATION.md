# Enhanced Bidding System Database Migration

This directory contains the database schema updates needed for the Enhanced Bidding System feature.

## Files

- `database_schema.sql` - Complete database schema for fresh installations (updated with new columns and tables)
- `migration_enhanced_bidding.sql` - Raw SQL migration script for existing installations
- `../migrate_enhanced_bidding.php` - PHP migration script with safety checks

## For Fresh Installations

Use the updated `database_schema.sql` file which includes all the new columns and tables:

```bash
mysql -u username -p database_name < sql/database_schema.sql
```

## For Existing Installations

### Option 1: Use PHP Migration Script (Recommended)

The PHP script includes safety checks and will not fail if columns/tables already exist:

```bash
php migrate_enhanced_bidding.php
```

### Option 2: Use Raw SQL Migration

If you prefer to run the SQL directly:

```bash
mysql -u username -p database_name < sql/migration_enhanced_bidding.sql
```

## Schema Changes

### New Columns Added

**bids table:**
- `status` ENUM('active', 'stopped') DEFAULT 'active'
- `stopped_at` TIMESTAMP NULL
- `stopped_by` INT NULL (foreign key to users.id)

**items table:**
- `status` ENUM('active', 'ended', 'cancelled') DEFAULT 'active'
- `ended_at` TIMESTAMP NULL
- `ended_by` INT NULL (foreign key to users.id)

**users table:**
- `role` ENUM('user', 'admin') DEFAULT 'user'

### New Tables

**admin_actions:**
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `admin_id` INT NOT NULL (foreign key to users.id)
- `action_type` ENUM('stop_bid', 'end_auction') NOT NULL
- `target_id` INT NOT NULL
- `reason` TEXT
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### New Indexes

Performance indexes added for:
- bids.status
- items.status
- users.role
- admin_actions.admin_id
- admin_actions.action_type
- admin_actions.created_at

## Verification

After running the migration, you can verify the changes by checking:

1. Column existence: `SHOW COLUMNS FROM table_name;`
2. Table existence: `SHOW TABLES LIKE 'admin_actions';`
3. Index existence: `SHOW INDEX FROM table_name;`

## Rollback

If you need to rollback the changes, you can:

1. Drop the new columns (will lose data):
   ```sql
   ALTER TABLE bids DROP COLUMN status, DROP COLUMN stopped_at, DROP COLUMN stopped_by;
   ALTER TABLE items DROP COLUMN status, DROP COLUMN ended_at, DROP COLUMN ended_by;
   ALTER TABLE users DROP COLUMN role;
   ```

2. Drop the new table:
   ```sql
   DROP TABLE admin_actions;
   ```

**Warning:** Rollback will permanently delete all data in the new columns and table.