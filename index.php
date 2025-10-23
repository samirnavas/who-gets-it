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

<!-- Hero Section with Gradient Background -->
<section class="bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 text-white py-12 md:py-16 lg:py-20" role="banner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4">
                Discover Your Next Treasure
            </h1>
            <p class="text-lg md:text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                Join thousands of bidders finding amazing deals on unique items
            </p>
            <?php if (!isLoggedIn()): ?>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="auth/register.php"
                        class="px-6 py-3 text-lg bg-yellow-400 text-purple-600 hover:bg-yellow-300 rounded-full shadow-lg font-semibold transition-all duration-200 hover:scale-105"
                        aria-label="Register to start bidding">
                        Start Bidding Now
                    </a>
                    <a href="auth/login.php"
                        class="px-6 py-3 text-lg border-2 border-white text-white hover:bg-white hover:text-purple-600 rounded-full font-semibold transition-all duration-200 hover:scale-105">
                        Sign In
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12"></section>

<?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
    <!-- Access denied message with modern styling -->
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 mb-8 shadow-md animate-slide-in">
        <div class="flex items-center">
            <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-semibold text-red-800">Access Denied</p>
                <p class="text-red-700 text-sm mt-1">You don't have permission to access the admin panel. Only
                    administrators can access that area.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isLoggedIn()): ?>
    <!-- Welcome message for logged-in users with modern card -->

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

    <!-- Filter and Sort Section -->
    <?php if (!empty($items)): ?>
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h2 class="text-2xl font-bold text-gray-900">
                    Active Auctions <span class="text-gray-500 text-lg">(<?php echo count($items); ?>)</span>
                </h2>
            </div>
        </div>
    <?php endif; ?>

    <!-- Responsive Auction Items Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8">
        <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
            <?php
            // Calculate time remaining for status determination
            $end_time = strtotime($item['end_time']);
            $current_time = time();
            $time_remaining = $end_time - $current_time;

            // Determine auction status
            $auction_status = 'active';
            if ($time_remaining <= 0) {
                $auction_status = 'ended';
            } elseif ($time_remaining <= 3600) { // Less than 1 hour
                $auction_status = 'ending-soon';
            }
            ?>
            <article class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 hover:border-blue-200" role="article" aria-labelledby="item-title-<?php echo (int) $item['id']; ?>">
                <a href="item.php?id=<?php echo (int) $item['id']; ?>" class="block text-inherit no-underline h-full"
                    aria-describedby="item-desc-<?php echo (int) $item['id']; ?>">
                    <!-- Item Image Container -->
                    <div class="relative w-full h-48 md:h-44 lg:h-40 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy"
                            class="w-full h-full object-cover transition-transform duration-300 hover:scale-110"
                            onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">

                        <!-- Image Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>

                        <!-- Seller Badge -->
                        <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm rounded-full px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm border border-white/20 transition-all duration-200 hover:bg-white hover:scale-105">
                            <?php echo htmlspecialchars($item['seller_username']); ?>
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide backdrop-blur-sm border border-white/20 
                            <?php 
                            switch ($auction_status) {
                                case 'ending-soon':
                                    echo 'bg-amber-500/90 text-white';
                                    break;
                                case 'ended':
                                    echo 'bg-gray-500/90 text-white';
                                    break;
                                default:
                                    echo 'bg-green-500/90 text-white';
                            }
                            ?>">
                            <?php
                            switch ($auction_status) {
                                case 'ending-soon':
                                    echo 'Ending Soon';
                                    break;
                                case 'ended':
                                    echo 'Ended';
                                    break;
                                default:
                                    echo 'Active';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-5 md:p-4 lg:p-5">
                        <h3 class="text-lg md:text-base lg:text-lg font-bold text-gray-900 mb-2 line-clamp-2 transition-colors duration-200 hover:text-blue-600" id="item-title-<?php echo (int) $item['id']; ?>">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>

                        <p class="text-sm text-gray-600 mb-4 line-clamp-2" id="item-desc-<?php echo (int) $item['id']; ?>">
                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                            <?php if (strlen($item['description']) > 100): ?>...<?php endif; ?>
                        </p>

                        <!-- Price Section -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 mb-4 relative overflow-hidden">
                            <div class="text-xs text-gray-600 mb-1 font-medium">Current Bid</div>
                            <div class="flex items-center justify-between text-2xl md:text-xl lg:text-2xl font-extrabold text-green-600">
                                <span>$<?php echo number_format($item['current_bid'], 2); ?></span>
                                <svg class="w-6 h-6 text-green-600 opacity-70 transition-all duration-200 hover:opacity-100 hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>

                        <!-- Timer Section -->
                        <div class="<?php echo $auction_status === 'ending-soon' ? 'bg-gradient-to-r from-red-50 to-orange-50 border-red-200' : ($auction_status === 'ended' ? 'bg-gray-50 border-gray-200' : 'bg-gray-50 border-gray-200'); ?> border rounded-lg p-3 transition-all duration-200">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 <?php echo $auction_status === 'ending-soon' ? 'text-red-500' : ($auction_status === 'ended' ? 'text-gray-500' : 'text-gray-500'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-semibold <?php echo $auction_status === 'ending-soon' ? 'text-red-600 font-bold' : ($auction_status === 'ended' ? 'text-gray-600' : 'text-gray-700'); ?>" id="countdown-<?php echo (int) $item['id']; ?>">
                                    Loading...
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty State -->
    <?php if (empty($items)): ?>
        <div class="text-center py-20 px-4">
            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 mb-8 max-w-md mx-auto shadow-lg">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-3xl p-12 max-w-lg mx-auto shadow-xl border border-gray-200">
                <svg class="w-20 h-20 text-gray-400 mx-auto mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">No Active Auctions</h3>
                <p class="text-base text-gray-600 mb-6 leading-relaxed">
                    There are no auctions running at the moment. Check back soon for new items!
                </p>
                <?php if (isLoggedIn()): ?>
                    <a href="create_item.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-bold text-lg shadow-lg transition-all duration-200 hover:shadow-xl hover:-translate-y-1 hover:scale-105">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        List Your First Item
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-bold text-lg shadow-lg transition-all duration-200 hover:shadow-xl hover:-translate-y-1 hover:scale-105">
                        Get Started Today
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
</section>

<!-- JavaScript for Countdown Timers -->
<script>
    // Countdown timer functionality for auction cards
    function updateCountdown(endTime, elementId) {
        const now = new Date().getTime();
        const distance = endTime - now;

        const element = document.getElementById(elementId);
        if (!element) return;

        if (distance < 0) {
            element.innerHTML = "ENDED";
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

        element.innerHTML = timeString;
    }

    // Initialize countdown timers for all items
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
            // Convert PHP datetime to JavaScript timestamp
            const endTime<?php echo (int) $item['id']; ?> = new Date("<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($item['end_time']))); ?>").getTime();

            // Update countdown immediately
            updateCountdown(endTime<?php echo (int) $item['id']; ?>, 'countdown-<?php echo (int) $item['id']; ?>');

            // Update countdown every second
            setInterval(function () {
                updateCountdown(endTime<?php echo (int) $item['id']; ?>, 'countdown-<?php echo (int) $item['id']; ?>');
            }, 1000);
        <?php endforeach; ?>
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>