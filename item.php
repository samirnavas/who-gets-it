<?php
/**
 * Item Detail Page
 * Displays complete item information and bidding functionality
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';

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

// Check if auction is still active
$is_active = strtotime($item['end_time']) > time();

// Initialize variables for form handling
$bid_error = '';
$bid_success = '';

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    if (!isLoggedIn()) {
        $bid_error = 'You must be logged in to place a bid.';
    } elseif (!$is_active) {
        $bid_error = 'This auction has ended.';
    } elseif (getCurrentUserId() == $item['user_id']) {
        $bid_error = 'You cannot bid on your own item.';
    } else {
        $bid_amount = isset($_POST['bid_amount']) ? (float)$_POST['bid_amount'] : 0;
        
        // Validate bid amount
        if ($bid_amount <= 0) {
            $bid_error = 'Please enter a valid bid amount.';
        } elseif ($bid_amount <= $item['current_bid']) {
            $bid_error = 'Your bid must be higher than the current bid of $' . htmlspecialchars(number_format($item['current_bid'], 2)) . '.';
        } else {
            try {
                // Start transaction
                $pdo = getDbConnection();
                $pdo->beginTransaction();
                
                // Insert bid into bids table
                $insert_bid_sql = "INSERT INTO bids (item_id, user_id, bid_amount) VALUES (?, ?, ?)";
                executeQuery($insert_bid_sql, [$item_id, getCurrentUserId(), $bid_amount]);
                
                // Update item with new current bid and highest bidder
                $update_item_sql = "UPDATE items SET current_bid = ?, highest_bidder_id = ? WHERE id = ?";
                executeQuery($update_item_sql, [$bid_amount, getCurrentUserId(), $item_id]);
                
                // Commit transaction
                $pdo->commit();
                
                // Update local item data for display
                $item['current_bid'] = $bid_amount;
                $item['highest_bidder_id'] = getCurrentUserId();
                
                $bid_success = 'Your bid of $' . htmlspecialchars(number_format($bid_amount, 2)) . ' has been placed successfully!';
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                error_log("Bid submission error: " . $e->getMessage());
                $bid_error = 'Failed to place bid. Please try again.';
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
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Expired
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Current Bid -->
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

            <!-- Countdown Timer -->
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm text-blue-600 mb-1">Time Remaining</div>
                <div id="countdown-timer" class="text-2xl font-bold text-blue-800">
                    Loading...
                </div>
            </div>

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
                        <p class="text-gray-600">This auction has ended.</p>
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
                        <dt class="text-sm font-medium text-gray-500">End Time</dt>
                        <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($item['end_time'])); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Listed On</dt>
                        <dd class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Bid History Section -->
    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Bid History</h2>
        
        <?php
        // Fetch bid history for this item
        try {
            $bid_history_sql = "SELECT b.*, u.username 
                               FROM bids b 
                               JOIN users u ON b.user_id = u.id 
                               WHERE b.item_id = ? 
                               ORDER BY b.bid_amount DESC, b.created_at DESC";
            $bid_history = fetchAll($bid_history_sql, [$item_id]);
        } catch (Exception $e) {
            $bid_history = [];
            error_log("Failed to fetch bid history: " . $e->getMessage());
        }
        ?>

        <?php if (!empty($bid_history)): ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($bid_history as $index => $bid): ?>
                        <li class="<?php echo $index === 0 ? 'bg-green-50 border-l-4 border-green-400' : 'bg-white'; ?>">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <?php if ($index === 0): ?>
                                                <div class="flex items-center">
                                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-green-800">Winning Bid</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($bid['username']); ?>
                                                </p>
                                                <?php if ($bid['user_id'] == getCurrentUserId()): ?>
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Your bid
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('M j, Y g:i A', strtotime($bid['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold <?php echo $index === 0 ? 'text-green-600' : 'text-gray-900'; ?>">
                                            $<?php echo number_format($bid['bid_amount'], 2); ?>
                                        </p>
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