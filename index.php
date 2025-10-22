<?php
/**
 * Main Auction Listing Page
 * Displays all active auction items in a responsive grid
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';

// Set page title for header
$page_title = 'Home';

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Welcome to College Auction</h1>
        <p class="text-lg text-gray-600">Discover amazing items and place your bids!</p>
    </div>

    <?php if (isLoggedIn()): ?>
        <!-- Welcome message for logged-in users -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-blue-800">
                Welcome back, <strong><?php echo htmlspecialchars(getCurrentUsername()); ?></strong>! 
                Ready to find your next great deal?
            </p>
        </div>
    <?php else: ?>
        <!-- Call to action for guests -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800">
                <a href="auth/register.php" class="font-medium text-yellow-900 hover:underline">Create an account</a> 
                or 
                <a href="auth/login.php" class="font-medium text-yellow-900 hover:underline">sign in</a> 
                to start bidding on items!
            </p>
        </div>
    <?php endif; ?>

    <?php
    // Fetch all active auction items (where end_time is in the future)
    try {
        $sql = "SELECT i.*, u.username as seller_username 
                FROM items i 
                JOIN users u ON i.user_id = u.id 
                WHERE i.end_time > NOW() 
                ORDER BY i.created_at DESC";
        $items = fetchAll($sql);
    } catch (Exception $e) {
        $items = [];
        $error_message = "Unable to load auction items. Please try again later.";
    }
    ?>

    <!-- Auction Items Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <a href="item.php?id=<?php echo (int)$item['id']; ?>" class="block bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200">
                    <!-- Item Image -->
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             class="w-full h-full object-cover"
                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    </div>
                    
                    <!-- Item Details -->
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                            <?php if (strlen($item['description']) > 100): ?>...<?php endif; ?>
                        </p>
                        
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-lg font-bold text-green-600">
                                $<?php echo number_format($item['current_bid'], 2); ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                by <?php echo htmlspecialchars($item['seller_username']); ?>
                            </span>
                        </div>
                        
                        <!-- Countdown Timer Placeholder -->
                        <div class="text-sm text-gray-500" id="countdown-<?php echo (int)$item['id']; ?>">
                            Loading...
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty state when no items -->
    <?php if (empty($items)): ?>
        <div class="text-center py-12">
            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 max-w-md mx-auto">
                    <p class="text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="text-gray-400 mb-4">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No active auctions right now</h3>
            <p class="text-gray-500">
                <?php if (isLoggedIn()): ?>
                    <a href="create_item.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        Be the first to list an item
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        Sign up to start listing items
                    </a>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Countdown Timers -->
<script>
// Countdown timer functionality
function updateCountdown(endTime, elementId) {
    const now = new Date().getTime();
    const distance = endTime - now;
    
    if (distance < 0) {
        document.getElementById(elementId).innerHTML = "EXPIRED";
        document.getElementById(elementId).className = "text-sm text-red-500 font-medium";
        return;
    }
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    let timeString = "";
    if (days > 0) {
        timeString += days + "d ";
    }
    if (hours > 0 || days > 0) {
        timeString += hours + "h ";
    }
    timeString += minutes + "m " + seconds + "s";
    
    document.getElementById(elementId).innerHTML = timeString;
}

// Initialize countdown timers for all items
<?php if (!empty($items)): ?>
    <?php foreach ($items as $item): ?>
        // Convert PHP datetime to JavaScript timestamp
        const endTime<?php echo (int)$item['id']; ?> = new Date("<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($item['end_time']))); ?>").getTime();
        
        // Update countdown immediately
        updateCountdown(endTime<?php echo (int)$item['id']; ?>, 'countdown-<?php echo (int)$item['id']; ?>');
        
        // Update countdown every second
        setInterval(function() {
            updateCountdown(endTime<?php echo (int)$item['id']; ?>, 'countdown-<?php echo (int)$item['id']; ?>');
        }, 1000);
    <?php endforeach; ?>
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>