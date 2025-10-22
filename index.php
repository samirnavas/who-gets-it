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
<div class="bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold mb-4 animate-fade-in">
                Discover Your Next Treasure
            </h1>
            <p class="text-xl md:text-2xl text-blue-100 mb-8 max-w-2xl mx-auto">
                Join thousands of bidders finding amazing deals on unique items
            </p>
            <?php if (!isLoggedIn()): ?>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="auth/register.php" 
                       class="px-8 py-4 bg-white text-purple-600 rounded-full font-bold text-lg hover:bg-gray-100 transform hover:scale-105 transition-all duration-200 shadow-lg">
                        Start Bidding Now
                    </a>
                    <a href="auth/login.php" 
                       class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-full font-bold text-lg hover:bg-white hover:text-purple-600 transform hover:scale-105 transition-all duration-200">
                        Sign In
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
        <!-- Access denied message with modern styling -->
        <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 mb-8 shadow-md animate-slide-in">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-semibold text-red-800">Access Denied</p>
                    <p class="text-red-700 text-sm mt-1">You don't have permission to access the admin panel. Only administrators can access that area.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
        <!-- Welcome message for logged-in users with modern card -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6 mb-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                        <?php echo strtoupper(substr(getCurrentUsername(), 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-gray-800 text-lg">
                            Welcome back, <strong class="text-blue-700"><?php echo htmlspecialchars(getCurrentUsername()); ?></strong>!
                        </p>
                        <p class="text-gray-600 text-sm">Ready to find your next great deal?</p>
                    </div>
                </div>
                <?php if (isAdmin()): ?>
                    <a href="admin/index.php" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                        Admin Panel
                    </a>
                <?php endif; ?>
            </div>
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

    <!-- Filter and Sort Section -->
    <?php if (!empty($items)): ?>
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900">
                    Active Auctions <span class="text-gray-500 text-lg">(<?php echo count($items); ?>)</span>
                </h2>
            </div>
        </div>
    <?php endif; ?>

    <!-- Auction Items Grid with enhanced cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <div class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300">
                    <a href="item.php?id=<?php echo (int) $item['id']; ?>" class="block">
                        <!-- Item Image with overlay -->
                        <div class="relative h-56 bg-gradient-to-br from-gray-200 to-gray-300 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                            
                            <!-- Gradient overlay on hover -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            
                            <!-- Seller badge -->
                            <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm rounded-full px-3 py-1 shadow-lg">
                                <span class="text-xs font-semibold text-gray-700">
                                    <?php echo htmlspecialchars($item['seller_username']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Item Details -->
                        <div class="p-5">
                            <h3 class="text-lg font-bold text-gray-900 mb-2 truncate group-hover:text-blue-600 transition-colors">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h3>

                            <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                                <?php if (strlen($item['description']) > 100): ?>...<?php endif; ?>
                            </p>

                            <!-- Price section with gradient -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 mb-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-gray-600 mb-1">Current Bid</p>
                                        <span class="text-2xl font-extrabold text-green-600">
                                            $<?php echo number_format($item['current_bid'], 2); ?>
                                        </span>
                                    </div>
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Countdown Timer with icon -->
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-gray-700" id="countdown-<?php echo (int) $item['id']; ?>">
                                        Loading...
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty state when no items -->
    <?php if (empty($items)): ?>
        <div class="text-center py-20">
            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 mb-8 max-w-md mx-auto shadow-lg">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-3xl p-12 max-w-2xl mx-auto shadow-xl">
                <div class="text-gray-400 mb-6">
                    <svg class="mx-auto h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">No Active Auctions</h3>
                <p class="text-gray-600 mb-6">
                    There are no auctions running at the moment. Check back soon for new items!
                </p>
                <?php if (isLoggedIn()): ?>
                    <a href="create_item.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-bold text-lg hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        List Your First Item
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-bold text-lg hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                        Get Started Today
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Countdown Timers -->
<script>
    // Countdown timer functionality
    function updateCountdown(endTime, elementId) {
        const now = new Date().getTime();
        const distance = endTime - now;

        const element = document.getElementById(elementId);
        if (!element) return;

        if (distance < 0) {
            element.innerHTML = "ENDED";
            element.className = "text-sm font-bold text-red-500";
            element.parentElement.className = "flex items-center justify-between bg-red-50 rounded-lg p-3";
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
        
        // Change color based on urgency
        if (distance < 3600000) { // Less than 1 hour
            element.className = "text-sm font-bold text-red-600";
            element.parentElement.className = "flex items-center justify-between bg-red-50 rounded-lg p-3";
        } else if (distance < 86400000) { // Less than 1 day
            element.className = "text-sm font-bold text-orange-600";
            element.parentElement.className = "flex items-center justify-between bg-orange-50 rounded-lg p-3";
        } else {
            element.className = "text-sm font-semibold text-gray-700";
        }
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

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slide-in {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .animate-fade-in {
        animation: fade-in 0.6s ease-out;
    }
    
    .animate-slide-in {
        animation: slide-in 0.4s ease-out;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php include 'includes/footer.php'; ?>