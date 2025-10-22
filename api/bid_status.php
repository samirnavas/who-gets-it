<?php
/**
 * API endpoint for real-time bid status updates
 * Returns JSON data for AJAX polling
 */

session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helper.php';
require_once '../includes/bid_helper.php';

// Set JSON content type
header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$user_id = getCurrentUserId();

try {
    // Get request parameters
    $status_filter = $_GET['status'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 10;
    
    // Get user bids
    $bid_data = getUserBids($user_id, $page, $per_page, $status_filter);
    $bids = $bid_data['bids'];
    
    // Prepare response data
    $response_bids = [];
    foreach ($bids as $bid) {
        $end_time = strtotime($bid['end_time']);
        $now = time();
        $time_remaining = $end_time - $now;
        
        $response_bids[] = [
            'id' => $bid['id'],
            'item_id' => $bid['item_id'],
            'bid_amount' => $bid['bid_amount'],
            'current_bid' => $bid['current_bid'],
            'status' => $bid['status'],
            'item_status' => $bid['item_status'],
            'highest_bidder_id' => $bid['highest_bidder_id'],
            'end_time' => $bid['end_time'],
            'time_remaining' => $time_remaining,
            'status_display' => $bid['bid_status_display'],
            'stopped_at' => $bid['stopped_at'],
            'stopped_by_username' => $bid['stopped_by_username']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'bids' => $response_bids,
        'total_count' => $bid_data['total_count'],
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Error in bid status API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>