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

// Include mobile auction interactions CSS
echo '<link rel="stylesheet" href="assets/css/mobile-auction-interactions.css">';
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
                <!-- Enhanced Mobile Current Bid Display -->
                <div class="price-display-mobile">
                    <div class="price-display-mobile-label">Current Bid</div>
                    <div class="price-display-mobile-amount">
                        $<?php echo number_format($item['current_bid'], 2); ?>
                    </div>
                    <?php if ($item['highest_bidder_id']): ?>
                        <?php
                        $highest_bidder = fetchOne("SELECT username FROM users WHERE id = ?", [$item['highest_bidder_id']]);
                        ?>
                        <div class="text-sm text-gray-600 mt-2 font-medium">
                            Leading: <?php echo htmlspecialchars($highest_bidder['username']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Enhanced Mobile Countdown Timer -->
            <?php if ($is_active): ?>
                <div class="countdown-mobile" id="countdown-container">
                    <div class="countdown-mobile-label">Time Remaining</div>
                    <div id="countdown-timer" class="countdown-mobile-time">
                        Loading...
                    </div>
                    <div id="countdown-segments" class="countdown-segments"></div>
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

            <!-- Enhanced Mobile-Friendly Bidding Form -->
            <?php if ($is_active && isLoggedIn()): ?>
                        <div class="border-t pt-6">
                              <h3 class="text-lg font-medium text-gray-900 mb-4">Place Your Bid</h3>
                              
                              <?php if (getCurrentUserId() == $item['user_id']): ?>
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                          <p class="text-yellow-800">You cannot bid on your own item.</p>
                                    </div>
                              <?php else: ?>
                                    <div class="w-full">
                                                                                    <div class="grid grid-cols-3 gap-3 md:hidden mb-4">
                                                <?php 
                                                $current_bid = $item['current_bid'];
                                                $quick_bids = [
                                                      $current_bid + 1,
                                                      $current_bid + 5,
                                                      $current_bid + 10
                                                ];
                                                ?>
                                                <?php foreach ($quick_bids as $quick_bid): ?>
                                                      <button type="button" 
                                                                  class="w-full py-2 px-3 bg-gray-50 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out" 
                                                                  onclick="setQuickBid(<?php echo $quick_bid; ?>)"
                                                                  aria-label="Quick bid $<?php echo number_format($quick_bid, 0); ?>">
                                                            $<?php echo number_format($quick_bid, 0); ?>
                                                      </button>
                                                <?php endforeach; ?>
                                          </div>
                                          
                                          <form method="POST" action="" class="space-y-6">
                                                <div>
                                                      <label for="bid_amount" class="block text-sm font-medium text-gray-700">
                                                            Bid Amount (minimum: $<?php echo number_format($item['current_bid'] + 0.01, 2); ?>)
               
                                                </label>
                                                      <div class="relative mt-1 rounded-md shadow-sm">
                                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                                  <span class="text-gray-500 text-lg font-semibold">$</span>
                                                            </div>
                                                            <input 
                                                                  type="number" 
                                                                  id="bid_amount" 
                                                                  name="bid_amount" 
                                                                  step="0.01" 
                                                                  min="<?php echo htmlspecialchars($item['current_bid'] + 0.01); ?>"
                                                                  class="block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-10"
                                                                  placeholder="<?php echo number_format($item['current_bid'] + 1, 2); ?>"
                                                                  required
                                                                  aria-describedby="bid-help"
                                                            >
                                                      </div>
                                                    <div id="bid-help" class="mt-2 text-sm text-gray-500">
                                                            Enter an amount higher than the current bid to place your bid.
                                                      </div>
                                                </div>
                                                
                                                <div>
                                      <button 
                                                            type="submit" 
                                                            name="place_bid"
                                                            img class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out"
                                                            aria-label="Place your bid" >
                                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" class="mr-2">
                                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                          </svg>
                                                            <span>Place Bid</span>
                                                      </button>
                                   </form>
                                    </div>
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
                           _BIDS </div>
                      </div>
                  <?php elseif (!$is_active): ?>
                        <div class="border-t pt-6">
                              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                               <?php if ($item['status'] === 'ended'): ?>
                                          <?php if ($winner_info): ?>
                                             <p class="text-gray-600">
                                                      This auction has been completed. 
                                       im          <strong><?php echo htmlspecialchars($winner_info['username']); ?></strong> 
                                                      won with a bid of <strong>$<?php echo number_format($winner_info['winning_bid'], 2); ?></strong>.
                                                </p>
                              s          <?php else: ?>
                                                <p class="text-gray-600">This auction has ended with no valid bids.</p>
                                          <?php endif; ?>
                                    <?php elseif ($item['status'] === 'cancelled'): ?>
                                           <p class="text-gray-600">This auction has been cancelled by an administrator.</p>
                                    <?php else: ?>
                                          <p class="text-gray-600">This auction has expired.</p>
                        section                   <?php endif; ?>
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

<!-- Mobile Navigation for Auction Browsing -->
<?php if ($is_active): ?>
<div class="auction-nav-mobile md:hidden">
    <button class="auction-nav-mobile-button" onclick="window.history.back()" aria-label="Go back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </button>
    
    <button class="auction-nav-mobile-button" onclick="window.location.href='index.php'" aria-label="View all auctions">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="4 6h16M4 12h16M4 18h16"/>
        </svg>
        All Auctions
    </button>
    
    <?php if (isLoggedIn() && getCurrentUserId() != $item['user_id']): ?>
    <button class="auction-nav-mobile-button primary" onclick="document.getElementById('bid_amount').focus()" aria-label="Focus bid input">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
        </svg>
        Bid Now
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

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
const countdownInterval = setInterval(updateCountdown, 1000);

// Enhanced mobile interactions with improved haptic feedback
function setQuickBid(amount) {
    const bidInput = document.getElementById('bid_amount');
    if (bidInput) {
        bidInput.value = amount.toFixed(2);
        bidInput.focus();
        addHapticFeedback(bidInput, 'medium');
        
        // Visual feedback for the selected quick bid button
        const quickBidButtons = document.querySelectorAll('.quick-bid-button');
        quickBidButtons.forEach(btn => btn.classList.remove('selected'));
        event.target.classList.add('selected');
    }
}

function addHapticFeedback(element, intensity = 'light') {
    if (element) {
        // Remove any existing haptic classes
        element.classList.remove('haptic-feedback', 'haptic-feedback-light', 'haptic-feedback-medium', 'haptic-feedback-strong');
        
        // Add appropriate haptic class
        element.classList.add(`haptic-feedback-${intensity}`);
        
        setTimeout(() => {
            element.classList.remove(`haptic-feedback-${intensity}`);
        }, intensity === 'strong' ? 300 : intensity === 'medium' ? 200 : 100);
    }
    
    // Try to trigger actual haptic feedback on supported devices
    if ('vibrate' in navigator) {
        const vibrationPattern = {
            light: 30,
            medium: 50,
            strong: [50, 50, 50]
        };
        navigator.vibrate(vibrationPattern[intensity] || 30);
    }
}

// Enhanced countdown with mobile-specific styling and segments
function updateCountdown() {
    const endTime = new Date("<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($item['end_time']))); ?>").getTime();
    const now = new Date().getTime();
    const distance = endTime - now;
    
    const timerElement = document.getElementById('countdown-timer');
    const containerElement = document.getElementById('countdown-container');
    const segmentsElement = document.getElementById('countdown-segments');
    
    if (!timerElement || !containerElement) return;
    
    if (distance < 0) {
        timerElement.innerHTML = "EXPIRED";
        containerElement.className = "countdown-mobile ended";
        if (segmentsElement) segmentsElement.style.display = 'none';
        clearInterval(countdownInterval);
        return;
    }
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    // Mobile-friendly time display
    if (window.innerWidth <= 768) {
        // Use segments for mobile
        if (segmentsElement) {
            segmentsElement.innerHTML = `
                ${days > 0 ? `<div class="countdown-segment">
                    <span class="countdown-segment-number">${days}</span>
                    <span class="countdown-segment-label">Days</span>
                </div>` : ''}
                ${hours > 0 || days > 0 ? `<div class="countdown-segment">
                    <span class="countdown-segment-number">${hours}</span>
                    <span class="countdown-segment-label">Hours</span>
                </div>` : ''}
                <div class="countdown-segment">
                    <span class="countdown-segment-number">${minutes}</span>
                    <span class="countdown-segment-label">Min</span>
                </div>
                <div class="countdown-segment">
                    <span class="countdown-segment-number">${seconds}</span>
                    <span class="countdown-segment-label">Sec</span>
                </div>
            `;
            timerElement.innerHTML = '';
        }
    } else {
        // Desktop format
        let timeString = "";
        if (days > 0) {
            timeString += days + "d ";
        }
        if (hours > 0 || days > 0) {
            timeString += hours + "h ";
        }
        timeString += minutes + "m " + seconds + "s";
        
        timerElement.innerHTML = timeString;
        if (segmentsElement) segmentsElement.innerHTML = '';
    }
    
    // Update container class based on urgency
    if (distance < 3600000) { // Less than 1 hour
        containerElement.className = "countdown-mobile urgent";
        // Add haptic feedback for urgent countdown
        if (distance < 300000 && seconds % 10 === 0) { // Last 5 minutes, every 10 seconds
            addHapticFeedback(containerElement, 'light');
        }
    } else if (distance < 86400000) { // Less than 1 day
        containerElement.className = "countdown-mobile warning";
    } else {
        containerElement.className = "countdown-mobile";
    }
}

// Enhanced swipe gesture support for mobile auction browsing
let startX = 0;
let startY = 0;
let isSwipeGesture = false;
let swipeThreshold = 80;
let swipeVelocityThreshold = 0.3;
let swipeStartTime = 0;

function initializeSwipeGestures() {
    const swipeArea = document.querySelector('.max-w-6xl');
    if (!swipeArea || window.innerWidth > 768) return; // Only on mobile
    
    swipeArea.classList.add('touch-gesture-area');
    
    // Show swipe hint on first visit
    showSwipeHint();
    
    swipeArea.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        swipeStartTime = Date.now();
        isSwipeGesture = false;
    }, { passive: true });
    
    swipeArea.addEventListener('touchmove', function(e) {
        if (!startX || !startY) return;
        
        const currentX = e.touches[0].clientX;
        const currentY = e.touches[0].clientY;
        
        const diffX = startX - currentX;
        const diffY = startY - currentY;
        
        // Check if it's a horizontal swipe (more horizontal than vertical movement)
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 30) {
            isSwipeGesture = true;
            
            // Prevent default scrolling during swipe
            e.preventDefault();
            
            // Visual feedback during swipe
            const swipeProgress = Math.min(Math.abs(diffX) / swipeThreshold, 1);
            const translateX = diffX * 0.3; // Damped movement
            
            swipeArea.style.transform = `translateX(${-translateX}px)`;
            swipeArea.style.opacity = 1 - (swipeProgress * 0.2);
            
            if (diffX > 0) {
                swipeArea.classList.add('swipe-left');
                swipeArea.classList.remove('swipe-right');
            } else {
                swipeArea.classList.add('swipe-right');
                swipeArea.classList.remove('swipe-left');
            }
        }
    }, { passive: false });
    
    swipeArea.addEventListener('touchend', function(e) {
        if (isSwipeGesture) {
            const diffX = startX - e.changedTouches[0].clientX;
            const swipeTime = Date.now() - swipeStartTime;
            const swipeVelocity = Math.abs(diffX) / swipeTime;
            
            // Reset visual state
            swipeArea.style.transform = '';
            swipeArea.style.opacity = '';
            swipeArea.classList.remove('swipe-left', 'swipe-right');
            
            // Check if swipe meets threshold (distance or velocity)
            if (Math.abs(diffX) > swipeThreshold || swipeVelocity > swipeVelocityThreshold) {
                addHapticFeedback(swipeArea, 'medium');
                
                if (diffX > 0) {
                    // Swipe left - next auction
                    navigateToNextAuction();
                } else {
                    // Swipe right - previous auction or back
                    navigateToPreviousAuction();
                }
            }
        }
        
        startX = 0;
        startY = 0;
        isSwipeGesture = false;
        swipeStartTime = 0;
    }, { passive: true });
}

function navigateToNextAuction() {
    showSwipeIndicator('right', 'Next Auction');
    
    // Get current item ID and fetch next auction
    const currentItemId = <?php echo (int)$item_id; ?>;
    
    // For now, show feedback. In a real implementation, you would:
    // 1. Fetch the next auction item ID from the server
    // 2. Navigate to item.php?id=nextItemId
    
    setTimeout(() => {
        // Example navigation (would be replaced with actual next item ID)
        // window.location.href = `item.php?id=${nextItemId}`;
        console.log('Navigate to next auction');
    }, 500);
}

function navigateToPreviousAuction() {
    // Check if we can go back in history, otherwise go to auction list
    if (window.history.length > 1) {
        showSwipeIndicator('left', 'Go Back');
        setTimeout(() => {
            window.history.back();
        }, 300);
    } else {
        showSwipeIndicator('left', 'Auction List');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 300);
    }
}

function showSwipeIndicator(direction, text) {
    const indicator = document.createElement('div');
    indicator.className = `swipe-indicator ${direction} show`;
    indicator.innerHTML = direction === 'left' ? 
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>' :
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
    
    document.body.appendChild(indicator);
    
    // Show text hint
    if (text) {
        const textHint = document.createElement('div');
        textHint.className = 'swipe-hint show';
        textHint.textContent = text;
        document.body.appendChild(textHint);
        
        setTimeout(() => {
            textHint.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(textHint)) {
                    document.body.removeChild(textHint);
                }
            }, 300);
        }, 1200);
    }
    
    setTimeout(() => {
        indicator.classList.remove('show');
        setTimeout(() => {
            if (document.body.contains(indicator)) {
                document.body.removeChild(indicator);
            }
        }, 300);
    }, 1500);
}

function showSwipeHint() {
    // Show hint only once per session
    if (sessionStorage.getItem('swipeHintShown')) return;
    
    setTimeout(() => {
        const hint = document.createElement('div');
        hint.className = 'swipe-hint show';
        hint.textContent = 'Swipe left/right to navigate';
        document.body.appendChild(hint);
        
        setTimeout(() => {
            hint.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(hint)) {
                    document.body.removeChild(hint);
                }
            }, 300);
        }, 3000);
        
        sessionStorage.setItem('swipeHintShown', 'true');
    }, 2000);
}

// Initialize mobile interactions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSwipeGestures();
    initializeMobileInteractions();
    
    // Add touch feedback to all interactive elements
    const interactiveElements = document.querySelectorAll('button, .quick-bid-button, .bid-button-mobile, .auction-nav-mobile-button');
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            addHapticFeedback(this, 'light');
        }, { passive: true });
    });
    
    // Enhanced bid input focus handling for mobile
    const bidInput = document.getElementById('bid_amount');
    if (bidInput) {
        bidInput.addEventListener('focus', function() {
            // Scroll to input on mobile
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        });
        
        bidInput.addEventListener('input', function() {
            // Validate bid amount in real-time
            const currentBid = <?php echo $item['current_bid']; ?>;
            const minBid = currentBid + 0.01;
            const value = parseFloat(this.value);
            
            if (value && value <= currentBid) {
                this.style.borderColor = '#EF4444';
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.style.borderColor = '#3B82F6';
                this.setAttribute('aria-invalid', 'false');
            }
        });
    }
});

function initializeMobileInteractions() {
    // Add CSS class for mobile-specific styling
    if (window.innerWidth <= 768) {
        document.body.classList.add('mobile-device');
    }
    
    // Handle orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            // Recalculate layout after orientation change
            if (window.innerWidth <= 768) {
                document.body.classList.add('mobile-device');
            } else {
                document.body.classList.remove('mobile-device');
            }
        }, 100);
    });
    
    // Prevent double-tap zoom on buttons
    const buttons = document.querySelectorAll('button, .quick-bid-button, .bid-button-mobile');
    buttons.forEach(button => {
        button.addEventListener('touchend', function(e) {
            e.preventDefault();
            this.click();
        });
    });
    
    // Add visual feedback for form validation
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('.bid-button-mobile');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="animate-spin">
                        <circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" stroke-opacity="0.25"/>
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <span>Processing...</span>
                `;
                addHapticFeedback(submitButton, 'strong');
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
