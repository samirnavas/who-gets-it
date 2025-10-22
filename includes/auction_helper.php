<?php
/**
 * Auction Management Helper Functions
 * Provides utility functions for auction completion and status management
 */

// Include database connection and other helpers
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/bid_helper.php';
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/performance_helper.php';

/**
 * End an auction by admin action
 * @param int $item_id Item/auction ID to end
 * @param string $reason Reason for ending the auction
 * @return array Result with 'success' boolean, 'message' string, and 'winner_id' if applicable
 */
function endAuction($item_id, $reason = '') {
    // Only admins can end auctions
    if (!isAdmin()) {
        return ['success' => false, 'message' => 'Unauthorized access', 'winner_id' => null];
    }
    
    $admin_id = getCurrentUserId();
    
    try {
        // Start transaction
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        
        // Check if auction exists and is active
        $auction = getAuctionById($item_id);
        if (!$auction || $auction['status'] !== 'active') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Auction not found or already ended', 'winner_id' => null];
        }
        
        // Get highest active bid to determine winner
        $highest_bid = getHighestActiveBid($item_id);
        $winner_id = $highest_bid ? $highest_bid['user_id'] : null;
        
        // Update auction status to ended
        $sql = "UPDATE items SET status = 'ended', ended_at = NOW(), ended_by = ? WHERE id = ?";
        $stmt = executeQuery($sql, [$admin_id, $item_id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to end auction', 'winner_id' => null];
        }
        
        // Log admin action
        $log_sql = "INSERT INTO admin_actions (admin_id, action_type, target_id, reason) VALUES (?, 'end_auction', ?, ?)";
        executeQuery($log_sql, [$admin_id, $item_id, $reason]);
        
        $pdo->commit();
        
        // Send notifications to all participants
        notifyAuctionEnded($item_id, $winner_id);
        
        // Notify admin of completed action
        $action_details = $winner_id ? 
            "Ended auction #{$item_id} with winner (User #{$winner_id})" : 
            "Ended auction #{$item_id} with no valid bids";
        notifyAdminAction($admin_id, 'auction_ended', $action_details);
        
        $message = $winner_id ? 
            "Auction ended successfully. Winner: " . $highest_bid['username'] : 
            "Auction ended successfully. No valid bids found.";
            
        return ['success' => true, 'message' => $message, 'winner_id' => $winner_id];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error ending auction: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error ending auction. Please try again.', 'winner_id' => null];
    }
}

/**
 * Cancel an auction by admin action
 * @param int $item_id Item/auction ID to cancel
 * @param string $reason Reason for cancelling the auction
 * @return array Result with 'success' boolean and 'message' string
 */
function cancelAuction($item_id, $reason = '') {
    // Only admins can cancel auctions
    if (!isAdmin()) {
        return ['success' => false, 'message' => 'Unauthorized access'];
    }
    
    $admin_id = getCurrentUserId();
    
    try {
        // Start transaction
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        
        // Check if auction exists and is active
        $auction = getAuctionById($item_id);
        if (!$auction || $auction['status'] !== 'active') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Auction not found or already ended'];
        }
        
        // Update auction status to cancelled
        $sql = "UPDATE items SET status = 'cancelled', ended_at = NOW(), ended_by = ? WHERE id = ?";
        $stmt = executeQuery($sql, [$admin_id, $item_id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to cancel auction'];
        }
        
        // Log admin action (using end_auction type with cancelled status)
        $log_sql = "INSERT INTO admin_actions (admin_id, action_type, target_id, reason) VALUES (?, 'end_auction', ?, ?)";
        $cancel_reason = "CANCELLED: " . $reason;
        executeQuery($log_sql, [$admin_id, $item_id, $cancel_reason]);
        
        $pdo->commit();
        
        return ['success' => true, 'message' => 'Auction cancelled successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error cancelling auction: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error cancelling auction. Please try again.'];
    }
}

/**
 * Get auction/item by ID with full details
 * @param int $item_id Item ID
 * @return array|false Auction record or false if not found
 */
function getAuctionById($item_id) {
    try {
        $sql = "SELECT i.*, u.username as owner_username, 
                       winner.username as winner_username,
                       ended_user.username as ended_by_username
                FROM items i 
                JOIN users u ON i.user_id = u.id 
                LEFT JOIN users winner ON i.highest_bidder_id = winner.id
                LEFT JOIN users ended_user ON i.ended_by = ended_user.id
                WHERE i.id = ?";
        return fetchOne($sql, [$item_id]);
    } catch (Exception $e) {
        error_log("Error getting auction by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all auctions for admin management (with pagination) - Uses optimized version
 * @param int $page Page number (1-based)
 * @param int $per_page Items per page
 * @param string $status Filter by status ('all', 'active', 'ended', 'cancelled')
 * @param string $search Search term for item title or owner username
 * @return array Array with 'auctions' and 'total_count'
 */
function getAuctionsForAdmin($page = 1, $per_page = 20, $status = 'all', $search = '') {
    // Use optimized version from performance_helper.php
    return getOptimizedAuctionsForAdmin($page, $per_page, $status, $search);
}

/**
 * Check if an auction is active (not ended and not past end time)
 * @param int $item_id Item ID
 * @return bool True if auction is active, false otherwise
 */
function isAuctionActive($item_id) {
    $auction = getAuctionById($item_id);
    if (!$auction) {
        return false;
    }
    
    return $auction['status'] === 'active' && strtotime($auction['end_time']) > time();
}

/**
 * Check if an auction has naturally ended (past end time)
 * @param int $item_id Item ID
 * @return bool True if auction has naturally ended, false otherwise
 */
function hasAuctionNaturallyEnded($item_id) {
    $auction = getAuctionById($item_id);
    if (!$auction) {
        return false;
    }
    
    return strtotime($auction['end_time']) <= time();
}

/**
 * Auto-end auctions that have passed their end time
 * This function should be called periodically (e.g., via cron job)
 * @return array Array of ended auction IDs
 */
function autoEndExpiredAuctions() {
    try {
        // Find active auctions that have passed their end time
        $sql = "SELECT id FROM items WHERE status = 'active' AND end_time <= NOW()";
        $expired_auctions = fetchAll($sql);
        
        $ended_auction_ids = [];
        
        foreach ($expired_auctions as $auction) {
            // End each expired auction
            $result = endAuctionNaturally($auction['id']);
            if ($result['success']) {
                $ended_auction_ids[] = $auction['id'];
            }
        }
        
        return $ended_auction_ids;
        
    } catch (Exception $e) {
        error_log("Error auto-ending expired auctions: " . $e->getMessage());
        return [];
    }
}

/**
 * End an auction naturally (due to time expiration, not admin action)
 * @param int $item_id Item ID
 * @return array Result with 'success' boolean, 'message' string, and 'winner_id' if applicable
 */
function endAuctionNaturally($item_id) {
    try {
        // Start transaction
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        
        // Check if auction exists and is active
        $auction = getAuctionById($item_id);
        if (!$auction || $auction['status'] !== 'active') {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Auction not found or already ended', 'winner_id' => null];
        }
        
        // Get highest active bid to determine winner
        $highest_bid = getHighestActiveBid($item_id);
        $winner_id = $highest_bid ? $highest_bid['user_id'] : null;
        
        // Update auction status to ended (no admin involved)
        $sql = "UPDATE items SET status = 'ended', ended_at = NOW() WHERE id = ?";
        $stmt = executeQuery($sql, [$item_id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to end auction', 'winner_id' => null];
        }
        
        $pdo->commit();
        
        // Send notifications to all participants
        notifyAuctionEnded($item_id, $winner_id);
        
        $message = $winner_id ? 
            "Auction ended naturally. Winner: " . $highest_bid['username'] : 
            "Auction ended naturally. No valid bids found.";
            
        return ['success' => true, 'message' => $message, 'winner_id' => $winner_id];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error ending auction naturally: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error ending auction. Please try again.', 'winner_id' => null];
    }
}

/**
 * Get auction winner information
 * @param int $item_id Item ID
 * @return array|false Winner information or false if no winner
 */
function getAuctionWinner($item_id) {
    $auction = getAuctionById($item_id);
    if (!$auction || $auction['status'] === 'active') {
        return false;
    }
    
    if (!$auction['highest_bidder_id']) {
        return false;
    }
    
    // Get the winning bid details
    $highest_bid = getHighestActiveBid($item_id);
    if (!$highest_bid) {
        return false;
    }
    
    return [
        'user_id' => $auction['highest_bidder_id'],
        'username' => $auction['winner_username'],
        'winning_bid' => $highest_bid['bid_amount'],
        'bid_time' => $highest_bid['created_at'],
        'auction_ended_at' => $auction['ended_at'],
        'ended_by_admin' => $auction['ended_by'] !== null
    ];
}

/**
 * Get admin action log for a specific auction
 * @param int $item_id Item ID
 * @return array Array of admin action records
 */
function getAuctionAdminActions($item_id) {
    if (!isAdmin()) {
        return [];
    }
    
    try {
        $sql = "SELECT aa.*, u.username as admin_username 
                FROM admin_actions aa 
                JOIN users u ON aa.admin_id = u.id 
                WHERE aa.action_type = 'end_auction' AND aa.target_id = ? 
                ORDER BY aa.created_at DESC";
        return fetchAll($sql, [$item_id]);
    } catch (Exception $e) {
        error_log("Error getting auction admin actions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get auction statistics for admin dashboard - Uses optimized version
 * @return array Array with various auction statistics
 */
function getAuctionStatistics() {
    // Use optimized version from performance_helper.php
    return getOptimizedAuctionStatistics();
}