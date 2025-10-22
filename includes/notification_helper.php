<?php
/**
 * Notification System Helper Functions
 * Provides utility functions for user notifications and email notifications
 */

// Include database connection and other helpers
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Create a user notification
 * @param int $user_id User ID to notify
 * @param string $type Notification type ('bid_stopped', 'auction_won', 'auction_lost', 'outbid', 'admin_action')
 * @param string $title Notification title
 * @param string $message Notification message
 * @param int|null $related_id Related item/bid/auction ID
 * @return bool True if successful, false otherwise
 */
function createNotification($user_id, $type, $title, $message, $related_id = null) {
    try {
        $sql = "INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, ?, ?, ?, ?)";
        executeQuery($sql, [$user_id, $type, $title, $message, $related_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notifications for a user
 * @param int $user_id User ID
 * @param bool $unread_only Whether to get only unread notifications
 * @param int $limit Maximum number of notifications to return
 * @return array Array of notification records
 */
function getUserNotifications($user_id, $unread_only = false, $limit = 50) {
    try {
        $where_clause = "WHERE user_id = ?";
        $params = [$user_id];
        
        if ($unread_only) {
            $where_clause .= " AND is_read = 0";
        }
        
        $sql = "SELECT * FROM notifications 
                {$where_clause}
                ORDER BY created_at DESC 
                LIMIT ?";
        $params[] = $limit;
        
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Error getting user notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security)
 * @return bool True if successful, false otherwise
 */
function markNotificationRead($notification_id, $user_id) {
    try {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = executeQuery($sql, [$notification_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * @param int $user_id User ID
 * @return bool True if successful, false otherwise
 */
function markAllNotificationsRead($user_id) {
    try {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
        executeQuery($sql, [$user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for a user
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($user_id) {
    try {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $result = fetchOne($sql, [$user_id]);
        return $result ? $result['count'] : 0;
    } catch (Exception $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send email notification
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @return bool True if successful, false otherwise
 */
function sendEmailNotification($to_email, $subject, $message) {
    try {
        // Basic email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: College Auction <noreply@collegeauction.com>',
            'Reply-To: noreply@collegeauction.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Send email using PHP's mail function
        $success = mail($to_email, $subject, $message, implode("\r\n", $headers));
        
        if (!$success) {
            error_log("Failed to send email to: " . $to_email);
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Error sending email notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify user when their bid is stopped
 * @param int $bid_id Bid ID that was stopped
 * @param string $reason Reason for stopping the bid
 * @return bool True if successful, false otherwise
 */
function notifyBidStopped($bid_id, $reason = '') {
    try {
        // Get bid and user information
        $sql = "SELECT b.*, u.email, u.username, i.title as item_title 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                JOIN items i ON b.item_id = i.id 
                WHERE b.id = ?";
        $bid = fetchOne($sql, [$bid_id]);
        
        if (!$bid) {
            return false;
        }
        
        // Create in-app notification
        $title = "Bid Stopped";
        $message = "Your bid of $" . number_format($bid['bid_amount'], 2) . " on \"" . $bid['item_title'] . "\" has been stopped by an administrator.";
        if (!empty($reason)) {
            $message .= " Reason: " . $reason;
        }
        
        createNotification($bid['user_id'], 'bid_stopped', $title, $message, $bid['item_id']);
        
        // Send email notification
        $email_subject = "Your Bid Has Been Stopped - College Auction";
        $email_message = "
        <html>
        <body>
            <h2>Bid Stopped Notification</h2>
            <p>Dear {$bid['username']},</p>
            <p>Your bid of <strong>$" . number_format($bid['bid_amount'], 2) . "</strong> on the item <strong>\"{$bid['item_title']}\"</strong> has been stopped by an administrator.</p>
            " . (!empty($reason) ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "") . "
            <p>You will not be able to place new bids on this item.</p>
            <p>If you have questions about this action, please contact the administrators.</p>
            <br>
            <p>Best regards,<br>College Auction Team</p>
        </body>
        </html>";
        
        sendEmailNotification($bid['email'], $email_subject, $email_message);
        
        return true;
    } catch (Exception $e) {
        error_log("Error notifying bid stopped: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify users when an auction ends
 * @param int $item_id Item/auction ID that ended
 * @param int|null $winner_id Winner user ID (null if no winner)
 * @return bool True if successful, false otherwise
 */
function notifyAuctionEnded($item_id, $winner_id = null) {
    try {
        // Get auction information
        $auction_sql = "SELECT i.*, u.username as seller_username, u.email as seller_email
                       FROM items i 
                       JOIN users u ON i.user_id = u.id 
                       WHERE i.id = ?";
        $auction = fetchOne($auction_sql, [$item_id]);
        
        if (!$auction) {
            return false;
        }
        
        // Get all active bidders for this auction
        $bidders_sql = "SELECT DISTINCT b.user_id, u.username, u.email, 
                               MAX(b.bid_amount) as highest_bid
                        FROM bids b 
                        JOIN users u ON b.user_id = u.id 
                        WHERE b.item_id = ? AND b.status = 'active'
                        GROUP BY b.user_id, u.username, u.email";
        $bidders = fetchAll($bidders_sql, [$item_id]);
        
        // Notify each bidder
        foreach ($bidders as $bidder) {
            $is_winner = ($winner_id && $bidder['user_id'] == $winner_id);
            
            if ($is_winner) {
                // Winner notification
                $title = "Congratulations! You Won the Auction";
                $message = "You have won the auction for \"{$auction['title']}\" with your bid of $" . number_format($bidder['highest_bid'], 2) . "!";
                
                createNotification($bidder['user_id'], 'auction_won', $title, $message, $item_id);
                
                // Winner email
                $email_subject = "Congratulations! You Won the Auction - College Auction";
                $email_message = "
                <html>
                <body>
                    <h2>🎉 Congratulations! You Won!</h2>
                    <p>Dear {$bidder['username']},</p>
                    <p>Congratulations! You have won the auction for <strong>\"{$auction['title']}\"</strong>!</p>
                    <p><strong>Your winning bid:</strong> $" . number_format($bidder['highest_bid'], 2) . "</p>
                    <p><strong>Auction ended:</strong> " . date('M j, Y g:i A') . "</p>
                    <p>Please contact the seller to arrange pickup or delivery of your item.</p>
                    <p><strong>Seller:</strong> {$auction['seller_username']}</p>
                    <br>
                    <p>Thank you for using College Auction!</p>
                    <p>Best regards,<br>College Auction Team</p>
                </body>
                </html>";
                
                sendEmailNotification($bidder['email'], $email_subject, $email_message);
            } else {
                // Loser notification
                $title = "Auction Ended";
                $message = "The auction for \"{$auction['title']}\" has ended. Unfortunately, your bid was not the winning bid.";
                
                createNotification($bidder['user_id'], 'auction_lost', $title, $message, $item_id);
                
                // Loser email
                $email_subject = "Auction Ended - College Auction";
                $email_message = "
                <html>
                <body>
                    <h2>Auction Ended</h2>
                    <p>Dear {$bidder['username']},</p>
                    <p>The auction for <strong>\"{$auction['title']}\"</strong> has ended.</p>
                    <p>Unfortunately, your bid of $" . number_format($bidder['highest_bid'], 2) . " was not the winning bid.</p>
                    <p>Thank you for participating! We hope you'll find other great items to bid on.</p>
                    <br>
                    <p>Best regards,<br>College Auction Team</p>
                </body>
                </html>";
                
                sendEmailNotification($bidder['email'], $email_subject, $email_message);
            }
        }
        
        // Notify seller
        if ($winner_id) {
            $winner_info = fetchOne("SELECT username FROM users WHERE id = ?", [$winner_id]);
            $seller_title = "Your Auction Has Ended Successfully";
            $seller_message = "Your auction for \"{$auction['title']}\" has ended successfully. Winner: {$winner_info['username']}";
        } else {
            $seller_title = "Your Auction Has Ended";
            $seller_message = "Your auction for \"{$auction['title']}\" has ended with no valid bids.";
        }
        
        createNotification($auction['user_id'], 'auction_ended', $seller_title, $seller_message, $item_id);
        
        return true;
    } catch (Exception $e) {
        error_log("Error notifying auction ended: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify user when they are outbid
 * @param int $item_id Item ID
 * @param int $outbid_user_id User who was outbid
 * @param float $new_bid_amount New highest bid amount
 * @return bool True if successful, false otherwise
 */
function notifyOutbid($item_id, $outbid_user_id, $new_bid_amount) {
    try {
        // Get item and user information
        $sql = "SELECT i.title, u.username, u.email 
                FROM items i, users u 
                WHERE i.id = ? AND u.id = ?";
        $info = fetchOne($sql, [$item_id, $outbid_user_id]);
        
        if (!$info) {
            return false;
        }
        
        // Create notification
        $title = "You've Been Outbid";
        $message = "Someone has placed a higher bid on \"{$info['title']}\". The current bid is now $" . number_format($new_bid_amount, 2) . ".";
        
        createNotification($outbid_user_id, 'outbid', $title, $message, $item_id);
        
        // Send email notification
        $email_subject = "You've Been Outbid - College Auction";
        $email_message = "
        <html>
        <body>
            <h2>You've Been Outbid</h2>
            <p>Dear {$info['username']},</p>
            <p>Someone has placed a higher bid on <strong>\"{$info['title']}\"</strong>.</p>
            <p><strong>Current highest bid:</strong> $" . number_format($new_bid_amount, 2) . "</p>
            <p>If you're still interested in this item, you can place a higher bid before the auction ends.</p>
            <p><a href=\"" . $_SERVER['HTTP_HOST'] . "/item.php?id={$item_id}\" style=\"background-color: #3B82F6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">View Item & Bid</a></p>
            <br>
            <p>Best regards,<br>College Auction Team</p>
        </body>
        </html>";
        
        sendEmailNotification($info['email'], $email_subject, $email_message);
        
        return true;
    } catch (Exception $e) {
        error_log("Error notifying outbid: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify admin of completed actions
 * @param int $admin_id Admin user ID
 * @param string $action Action performed ('bid_stopped', 'auction_ended')
 * @param string $details Action details
 * @return bool True if successful, false otherwise
 */
function notifyAdminAction($admin_id, $action, $details) {
    try {
        $title = "Admin Action Completed";
        $message = "Action: {$action}. Details: {$details}";
        
        createNotification($admin_id, 'admin_action', $title, $message);
        
        return true;
    } catch (Exception $e) {
        error_log("Error notifying admin action: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up old notifications (older than 30 days)
 * @return bool True if successful, false otherwise
 */
function cleanupOldNotifications() {
    try {
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        executeQuery($sql);
        return true;
    } catch (Exception $e) {
        error_log("Error cleaning up old notifications: " . $e->getMessage());
        return false;
    }
}