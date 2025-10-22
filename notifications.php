<?php
/**
 * User Notifications Page
 * Displays user notifications and allows marking as read
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';
require_once 'includes/notification_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = getCurrentUserId();

// Handle mark as read actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
        markNotificationRead($_POST['notification_id'], $user_id);
    } elseif (isset($_POST['mark_all_read'])) {
        markAllNotificationsRead($user_id);
    }
    
    // Redirect to prevent form resubmission
    header('Location: notifications.php');
    exit();
}

// Get notifications
$notifications = getUserNotifications($user_id, false, 100);
$unread_count = getUnreadNotificationCount($user_id);

// Set page title
$page_title = "Notifications";

// Include header
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
        <?php if ($unread_count > 0): ?>
            <form method="POST" class="inline">
                <button type="submit" name="mark_all_read" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Mark All Read (<?php echo $unread_count; ?>)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-12">
            <div class="text-gray-400 mb-4">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zm6 10V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
            <p class="text-gray-500">You don't have any notifications yet.</p>
        </div>
    <?php else: ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $is_unread = !$notification['is_read'];
                    $bg_class = $is_unread ? 'bg-blue-50' : 'bg-white';
                    
                    // Determine icon based on notification type
                    $icon_class = 'text-gray-400';
                    $icon_svg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
                    
                    switch ($notification['type']) {
                        case 'bid_stopped':
                            $icon_class = 'text-red-500';
                            $icon_svg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />';
                            break;
                        case 'auction_won':
                            $icon_class = 'text-green-500';
                            $icon_svg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />';
                            break;
                        case 'auction_lost':
                            $icon_class = 'text-gray-500';
                            $icon_svg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
                            break;
                        case 'outbid':
                            $icon_class = 'text-yellow-500';
                            $icon_svg = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />';
                            break;
                    }
                    ?>
                    <li class="<?php echo $bg_class; ?>">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 <?php echo $icon_class; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <?php echo $icon_svg; ?>
                                        </svg>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </p>
                                            <?php if ($is_unread): ?>
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    New
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-600">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <p class="mt-2 text-xs text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if ($notification['related_id']): ?>
                                        <a href="item.php?id=<?php echo $notification['related_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            View Item
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($is_unread): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" 
                                                    class="text-gray-400 hover:text-gray-600 text-sm">
                                                Mark Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>