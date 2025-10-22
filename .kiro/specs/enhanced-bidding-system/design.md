# Design Document

## Overview

The Enhanced Bidding System extends the existing auction platform with comprehensive bid management capabilities. The design builds upon the current PHP/MySQL architecture while adding new database tables, administrative interfaces, and enhanced user experiences for bid tracking and auction management.

## Architecture

### System Components

The system follows the existing MVC-like pattern with the following key components:

- **Database Layer**: MySQL with new tables for bid status tracking and admin actions
- **Business Logic Layer**: PHP functions for bid management, admin controls, and status updates
- **Presentation Layer**: Enhanced web interfaces for users and administrators
- **Authentication Layer**: Existing auth system extended with admin role management

### Database Schema Extensions

```sql
-- Add bid status tracking
ALTER TABLE bids ADD COLUMN status ENUM('active', 'stopped') DEFAULT 'active';
ALTER TABLE bids ADD COLUMN stopped_at TIMESTAMP NULL;
ALTER TABLE bids ADD COLUMN stopped_by INT NULL;
ALTER TABLE bids ADD FOREIGN KEY (stopped_by) REFERENCES users(id);

-- Add auction status tracking
ALTER TABLE items ADD COLUMN status ENUM('active', 'ended', 'cancelled') DEFAULT 'active';
ALTER TABLE items ADD COLUMN ended_at TIMESTAMP NULL;
ALTER TABLE items ADD COLUMN ended_by INT NULL;
ALTER TABLE items ADD FOREIGN KEY (ended_by) REFERENCES users(id);

-- Add admin role to users
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';

-- Create admin actions log
CREATE TABLE admin_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type ENUM('stop_bid', 'end_auction') NOT NULL,
    target_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
```

## Components and Interfaces

### 1. Enhanced My Bids Page (`my_bids.php`)

**Purpose**: Display comprehensive bid history with real-time status updates

**Key Features**:
- Tabular display of all user bids with status indicators
- Real-time updates using AJAX for bid status changes
- Filtering options (active, stopped, won, lost)
- Pagination for large bid histories

**Data Display**:
- Item title and image thumbnail
- Bid amount and timestamp
- Current status (winning, outbid, stopped, won, lost)
- Auction end time and remaining time
- Action buttons (view item, rebid if allowed)

### 2. Admin Panel (`admin/index.php`)

**Purpose**: Central dashboard for auction and bid management

**Key Features**:
- Overview statistics (active auctions, total bids, stopped bids)
- Quick actions for common admin tasks
- Navigation to detailed management pages

### 3. Admin Bid Management (`admin/bids.php`)

**Purpose**: Comprehensive bid oversight and control interface

**Key Features**:
- Searchable and filterable bid listing
- Individual bid stop functionality with reason logging
- Bulk actions for multiple bid management
- Audit trail display for admin actions

**Interface Elements**:
- Data table with sorting and filtering
- "Stop Bid" buttons with confirmation dialogs
- Reason input for bid stops
- Status indicators and timestamps

### 4. Admin Auction Management (`admin/auctions.php`)

**Purpose**: Auction lifecycle management and completion

**Key Features**:
- Active auction listing with end controls
- Auction completion workflow
- Winner determination and notification
- Auction statistics and reporting

### 5. Enhanced Item Display (`item.php` updates)

**Purpose**: Show auction outcomes and winner information

**Key Features**:
- Winner display for completed auctions
- Bid history with status indicators
- Admin action notifications
- Enhanced status messaging

## Data Models

### Bid Model Extensions

```php
class Bid {
    public $id;
    public $item_id;
    public $user_id;
    public $bid_amount;
    public $status; // 'active', 'stopped'
    public $created_at;
    public $stopped_at;
    public $stopped_by;
    
    public function isActive() {
        return $this->status === 'active';
    }
    
    public function isStopped() {
        return $this->status === 'stopped';
    }
    
    public function getStatusDisplay() {
        // Return user-friendly status text
    }
}
```

### Item Model Extensions

```php
class Item {
    // Existing properties...
    public $status; // 'active', 'ended', 'cancelled'
    public $ended_at;
    public $ended_by;
    public $winner_id;
    
    public function isActive() {
        return $this->status === 'active' && strtotime($this->end_time) > time();
    }
    
    public function isEnded() {
        return $this->status === 'ended' || strtotime($this->end_time) <= time();
    }
    
    public function getWinner() {
        // Return winner user object if auction ended
    }
    
    public function getValidBids() {
        // Return only active (non-stopped) bids
    }
}
```

### Admin Action Model

```php
class AdminAction {
    public $id;
    public $admin_id;
    public $action_type; // 'stop_bid', 'end_auction'
    public $target_id;
    public $reason;
    public $created_at;
    
    public function getAdminUsername() {
        // Return admin username
    }
    
    public function getTargetDescription() {
        // Return description of target (bid/auction)
    }
}
```

## Error Handling

### Bid Management Errors

- **Invalid Bid Stop**: Validate bid exists and is active before stopping
- **Permission Errors**: Ensure only admins can perform admin actions
- **Concurrent Modifications**: Handle race conditions in bid status updates
- **Database Failures**: Implement transaction rollback for critical operations

### Admin Action Validation

- **Role Verification**: Confirm admin privileges before allowing actions
- **Target Validation**: Ensure target bids/auctions exist and are in valid states
- **Audit Trail**: Log all admin actions with timestamps and reasons
- **Error Recovery**: Provide clear error messages and recovery options

### User Experience Errors

- **Status Synchronization**: Handle cases where UI state differs from database
- **Real-time Updates**: Graceful degradation when AJAX updates fail
- **Navigation Errors**: Proper redirects when accessing invalid resources

## Testing Strategy

### Unit Testing Focus Areas

1. **Bid Status Management**
   - Test bid stopping functionality
   - Validate status transitions
   - Verify permission checks

2. **Auction Completion Logic**
   - Test winner determination algorithms
   - Validate auction ending workflows
   - Verify status updates

3. **Admin Permission System**
   - Test role-based access controls
   - Validate admin action logging
   - Verify audit trail accuracy

### Integration Testing Scenarios

1. **End-to-End Bid Management**
   - User places bid → Admin stops bid → User sees stopped status
   - Multiple bids on item → Admin ends auction → Winner determined

2. **Real-time Status Updates**
   - Bid status changes reflect immediately across all interfaces
   - Auction endings update all relevant pages

3. **Admin Workflow Testing**
   - Complete admin workflows from login to action completion
   - Verify proper error handling and user feedback

### User Acceptance Testing

1. **User Bid Tracking**
   - Users can easily find and understand their bid status
   - Status changes are clearly communicated
   - Navigation between related pages works smoothly

2. **Admin Efficiency**
   - Admins can quickly identify and act on problematic bids
   - Bulk operations work correctly for multiple items
   - Audit trails provide sufficient information for accountability

## Security Considerations

### Authentication and Authorization

- **Admin Role Verification**: Strict checking of admin privileges for all admin actions
- **Session Management**: Secure session handling for admin interfaces
- **CSRF Protection**: Implement CSRF tokens for all admin forms

### Data Protection

- **Input Validation**: Sanitize all user inputs, especially admin reason fields
- **SQL Injection Prevention**: Use prepared statements for all database queries
- **XSS Prevention**: Proper output escaping for all user-generated content

### Audit and Compliance

- **Action Logging**: Comprehensive logging of all admin actions with timestamps
- **Data Retention**: Define retention policies for bid history and admin logs
- **Access Monitoring**: Track admin access patterns for security monitoring

## Performance Considerations

### Database Optimization

- **Indexing Strategy**: Add indexes for bid status queries and admin searches
- **Query Optimization**: Optimize queries for bid history and admin dashboards
- **Connection Pooling**: Efficient database connection management

### Real-time Updates

- **AJAX Polling**: Implement efficient polling for status updates
- **Caching Strategy**: Cache frequently accessed data like bid counts
- **Load Balancing**: Consider load distribution for high-traffic scenarios

### Scalability Planning

- **Pagination**: Implement pagination for large bid histories
- **Data Archiving**: Plan for archiving old auction data
- **Performance Monitoring**: Track response times and database performance