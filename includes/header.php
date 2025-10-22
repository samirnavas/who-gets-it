<?php
/**
 * Header Component
 * Responsive navigation header with conditional menu items based on authentication state
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication helper functions
require_once __DIR__ . '/auth_helper.php';

// Include notification helper if user is logged in
if (isLoggedIn()) {
    require_once __DIR__ . '/notification_helper.php';
}

// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Helper function to determine if a nav item is active
function isActiveNav($page, $dir = '') {
    global $current_page, $current_dir;
    if (!empty($dir)) {
        return $current_dir === $dir && $current_page === $page;
    }
    return $current_page === $page;
}

// Helper function to get nav link classes
function getNavLinkClasses($page, $dir = '') {
    $baseClasses = "px-3 py-2 rounded-md text-sm font-medium transition duration-200";
    if (isActiveNav($page, $dir)) {
        return $baseClasses . " bg-blue-700 text-white";
    }
    return $baseClasses . " text-blue-100 hover:bg-blue-700 hover:text-white";
}

// Helper function to get mobile nav link classes
function getMobileNavLinkClasses($page, $dir = '') {
    $baseClasses = "block px-3 py-2 rounded-md text-base font-medium transition duration-200";
    if (isActiveNav($page, $dir)) {
        return $baseClasses . " bg-blue-700 text-white";
    }
    return $baseClasses . " text-blue-100 hover:bg-blue-700 hover:text-white";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>College Auction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Mobile menu toggle functionality
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            const isHidden = mobileMenu.classList.contains('hidden');
            
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                mobileMenu.classList.add('block');
            } else {
                mobileMenu.classList.remove('block');
                mobileMenu.classList.add('hidden');
            }
        }
        
        <?php if (isLoggedIn()): ?>
        // Real-time notification updates
        function updateNotificationCount() {
            fetch('api/notifications.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badges = document.querySelectorAll('.notification-badge');
                        badges.forEach(badge => {
                            if (data.unread_count > 0) {
                                badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    }
                })
                .catch(error => console.error('Error updating notification count:', error));
        }
        
        // Update notification count every 30 seconds
        setInterval(updateNotificationCount, 30000);
        
        // Update on page load
        document.addEventListener('DOMContentLoaded', updateNotificationCount);
        <?php endif; ?>
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation Header -->
    <nav class="bg-blue-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <span class="text-white text-xl font-bold">College Auction</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex md:items-center md:space-x-4">
                    <!-- Always visible links -->
                    <a href="index.php" class="<?php echo getNavLinkClasses('index.php'); ?>">
                        Home
                    </a>

                    <?php if (isLoggedIn()): ?>
                        <!-- Logged-in user navigation -->
                        <a href="create_item.php" class="<?php echo getNavLinkClasses('create_item.php'); ?>">
                            List New Item
                        </a>
                        <a href="my_bids.php" class="<?php echo getNavLinkClasses('my_bids.php'); ?>">
                            My Bids
                        </a>
                        
                        <!-- Notifications -->
                        <a href="notifications.php" class="<?php echo getNavLinkClasses('notifications.php'); ?> relative">
                            Notifications
                            <?php 
                            $unread_count = getUnreadNotificationCount(getCurrentUserId());
                            ?>
                            <span class="notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" 
                                  style="display: <?php echo $unread_count > 0 ? 'flex' : 'none'; ?>">
                                <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                            </span>
                        </a>
                        
                        <?php if (isAdmin()): ?>
                            <!-- Admin navigation -->
                            <a href="admin/index.php" class="<?php echo getNavLinkClasses('index.php', 'admin'); ?> bg-red-600 hover:bg-red-700">
                                Admin Panel
                            </a>
                        <?php endif; ?>
                        
                        <!-- User dropdown or simple display -->
                        <div class="flex items-center space-x-4">
                            <span class="text-blue-100 text-sm">
                                Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>
                            </span>
                            <a href="auth/logout.php" class="<?php echo getNavLinkClasses('logout.php', 'auth'); ?>">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Guest user navigation -->
                        <a href="auth/login.php" class="<?php echo getNavLinkClasses('login.php', 'auth'); ?>">
                            Login
                        </a>
                        <a href="auth/register.php" class="<?php echo getNavLinkClasses('register.php', 'auth'); ?>">
                            Register
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button 
                        onclick="toggleMobileMenu()" 
                        class="text-blue-100 hover:text-white focus:outline-none focus:text-white p-2"
                        aria-label="Toggle mobile menu"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-blue-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <!-- Always visible links -->
                <a href="index.php" class="<?php echo getMobileNavLinkClasses('index.php'); ?>">
                    Home
                </a>

                <?php if (isLoggedIn()): ?>
                    <!-- Logged-in user mobile navigation -->
                    <a href="create_item.php" class="<?php echo getMobileNavLinkClasses('create_item.php'); ?>">
                        List New Item
                    </a>
                    <a href="my_bids.php" class="<?php echo getMobileNavLinkClasses('my_bids.php'); ?>">
                        My Bids
                    </a>
                    
                    <!-- Mobile Notifications -->
                    <a href="notifications.php" class="<?php echo getMobileNavLinkClasses('notifications.php'); ?> relative">
                        Notifications
                        <?php 
                        $unread_count = getUnreadNotificationCount(getCurrentUserId());
                        ?>
                        <span class="notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" 
                              style="display: <?php echo $unread_count > 0 ? 'flex' : 'none'; ?>">
                            <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                        </span>
                    </a>
                    
                    <?php if (isAdmin()): ?>
                        <!-- Admin mobile navigation -->
                        <a href="admin/index.php" class="<?php echo getMobileNavLinkClasses('index.php', 'admin'); ?> bg-red-600 hover:bg-red-700">
                            Admin Panel
                        </a>
                    <?php endif; ?>
                    
                    <!-- User info -->
                    <div class="px-3 py-2 text-blue-100 text-sm border-t border-blue-600 mt-2 pt-2">
                        Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>
                    </div>
                    
                    <a href="auth/logout.php" class="<?php echo getMobileNavLinkClasses('logout.php', 'auth'); ?>">
                        Logout
                    </a>
                <?php else: ?>
                    <!-- Guest user mobile navigation -->
                    <a href="auth/login.php" class="<?php echo getMobileNavLinkClasses('login.php', 'auth'); ?>">
                        Login
                    </a>
                    <a href="auth/register.php" class="<?php echo getMobileNavLinkClasses('register.php', 'auth'); ?>">
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main content container -->
    <main class="flex-1">