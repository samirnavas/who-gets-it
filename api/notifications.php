<?php
/**
 * Notifications API Endpoint
 * Provides JSON API for notification data
 */

session_start();
require_once '../config/db_connect.php';
require_once '../includes/auth_helper.php';
require_once '../includes/notification_helper.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = getCurrentUserId();

try {
    $action = $_GET['action'] ?? 'get_notifications';
    
    switch ($action) {
        case 'get_notifications':
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $notifications = getUserNotifications($user_id, $unread_only, $limit);
            $unread_count = getUnreadNotificationCount($user_id);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            break;
            
        case 'get_unread_count':
            $unread_count = getUnreadNotificationCount($user_id);
            echo json_encode([
                'success' => true,
                'unread_count' => $unread_count
            ]);
            break;
            
        case 'mark_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $notification_id = $input['notification_id'] ?? null;
            
            if (!$notification_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                exit();
            }
            
            $success = markNotificationRead($notification_id, $user_id);
            echo json_encode(['success' => $success]);
            break;
            
        case 'mark_all_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit();
            }
            
            $success = markAllNotificationsRead($user_id);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("Notifications API error: " . $e->getMessage());
}
?>