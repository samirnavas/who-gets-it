<?php
/**
 * Bid Management Helper Functions
 * Provides utility functions for bid status tracking and management
 */

// Include database connection and auth helper
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/performance_helper.php';

/**
 * Stop a bid by admin action
 * @param int $bid_id Bid ID to stop
 * @param string $reason Reason for stopping the bid
 * @return bool True if successful, false otherwise
 */
function stopBid($bid_id, $reason = '') {
    // Only admins can stop bids
    if (!isAdmin()) {
        return false;
    }
    
    $admin_id = getCurrentUserId();
    
    try {
        // Start transaction
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        
        // Check if bid exists and is active
        $bid = getBidById($bid_id);
        if (!$bid || $bid['status'] !== 'active') {
            $pdo->rollBack();
            return false;
        }
        
        // Update bid status to stopped
        $sql = "UPDATE bids SET status = 'stopped', stopped_at = NOW(), stopped_by = ? WHERE id = ?";
        $stmt = executeQuery($sql, [$admin_id, $bid_id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }
        
        // Log admin action
        $log_sql = "INSERT INTO admin_actions (admin_id, action_type, target_id, reason) VALUES (?, 'stop_bid', ?, ?)";
        executeQuery($log_sql, [$admin_id, $bid_id, $reason]);
        
        // Update item's current bid if this was the highest bid
        updateItemCurrentBid($bid['item_id']);
        
        $pdo->commit();
        
        // Send notification to the user whose bid was stopped
        notifyBidStopped($bid_id, $reason);
        
        // Notify admin of completed action
        notifyAdminAction($admin_id, 'bid_stopped', "Stopped bid #{$bid_id} for item #{$bid['item_id']}");
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error stopping bid: " . $e->getMessage());
        return false;
    }
}

/**
 * Get bid by ID
 * @param int $bid_id Bid ID
 * @return array|false Bid record or false if not found
 */
function getBidById($bid_id) {
    try {
        $sql = "SELECT b.*, u.username, i.title as item_title 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                JOIN items i ON b.item_id = i.id 
                WHERE b.id = ?";
        return fetchOne($sql, [$bid_id]);
    } catch (Exception $e) {
        error_log("Error getting bid by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all bids for an item (only active bids)
 * @param int $item_id Item ID
 * @return array Array of active bid records
 */
function getActiveBidsForItem($item_id) {
    try {
        $sql = "SELECT b.*, u.username 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.item_id = ? AND b.status = 'active' 
                ORDER BY b.bid_amount DESC, b.created_at ASC";
        return fetchAll($sql, [$item_id]);
    } catch (Exception $e) {
        error_log("Error getting active bids for item: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all bids for an item (including stopped bids)
 * @param int $item_id Item ID
 * @return array Array of all bid records
 */
function getAllBidsForItem($item_id) {
    try {
        $sql = "SELECT b.*, u.username, 
                       stopped_user.username as stopped_by_username
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                LEFT JOIN users stopped_user ON b.stopped_by = stopped_user.id
                WHERE b.item_id = ? 
                ORDER BY b.bid_amount DESC, b.created_at ASC";
        return fetchAll($sql, [$item_id]);
    } catch (Exception $e) {
        error_log("Error getting all bids for item: " . $e->getMessage());
        return [];
    }
}

/**
 * Get highest active bid for an item
 * @param int $item_id Item ID
 * @return array|false Highest active bid record or false if none
 */
function getHighestActiveBid($item_id) {
    try {
        $sql = "SELECT b.*, u.username 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.item_id = ? AND b.status = 'active' 
                ORDER BY b.bid_amount DESC, b.created_at ASC 
                LIMIT 1";
        return fetchOne($sql, [$item_id]);
    } catch (Exception $e) {
        error_log("Error getting highest active bid: " . $e->getMessage());
        return false;
    }
}

/**
 * Update item's current bid and highest bidder based on active bids
 * @param int $item_id Item ID
 * @return bool True if successful, false otherwise
 */
function updateItemCurrentBid($item_id) {
    try {
        $highest_bid = getHighestActiveBid($item_id);
        
        if ($highest_bid) {
            // Update with highest active bid
            $sql = "UPDATE items SET current_bid = ?, highest_bidder_id = ? WHERE id = ?";
            executeQuery($sql, [$highest_bid['bid_amount'], $highest_bid['user_id'], $item_id]);
        } else {
            // No active bids, reset to starting bid
            $sql = "UPDATE items SET current_bid = starting_bid, highest_bidder_id = NULL WHERE id = ?";
            executeQuery($sql, [$item_id]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error updating item current bid: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a bid is valid (active and not stopped)
 * @param int $bid_id Bid ID
 * @return bool True if bid is valid, false otherwise
 */
function isBidValid($bid_id) {
    $bid = getBidById($bid_id);
    return $bid && $bid['status'] === 'active';
}

/**
 * Validate new bid placement (excludes stopped bids from calculations)
 * @param int $item_id Item ID
 * @param float $bid_amount Proposed bid amount
 * @param int $user_id User placing the bid
 * @return array Validation result with 'valid' boolean and 'message' string
 */
function validateNewBid($item_id, $bid_amount, $user_id) {
    try {
        // Get item details - only allow bidding on active auctions
        $item_sql = "SELECT * FROM items WHERE id = ?";
        $item = fetchOne($item_sql, [$item_id]);
        
        if (!$item) {
            return ['valid' => false, 'message' => 'Item not found'];
        }
        
        // Check auction status - must be active
        if ($item['status'] !== 'active') {
            $status_message = $item['status'] === 'ended' ? 'completed' : $item['status'];
            return ['valid' => false, 'message' => "Auction has been {$status_message}"];
        }
        
        // Check if auction has naturally ended (time expired)
        if (strtotime($item['end_time']) <= time()) {
            return ['valid' => false, 'message' => 'Auction has ended'];
        }
        
        // Check if user is the item owner
        if ($item['user_id'] == $user_id) {
            return ['valid' => false, 'message' => 'You cannot bid on your own item'];
        }
        
        // Get highest active bid (excluding stopped bids)
        $highest_bid = getHighestActiveBid($item_id);
        $minimum_bid = $highest_bid ? $highest_bid['bid_amount'] + 0.01 : $item['starting_bid'];
        
        // Check if bid amount is sufficient
        if ($bid_amount < $minimum_bid) {
            $formatted_min = number_format($minimum_bid, 2);
            return ['valid' => false, 'message' => "Bid must be at least ${formatted_min}"];
        }
        
        // Check if user has any stopped bids on this item
        $stopped_bid_sql = "SELECT COUNT(*) as count FROM bids WHERE item_id = ? AND user_id = ? AND status = 'stopped'";
        $stopped_count = fetchOne($stopped_bid_sql, [$item_id, $user_id]);
        
        if ($stopped_count && $stopped_count['count'] > 0) {
            return ['valid' => false, 'message' => 'You cannot place new bids on this item due to previous bid restrictions'];
        }
        
        return ['valid' => true, 'message' => 'Bid is valid'];
        
    } catch (Exception $e) {
        error_log("Error validating new bid: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Error validating bid. Please try again.'];
    }
}

/**
 * Get all bids for admin management (with pagination) - Uses optimized version
 * @param int $page Page number (1-based)
 * @param int $per_page Items per page
 * @param string $status Filter by status ('all', 'active', 'stopped')
 * @param string $search Search term for item title or username
 * @return array Array with 'bids' and 'total_count'
 */
function getBidsForAdmin($page = 1, $per_page = 20, $status = 'all', $search = '') {
    // Use optimized version from performance_helper.php
    return getOptimizedBidsForAdmin($page, $per_page, $status, $search);
}

/**
 * Get all bids for a specific user (with pagination and filtering) - Uses optimized version
 * @param int $user_id User ID
 * @param int $page Page number (1-based)
 * @param int $per_page Items per page
 * @param string $status Filter by status ('all', 'active', 'stopped', 'won', 'lost')
 * @return array Array with 'bids' and 'total_count'
 */
function getUserBids($user_id, $page = 1, $per_page = 20, $status = 'all') {
    // Use optimized version from performance_helper.php
    $result = getOptimizedUserBids($user_id, $page, $per_page, $status);
    
    // Add status information for each bid
    foreach ($result['bids'] as &$bid) {
        $bid['bid_status_display'] = getBidStatusDisplay($bid);
    }
    
    return $result;
}

/**
 * Get display status for a bid
 * @param array $bid Bid record with item information
 * @return array Status information with 'status', 'class', and 'message'
 */
function getBidStatusDisplay($bid) {
    $now = time();
    $end_time = strtotime($bid['end_time']);
    
    // Check if bid was stopped
    if ($bid['status'] === 'stopped') {
        return [
            'status' => 'stopped',
            'class' => 'bg-red-100 text-red-800',
            'message' => 'Bid Stopped'
        ];
    }
    
    // Check if auction ended or was cancelled
    if ($bid['item_status'] === 'ended' || $bid['item_status'] === 'cancelled') {
        if ($bid['highest_bidder_id'] == $bid['user_id']) {
            return [
                'status' => 'won',
                'class' => 'bg-green-100 text-green-800',
                'message' => 'Won'
            ];
        } else {
            return [
                'status' => 'lost',
                'class' => 'bg-gray-100 text-gray-800',
                'message' => 'Lost'
            ];
        }
    }
    
    // Check if auction naturally ended (time expired)
    if ($end_time <= $now) {
        if ($bid['highest_bidder_id'] == $bid['user_id']) {
            return [
                'status' => 'won',
                'class' => 'bg-green-100 text-green-800',
                'message' => 'Won'
            ];
        } else {
            return [
                'status' => 'lost',
                'class' => 'bg-gray-100 text-gray-800',
                'message' => 'Lost'
            ];
        }
    }
    
    // Active auction - check if winning
    if ($bid['highest_bidder_id'] == $bid['user_id']) {
        return [
            'status' => 'winning',
            'class' => 'bg-blue-100 text-blue-800',
            'message' => 'Winning'
        ];
    } else {
        return [
            'status' => 'outbid',
            'class' => 'bg-yellow-100 text-yellow-800',
            'message' => 'Outbid'
        ];
    }
}

/**
 * Place a new bid with full validation and error handling
 * @param int $item_id Item ID
 * @param float $bid_amount Bid amount
 * @param int $user_id User placing the bid
 * @return array Result with 'success' boolean, 'message' string, and 'bid_id' if successful
 */
function placeBid($item_id, $bid_amount, $user_id) {
    try {
        // Validate the bid first
        $validation = validateNewBid($item_id, $bid_amount, $user_id);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message'], 'bid_id' => null];
        }
        
        // Start transaction
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        
        // Double-check auction is still active (race condition protection)
        $item_check_sql = "SELECT status, end_time FROM items WHERE id = ?";
        $item_check = fetchOne($item_check_sql, [$item_id]);
        
        if (!$item_check || $item_check['status'] !== 'active' || strtotime($item_check['end_time']) <= time()) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Auction is no longer active', 'bid_id' => null];
        }
        
        // Get the current highest bidder before placing new bid (for outbid notification)
        $previous_highest_bid = getHighestActiveBid($item_id);
        $previous_highest_bidder_id = $previous_highest_bid ? $previous_highest_bid['user_id'] : null;
        
        // Insert bid into bids table (status defaults to 'active')
        $insert_bid_sql = "INSERT INTO bids (item_id, user_id, bid_amount) VALUES (?, ?, ?)";
        executeQuery($insert_bid_sql, [$item_id, $user_id, $bid_amount]);
        $bid_id = $pdo->lastInsertId();
        
        // Update item with new current bid and highest bidder
        $update_success = updateItemCurrentBid($item_id);
        if (!$update_success) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to update auction status', 'bid_id' => null];
        }
        
        // Commit transaction first
        $pdo->commit();
        
        // Send outbid notification to previous highest bidder (if any and different user)
        if ($previous_highest_bidder_id && $previous_highest_bidder_id != $user_id) {
            notifyOutbid($item_id, $previous_highest_bidder_id, $bid_amount);
        }
        
        return [
            'success' => true, 
            'message' => 'Bid placed successfully', 
            'bid_id' => $bid_id
        ];
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error placing bid: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to place bid. Please try again.', 'bid_id' => null];
    }
}

/**
 * Get admin action log for a specific bid
 * @param int $bid_id Bid ID
 * @return array Array of admin action records
 */
function getBidAdminActions($bid_id) {
    if (!isAdmin()) {
        return [];
    }
    
    try {
        $sql = "SELECT aa.*, u.username as admin_username 
                FROM admin_actions aa 
                JOIN users u ON aa.admin_id = u.id 
                WHERE aa.action_type = 'stop_bid' AND aa.target_id = ? 
                ORDER BY aa.created_at DESC";
        return fetchAll($sql, [$bid_id]);
    } catch (Exception $e) {
        error_log("Error getting bid admin actions: " . $e->getMessage());
        return [];
    }
}