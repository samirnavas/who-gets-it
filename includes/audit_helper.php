<?php
/**
 * Audit Logging Helper Functions
 * Provides comprehensive audit logging for all admin actions
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Log admin action to database
 * @param string $action_type Type of action (stop_bid, end_auction, etc.)
 * @param int $target_id ID of the target (bid_id, item_id, etc.)
 * @param string $reason Reason for the action
 * @param array $additional_data Additional data to log (optional)
 * @return bool True if logged successfully, false otherwise
 */
function logAdminAction($action_type, $target_id, $reason = '', $additional_data = []) {
    $admin_id = getCurrentUserId();
    
    if (!$admin_id || !isAdmin()) {
        error_log("Attempted to log admin action without admin privileges");
        return false;
    }
    
    try {
        // Validate inputs
        $action_type = sanitizeString($action_type, 50);
        $target_id = validateInteger($target_id, 1);
        $reason = validateAdminReason($reason, 1000);
        
        if ($target_id === false) {
            error_log("Invalid target_id for admin action: " . $target_id);
            return false;
        }
        
        // Prepare additional data as JSON
        $additional_json = null;
        if (!empty($additional_data)) {
            $additional_json = json_encode($additional_data, JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "INSERT INTO admin_actions (admin_id, action_type, target_id, reason, additional_data, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = executeQuery($sql, [$admin_id, $action_type, $target_id, $reason, $additional_json]);
        
        // Also log to system log for security monitoring
        $admin_username = getCurrentUsername();
        $log_message = "Admin action: {$action_type} on target {$target_id} by {$admin_username}";
        if (!empty($reason)) {
            $log_message .= " - Reason: {$reason}";
        }
        error_log($log_message);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
        return false;
    }
}

/**
 * Get admin action history
 * @param int $limit Number of records to retrieve
 * @param int $offset Offset for pagination
 * @param string $action_type Filter by action type (optional)
 * @param int $admin_id Filter by admin ID (optional)
 * @return array Array of admin action records
 */
function getAdminActionHistory($limit = 50, $offset = 0, $action_type = null, $admin_id = null) {
    if (!isAdmin()) {
        return [];
    }
    
    try {
        $where_conditions = [];
        $params = [];
        
        if ($action_type) {
            $where_conditions[] = "aa.action_type = ?";
            $params[] = $action_type;
        }
        
        if ($admin_id) {
            $where_conditions[] = "aa.admin_id = ?";
            $params[] = $admin_id;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT aa.*, u.username as admin_username 
                FROM admin_actions aa 
                JOIN users u ON aa.admin_id = u.id 
                {$where_clause}
                ORDER BY aa.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return fetchAll($sql, $params);
        
    } catch (Exception $e) {
        error_log("Error getting admin action history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get admin action statistics
 * @param string $period Period for statistics ('day', 'week', 'month')
 * @return array Statistics array
 */
function getAdminActionStats($period = 'week') {
    if (!isAdmin()) {
        return [];
    }
    
    try {
        $date_condition = '';
        switch ($period) {
            case 'day':
                $date_condition = "aa.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $date_condition = "aa.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "aa.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            default:
                $date_condition = "1=1";
        }
        
        $sql = "SELECT 
                    action_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT admin_id) as admin_count
                FROM admin_actions aa 
                WHERE {$date_condition}
                GROUP BY action_type
                ORDER BY count DESC";
        
        return fetchAll($sql);
        
    } catch (Exception $e) {
        error_log("Error getting admin action stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Log security event (failed login attempts, suspicious activity, etc.)
 * @param string $event_type Type of security event
 * @param string $description Description of the event
 * @param array $context Additional context data
 * @return bool True if logged successfully, false otherwise
 */
function logSecurityEvent($event_type, $description, $context = []) {
    try {
        // Add IP address and user agent to context
        $context['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['timestamp'] = date('Y-m-d H:i:s');
        
        if (isLoggedIn()) {
            $context['user_id'] = getCurrentUserId();
            $context['username'] = getCurrentUsername();
        }
        
        // Log to system log
        $log_message = "Security Event: {$event_type} - {$description}";
        if (!empty($context)) {
            $log_message .= " - Context: " . json_encode($context);
        }
        error_log($log_message);
        
        // Could also log to database security_events table if needed
        return true;
        
    } catch (Exception $e) {
        error_log("Error logging security event: " . $e->getMessage());
        return false;
    }
}

/**
 * Log failed admin action attempt
 * @param string $action_type Type of action attempted
 * @param string $reason Reason for failure
 * @param array $context Additional context
 * @return bool True if logged successfully, false otherwise
 */
function logFailedAdminAction($action_type, $reason, $context = []) {
    $description = "Failed admin action: {$action_type} - {$reason}";
    return logSecurityEvent('failed_admin_action', $description, $context);
}

/**
 * Validate admin action permissions
 * @param string $action_type Type of action to validate
 * @param int $target_id Target ID for the action
 * @return array Validation result with 'valid' boolean and 'error' message
 */
function validateAdminActionPermissions($action_type, $target_id) {
    if (!isAdmin()) {
        logFailedAdminAction($action_type, 'Not an admin user', ['target_id' => $target_id]);
        return ['valid' => false, 'error' => 'Admin privileges required'];
    }
    
    $target_id = validateInteger($target_id, 1);
    if ($target_id === false) {
        logFailedAdminAction($action_type, 'Invalid target ID', ['target_id' => $target_id]);
        return ['valid' => false, 'error' => 'Invalid target ID'];
    }
    
    // Additional validation based on action type
    switch ($action_type) {
        case 'stop_bid':
            // Check if bid exists and is active
            try {
                $bid = fetchOne("SELECT status FROM bids WHERE id = ?", [$target_id]);
                if (!$bid) {
                    logFailedAdminAction($action_type, 'Bid not found', ['bid_id' => $target_id]);
                    return ['valid' => false, 'error' => 'Bid not found'];
                }
                if ($bid['status'] !== 'active') {
                    logFailedAdminAction($action_type, 'Bid not active', ['bid_id' => $target_id, 'status' => $bid['status']]);
                    return ['valid' => false, 'error' => 'Bid is not active'];
                }
            } catch (Exception $e) {
                logFailedAdminAction($action_type, 'Database error: ' . $e->getMessage(), ['bid_id' => $target_id]);
                return ['valid' => false, 'error' => 'Database error'];
            }
            break;
            
        case 'end_auction':
        case 'cancel_auction':
            // Check if auction exists and is active
            try {
                $auction = fetchOne("SELECT status FROM items WHERE id = ?", [$target_id]);
                if (!$auction) {
                    logFailedAdminAction($action_type, 'Auction not found', ['item_id' => $target_id]);
                    return ['valid' => false, 'error' => 'Auction not found'];
                }
                if ($auction['status'] !== 'active') {
                    logFailedAdminAction($action_type, 'Auction not active', ['item_id' => $target_id, 'status' => $auction['status']]);
                    return ['valid' => false, 'error' => 'Auction is not active'];
                }
            } catch (Exception $e) {
                logFailedAdminAction($action_type, 'Database error: ' . $e->getMessage(), ['item_id' => $target_id]);
                return ['valid' => false, 'error' => 'Database error'];
            }
            break;
    }
    
    return ['valid' => true, 'error' => null];
}