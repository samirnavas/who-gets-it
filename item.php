<?php
/**
 * Item Detail Page
 * Displays complete item information and bidding functionality
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';
require_once 'includes/bid_helper.php';
require_once 'includes/auction_helper.php';

// Get item ID from URL parameter
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch item details with seller information
try {
    $sql = "SELECT i.*, u.username as seller_username 
            FROM items i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.id = ?";
    $item = fetchOne($sql, [$item_id]);
    
    if (!$item) {
        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    $error_message = "Unable to load item details. Please try again later.";
}

// Check if auction is still active (using new helper function)
$is_active = isAuctionActive($item_id);

// Get auction winner information if auction has ended
$winner_info = null;
if (!$is_active || $item['status'] !== 'active') {
    $winner_info = getAuctionWinner($item_id);
}

// Initialize variables for form handling
$bid_error = '';
$bid_success = '';

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    if (!isLoggedIn()) {
        $bid_error = 'You must be logged in to place a bid.';
    } else {
        $bid_amount = isset($_POST['bid_amount']) ? (float)$_POST['bid_amount'] : 0;
        
        // Validate bid amount
        if ($bid_amount <= 0) {
            $bid_error = 'Please enter a valid bid amount.';
        } else {
            // Use centralized bid placement function that handles all validation and updates
            $result = placeBid($item_id, $bid_amount, getCurrentUserId());
            
            if (!$result['success']) {
                $bid_error = $result['message'];
            } else {
                // Refresh item data for display
                $item_sql = "SELECT i.*, u.username as seller_username 
                            FROM items i 
                            JOIN users u ON i.user_id = u.id 
                            WHERE i.id = ?";
                $item = fetchOne($item_sql, [$item_id]);
                
                $bid_success = 'Your bid of $' . htmlspecialchars(number_format($bid_amount, 2)) . ' has been placed successfully!';
            }
        }
    }
}

// Set page title for header
$page_title = htmlspecialchars($item['title']);

// Include header
include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (isset($error_message)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($bid_error)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800"><?php echo htmlspecialchars($bid_error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($bid_success)): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p class="text-green-800"><?php echo htmlspecialchars($bid_success); ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Item Image -->
        <div class="space-y-4">
            <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden">
                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                     class="w-full h-96 object-cover"
                     onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
            </div>
        </div>

        <!-- Item Information -->
        <div class="space-y-6">
            <!-- Title and Status -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <?php echo htmlspecialchars($item['title']); ?>
                </h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        Listed by <?php echo htmlspecialchars($item['seller_username']); ?>
                    </span>
                    <?php if ($is_active): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    <?php elseif ($item['status'] === 'ended'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Completed
                        </span>
                    <?php elseif ($item['status'] === 'cancelled'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Cancelled
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Expired
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Current Bid / Winner Information -->
            <?php if ($winner_info): ?>
                <!-- Winner Information for Completed Auctions -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm font-medium text-green-800">Auction Winner</div>
                    </div>
                    <div class="text-2xl font-bold text-green-700 mb-2">
                        <?php echo htmlspecialchars($winner_info['username']); ?>
                    </div>
                    <div class="text-lg font-semibold text-green-600 mb-1">
                        Winning Bid: $<?php echo number_format($winner_info['winning_bid'], 2); ?>
                    </div>
                    <div class="text-sm text-green-600">
                        Won on <?php echo date('M j, Y g:i A', strtotime($winner_info['auction_ended_at'])); ?>
                    </div>
                    <?php if ($winner_info['ended_by_admin']): ?>
                        <div class="text-xs text-green-500 mt-1">
                            <em>Auction ended by administrator</em>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!$is_active && $item['status'] === 'ended'): ?>
                <!-- No Winner for Ended Auctions -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">Auction Result</div>
                    <div class="text-lg font-medium text-gray-700">
                        No Valid Bids
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Ended on <?php echo date('M j, Y g:i A', strtotime($item['ended_at'] ?? $item['end_time'])); ?>
                    </div>
                </div>
            <?php elseif (!$is_active && $item['status'] === 'cancelled'): ?>
                <!-- Cancelled Auction -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">Auction Status</div>
                    <div class="text-lg font-medium text-gray-700">
                        Cancelled
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Cancelled on <?php echo date('M j, Y g:i A', strtotime($item['ended_at'] ?? $item['end_time'])); ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Current Bid for Active Auctions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">Current Bid</div>
                    <div class="text-3xl font-bold text-green-600">
                        $<?php echo number_format($item['current_bid'], 2); ?>
                    </div>
                    <?php if ($item['highest_bidder_id']): ?>
                        <?php
                        $highest_bidder = fetchOne("SELECT username FROM users WHERE id = ?", [$item['highest_bidder_id']]);
                        ?>
                        <div class="text-sm text-gray-500 mt-1">
                            Highest bidder: <?php echo htmlspecialchars($highest_bidder['username']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Countdown Timer / Completion Info -->
            <?php if ($is_active): ?>
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600 mb-1">Time Remaining</div>
                    <div id="countdown-timer" class="text-2xl font-bold text-blue-800">
                        Loading...
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">
                        <?php if ($item['status'] === 'ended'): ?>
                            Auction Completed
                        <?php elseif ($item['status'] === 'cancelled'): ?>
                            Auction Cancelled
                        <?php else: ?>
                            Auction Expired
                        <?php endif; ?>
                    </div>
                    <div class="text-lg font-medium text-gray-700">
                        <?php 
                        $end_date = $item['ended_at'] ?? $item['end_time'];
                        echo date('M j, Y g:i A', strtotime($end_date)); 
                        ?>
                    </div>
                    <?php if ($winner_info): ?>
                        <div class="text-sm text-gray-500 mt-1">
                            Duration: <?php 
                            $start = strtotime($item['created_at']);
                            $end = strtotime($winner_info['auction_ended_at']);
                            $duration = $end - $start;
                            $days = floor($duration / (24 * 60 * 60));
                            $hours = floor(($duration % (24 * 60 * 60)) / (60 * 60));
                            echo $days > 0 ? "$days days, $hours hours" : "$hours hours";
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Description</h3>
                <p class="text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </p>
            </div>

            <!-- Bidding Form -->
            <?php if ($is_active && isLoggedIn()): ?>
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Place Your Bid</h3>
                    
                    <?php if (getCurrentUserId() == $item['user_id']): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-yellow-800">You cannot bid on your own item.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" class="space-y-4">
                            <div>
                                <label for="bid_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Bid Amount (minimum: $<?php echo number_format($item['current_bid'] + 0.01, 2); ?>)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        id="bid_amount" 
                                        name="bid_amount" 
                                        step="0.01" 
                                        min="<?php echo htmlspecialchars($item['current_bid'] + 0.01); ?>"
                                        class="block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="<?php echo number_format($item['current_bid'] + 1, 2); ?>"
                                        required
                                    >
                                </div>
                            </div>
                            
                            <button 
                                type="submit" 
                                name="place_bid"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200"
                            >
                                Place Bid
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php elseif ($is_active && !isLoggedIn()): ?>
                <div class="border-t pt-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-blue-800">
                            <a href="auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="font-medium hover:underline">
                                Sign in
                            </a> 
                            to place a bid on this item.
                        </p>
                    </div>
                </div>
            <?php elseif (!$is_active): ?>
                <div class="border-t pt-6">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <?php if ($item['status'] === 'ended'): ?>
                            <?php if ($winner_info): ?>
                                <p class="text-gray-600">
                                    This auction has been completed. 
                                    <strong><?php echo htmlspecialchars($winner_info['username']); ?></strong> 
                                    won with a bid of <strong>$<?php echo number_format($winner_info['winning_bid'], 2); ?></strong>.
                                </p>
                            <?php else: ?>
                                <p class="text-gray-600">This auction has ended with no valid bids.</p>
                            <?php endif; ?>
                        <?php elseif ($item['status'] === 'cancelled'): ?>
                            <p class="text-gray-600">This auction has been cancelled by an administrator.</p>
                        <?php else: ?>
                            <p class="text-gray-600">This auction has expired.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Auction Details -->
            <div class="border-t pt-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Auction Details</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Starting Bid</dt>
                        <dd class="text-sm text-gray-900">$<?php echo number_format($item['starting_bid'], 2); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            <?php echo $is_active ? 'End Time' : 'Scheduled End'; ?>
                        </dt>
                        <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($item['end_time'])); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Listed On</dt>
                        <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></dd>
                    </div>
                    <?php if (!$is_active && $item['ended_at']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                <?php echo $item['status'] === 'cancelled' ? 'Cancelled On' : 'Completed On'; ?>
                            </dt>
                            <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($item['ended_at'])); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($winner_info): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Bids</dt>
                            <dd class="text-sm text-gray-900">
                                <?php 
                                $bid_count = fetchOne("SELECT COUNT(*) as count FROM bids WHERE item_id = ? AND status = 'active'", [$item_id]);
                                echo $bid_count['count'];
                                ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Winning Bid Time</dt>
                            <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($winner_info['bid_time'])); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Bid History Section -->
    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Bid History</h2>
        
        <?php
        // Fetch bid history for this item (including stopped bids and admin info)
        try {
            $bid_history = getAllBidsForItem($item_id);
        } catch (Exception $e) {
            $bid_history = [];
            error_log("Failed to fetch bid history: " . $e->getMessage());
        }
        ?>

        <?php if (!empty($bid_history)): ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php 
                    // Separate active and stopped bids for proper display
                    $active_bids = array_filter($bid_history, function($bid) { return $bid['status'] === 'active'; });
                    $stopped_bids = array_filter($bid_history, function($bid) { return $bid['status'] === 'stopped'; });
                    
                    // Sort active bids by amount (highest first)
                    usort($active_bids, function($a, $b) {
                        if ($a['bid_amount'] == $b['bid_amount']) {
                            return strtotime($a['created_at']) - strtotime($b['created_at']); // Earlier bid wins ties
                        }
                        return $b['bid_amount'] - $a['bid_amount']; // Higher bids first
                    });
                    
                    // Sort stopped bids by creation time (newest first)
                    usort($stopped_bids, function($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                    
                    // Combine arrays: active bids first, then stopped bids
                    $sorted_bids = array_merge($active_bids, $stopped_bids);
                    ?>
                    
                    <?php foreach ($sorted_bids as $index => $bid): ?>
                        <?php
                        // Determine if this is the winning bid (highest active bid)
                        $is_winning = ($bid['status'] === 'active' && $index === 0 && !empty($active_bids));
                        $is_stopped = ($bid['status'] === 'stopped');
                        
                        // Set background color based on status
                        $bg_class = '';
                        if ($is_winning && ($winner_info && $winner_info['user_id'] == $bid['user_id'])) {
                            $bg_class = 'bg-green-50 border-l-4 border-green-400';
                        } elseif ($is_winning && $is_active) {
                            $bg_class = 'bg-blue-50 border-l-4 border-blue-400';
                        } elseif ($is_stopped) {
                            $bg_class = 'bg-red-50 border-l-4 border-red-400';
                        } else {
                            $bg_class = 'bg-white';
                        }
                        ?>
                        <li class="<?php echo $bg_class; ?>">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($is_stopped): ?>
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-red-800">Stopped</span>
                                                </div>
                                            <?php elseif ($is_winning && $winner_info && $winner_info['user_id'] == $bid['user_id']): ?>
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-green-800">Winner</span>
                                                </div>
                                            <?php elseif ($is_winning && $is_active): ?>
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-blue-800">Leading</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($bid['username']); ?>
                                                </p>
                                                <?php if ($bid['user_id'] == getCurrentUserId()): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Your bid
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <!-- Status Badge -->
                                                <?php if ($is_stopped): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Bid Stopped
                                                    </span>
                                                <?php elseif ($winner_info && $winner_info['user_id'] == $bid['user_id']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Won Auction
                                                    </span>
                                                <?php elseif ($is_winning && $is_active): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Current Leader
                                                    </span>
                                                <?php elseif ($bid['status'] === 'active' && !$is_active): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Outbid
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-500">
                                                Placed on <?php echo date('M j, Y g:i A', strtotime($bid['created_at'])); ?>
                                            </p>
                                            
                                            <!-- Admin Action Information for Stopped Bids -->
                                            <?php if ($is_stopped): ?>
                                                <div class="mt-2 text-xs text-red-600 bg-red-50 rounded px-2 py-1">
                                                    <div class="flex items-center">
                                                        <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        <span>
                                                            Stopped by <?php echo htmlspecialchars($bid['stopped_by_username'] ?? 'Administrator'); ?>
                                                            on <?php echo date('M j, Y g:i A', strtotime($bid['stopped_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <?php
                                                    // Get admin action details for this bid
                                                    $admin_actions = getBidAdminActions($bid['id']);
                                                    if (!empty($admin_actions) && !empty($admin_actions[0]['reason'])):
                                                    ?>
                                                        <div class="mt-1">
                                                            <strong>Reason:</strong> <?php echo htmlspecialchars($admin_actions[0]['reason']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold <?php 
                                            if ($is_stopped) {
                                                echo 'text-red-600 line-through';
                                            } elseif ($is_winning && $winner_info && $winner_info['user_id'] == $bid['user_id']) {
                                                echo 'text-green-600';
                                            } elseif ($is_winning && $is_active) {
                                                echo 'text-blue-600';
                                            } else {
                                                echo 'text-gray-900';
                                            }
                                        ?>">
                                            $<?php echo number_format($bid['bid_amount'], 2); ?>
                                        </p>
                                        <?php if ($is_stopped): ?>
                                            <p class="text-xs text-red-500 mt-1">Invalid</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No bids yet</h3>
                <p class="text-gray-500">
                    <?php if ($is_active && isLoggedIn() && getCurrentUserId() != $item['user_id']): ?>
                        Be the first to place a bid on this item!
                    <?php elseif ($is_active && !isLoggedIn()): ?>
                        <a href="auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                            Sign in
                        </a> to be the first to bid.
                    <?php else: ?>
                        This auction ended without any bids.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript for Countdown Timer -->
<script>
// Countdown timer functionality
function updateCountdown() {
    const endTime = new Date("<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($item['end_time']))); ?>").getTime();
    const now = new Date().getTime();
    const distance = endTime - now;
    
    const timerElement = document.getElementById('countdown-timer');
    
    if (distance < 0) {
        timerElement.innerHTML = "EXPIRED";
        timerElement.className = "text-2xl font-bold text-red-600";
        return;
    }
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    let timeString = "";
    if (days > 0) {
        timeString += days + " days, ";
    }
    if (hours > 0 || days > 0) {
        timeString += hours + " hours, ";
    }
    timeString += minutes + " minutes, " + seconds + " seconds";
    
    timerElement.innerHTML = timeString;
}

// Update countdown immediately and then every second
updateCountdown();
setInterval(updateCountdown, 1000);
</script>

<?php include 'includes/footer.php'; ?>