<?php
/**
 * Performance Optimization Helper Functions
 * Provides optimized queries and pagination strategies for large datasets
 */

require_once __DIR__ . '/../config/db_connect.php';

/**
 * Get optimized bid history for a user with efficient pagination
 * @param int $user_id User ID
 * @param int $page Page number
 * @param int $per_page Items per page
 * @param string $status_filter Status filter
 * @return array Paginated bid results
 */
function getOptimizedUserBids($user_id, $page = 1, $per_page = 20, $status_filter = 'all') {
    $offset = ($page - 1) * $per_page;
    
    // Build optimized query with covering indexes
    $where_conditions = ['b.user_id = ?'];
    $params = [$user_id];
    
    if ($status_filter !== 'all') {
        $where_conditions[] = 'b.status = ?';
        $params[] = $status_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Use covering index for better performance
    $sql = "SELECT 
                b.id,
                b.item_id,
                b.bid_amount,
                b.status,
                b.created_at,
                b.stopped_at,
                i.title as item_title,
                i.end_time,
                i.status as item_status,
                i.current_bid,
                i.highest_bidder_id,
                (CASE 
                    WHEN i.highest_bidder_id = b.user_id AND i.status = 'active' AND i.end_time > NOW() THEN 'winning'
                    WHEN i.highest_bidder_id = b.user_id AND (i.status = 'ended' OR i.end_time <= NOW()) THEN 'won'
                    WHEN b.status = 'stopped' THEN 'stopped'
                    WHEN i.status = 'ended' OR i.end_time <= NOW() THEN 'lost'
                    ELSE 'outbid'
                END) as bid_status
            FROM bids b
            INNER JOIN items i ON b.item_id = i.id
            WHERE {$where_clause}
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $bids = fetchAll($sql, $params);
    
    // Get total count efficiently
    $count_sql = "SELECT COUNT(*) as total FROM bids b WHERE {$where_clause}";
    $count_params = array_slice($params, 0, -2); // Remove LIMIT and OFFSET
    $total_result = fetchOne($count_sql, $count_params);
    $total_count = $total_result['total'];
    
    return [
        'bids' => $bids,
        'total_count' => $total_count,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_count / $per_page)
    ];
}

/**
 * Get optimized bids for admin interface with efficient filtering and pagination
 * @param int $page Page number
 * @param int $per_page Items per page
 * @param string $status_filter Status filter
 * @param string $search Search query
 * @return array Paginated admin bid results
 */
function getOptimizedBidsForAdmin($page = 1, $per_page = 20, $status_filter = 'all', $search = '') {
    $offset = ($page - 1) * $per_page;
    
    $where_conditions = [];
    $params = [];
    
    // Status filter
    if ($status_filter !== 'all') {
        $where_conditions[] = 'b.status = ?';
        $params[] = $status_filter;
    }
    
    // Search filter
    if (!empty($search)) {
        $where_conditions[] = '(i.title LIKE ? OR u.username LIKE ?)';
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);
    
    // Optimized query using covering indexes
    $sql = "SELECT 
                b.id,
                b.item_id,
                b.user_id,
                b.bid_amount,
                b.status,
                b.created_at,
                b.stopped_at,
                b.stopped_by,
                i.title as item_title,
                u.username,
                sb.username as stopped_by_username
            FROM bids b
            INNER JOIN items i ON b.item_id = i.id
            INNER JOIN users u ON b.user_id = u.id
            LEFT JOIN users sb ON b.stopped_by = sb.id
            WHERE {$where_clause}
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $bids = fetchAll($sql, $params);
    
    // Efficient count query
    $count_sql = "SELECT COUNT(*) as total 
                  FROM bids b
                  INNER JOIN items i ON b.item_id = i.id
                  INNER JOIN users u ON b.user_id = u.id
                  WHERE {$where_clause}";
    $count_params = array_slice($params, 0, -2);
    $total_result = fetchOne($count_sql, $count_params);
    $total_count = $total_result['total'];
    
    return [
        'bids' => $bids,
        'total_count' => $total_count,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_count / $per_page)
    ];
}

/**
 * Get optimized auctions for admin interface
 * @param int $page Page number
 * @param int $per_page Items per page
 * @param string $status_filter Status filter
 * @param string $search Search query
 * @return array Paginated admin auction results
 */
function getOptimizedAuctionsForAdmin($page = 1, $per_page = 20, $status_filter = 'all', $search = '') {
    $offset = ($page - 1) * $per_page;
    
    $where_conditions = [];
    $params = [];
    
    // Status filter with time consideration
    if ($status_filter === 'active') {
        $where_conditions[] = "(i.status = 'active' AND i.end_time > NOW())";
    } elseif ($status_filter === 'ended') {
        $where_conditions[] = "(i.status = 'ended' OR (i.status = 'active' AND i.end_time <= NOW()))";
    } elseif ($status_filter === 'cancelled') {
        $where_conditions[] = "i.status = 'cancelled'";
    }
    
    // Search filter
    if (!empty($search)) {
        $where_conditions[] = '(i.title LIKE ? OR u.username LIKE ?)';
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = empty($where_conditions) ? '1=1' : implode(' AND ', $where_conditions);
    
    // Optimized query with subqueries for bid counts
    $sql = "SELECT 
                i.id,
                i.title,
                i.current_bid,
                i.end_time,
                i.status,
                i.ended_at,
                i.ended_by,
                i.image_url,
                u.username as owner_username,
                hb.username as winner_username,
                eb.username as ended_by_username,
                COALESCE(active_bids.count, 0) as active_bid_count,
                COALESCE(stopped_bids.count, 0) as stopped_bid_count
            FROM items i
            INNER JOIN users u ON i.user_id = u.id
            LEFT JOIN users hb ON i.highest_bidder_id = hb.id
            LEFT JOIN users eb ON i.ended_by = eb.id
            LEFT JOIN (
                SELECT item_id, COUNT(*) as count 
                FROM bids 
                WHERE status = 'active' 
                GROUP BY item_id
            ) active_bids ON i.id = active_bids.item_id
            LEFT JOIN (
                SELECT item_id, COUNT(*) as count 
                FROM bids 
                WHERE status = 'stopped' 
                GROUP BY item_id
            ) stopped_bids ON i.id = stopped_bids.item_id
            WHERE {$where_clause}
            ORDER BY 
                CASE 
                    WHEN i.status = 'active' AND i.end_time <= NOW() THEN 1
                    WHEN i.status = 'active' THEN 2
                    ELSE 3
                END,
                i.end_time DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $auctions = fetchAll($sql, $params);
    
    // Efficient count query
    $count_sql = "SELECT COUNT(*) as total 
                  FROM items i
                  INNER JOIN users u ON i.user_id = u.id
                  WHERE {$where_clause}";
    $count_params = array_slice($params, 0, -2);
    $total_result = fetchOne($count_sql, $count_params);
    $total_count = $total_result['total'];
    
    return [
        'auctions' => $auctions,
        'total_count' => $total_count,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total_count / $per_page)
    ];
}

/**
 * Get optimized auction statistics
 * @return array Statistics array
 */
function getOptimizedAuctionStatistics() {
    // Use single query with conditional aggregation for better performance
    $sql = "SELECT 
                COUNT(CASE WHEN status = 'active' AND end_time > NOW() THEN 1 END) as auctions_active,
                COUNT(CASE WHEN status = 'ended' OR (status = 'active' AND end_time <= NOW()) THEN 1 END) as auctions_ended,
                COUNT(CASE WHEN status = 'active' AND end_time <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND end_time > NOW() THEN 1 END) as auctions_ending_soon,
                COUNT(CASE WHEN status = 'active' AND end_time <= NOW() THEN 1 END) as auctions_expired
            FROM items";
    
    return fetchOne($sql) ?: [
        'auctions_active' => 0,
        'auctions_ended' => 0,
        'auctions_ending_soon' => 0,
        'auctions_expired' => 0
    ];
}

/**
 * Get optimized recent admin actions
 * @param int $limit Number of actions to retrieve
 * @return array Recent admin actions
 */
function getOptimizedRecentAdminActions($limit = 10) {
    $sql = "SELECT 
                aa.id,
                aa.action_type,
                aa.target_id,
                aa.reason,
                aa.created_at,
                u.username as admin_username
            FROM admin_actions aa
            INNER JOIN users u ON aa.admin_id = u.id
            ORDER BY aa.created_at DESC
            LIMIT ?";
    
    return fetchAll($sql, [$limit]);
}

/**
 * Optimize database tables (run periodically)
 * @return array Results of optimization
 */
function optimizeDatabaseTables() {
    if (!isAdmin()) {
        return ['success' => false, 'error' => 'Admin privileges required'];
    }
    
    $tables = ['users', 'items', 'bids', 'admin_actions'];
    $results = [];
    
    try {
        foreach ($tables as $table) {
            $sql = "OPTIMIZE TABLE {$table}";
            executeQuery($sql);
            $results[$table] = 'optimized';
        }
        
        return ['success' => true, 'results' => $results];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get database performance statistics
 * @return array Performance statistics
 */
function getDatabasePerformanceStats() {
    if (!isAdmin()) {
        return [];
    }
    
    try {
        // Get table sizes
        $table_stats = fetchAll("
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");
        
        // Get index usage (MySQL 5.7+)
        $index_stats = [];
        try {
            $index_stats = fetchAll("
                SELECT 
                    object_schema,
                    object_name,
                    index_name,
                    count_read,
                    count_write
                FROM performance_schema.table_io_waits_summary_by_index_usage 
                WHERE object_schema = DATABASE()
                ORDER BY count_read DESC
                LIMIT 20
            ");
        } catch (Exception $e) {
            // Performance schema might not be available
        }
        
        return [
            'table_stats' => $table_stats,
            'index_stats' => $index_stats
        ];
    } catch (Exception $e) {
        error_log("Error getting database performance stats: " . $e->getMessage());
        return [];
    }
}