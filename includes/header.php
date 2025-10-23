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
    $baseClasses = "flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:bg-blue-700 hover:bg-opacity-80 hover:shadow-md transform hover:scale-105";
    if (isActiveNav($page, $dir)) {
        return $baseClasses . " bg-blue-700 bg-opacity-90 text-white shadow-lg";
    }
    return $baseClasses . " text-blue-100 hover:text-white";
}

// Helper function to get mobile nav link classes
function getMobileNavLinkClasses($page, $dir = '') {
    $baseClasses = "block px-4 py-3 text-base font-medium transition-all duration-200 hover:bg-blue-700 hover:bg-opacity-50";
    if (isActiveNav($page, $dir)) {
        return $baseClasses . " bg-blue-700 bg-opacity-70 text-white";
    }
    return $baseClasses . " text-blue-100 hover:text-white";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'College Auction - Discover amazing deals on unique items through our secure bidding platform'; ?>">
    <meta name="theme-color" content="#3B82F6">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>College Auction</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/responsive-foundation.css" as="style">
    
    <!-- CSS Framework and Custom Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/responsive-foundation.css">
    <link rel="stylesheet" href="assets/css/auction-cards.css">
    <link rel="stylesheet" href="assets/css/responsive-forms.css">
    <link rel="stylesheet" href="assets/css/compact-forms.css">
    
    <!-- Tailwind CSS Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a'
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', '-apple-system', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <script>
        // Enhanced mobile menu toggle functionality with slide-out animation
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
            const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
            const menuButton = document.querySelector('[aria-controls="mobile-menu"]');
            const hamburgerIcon = document.getElementById('hamburger-icon');
            const closeIcon = document.getElementById('close-icon');
            const isOpen = !mobileMenu.classList.contains('hidden');
            
            if (!isOpen) {
                // Open menu
                mobileMenu.classList.remove('hidden');
                mobileMenu.classList.add('flex');
                
                // Trigger animations after DOM update
                requestAnimationFrame(() => {
                    mobileMenuOverlay.classList.remove('opacity-0');
                    mobileMenuOverlay.classList.add('opacity-100');
                    mobileMenuDrawer.classList.remove('-translate-x-full');
                    mobileMenuDrawer.classList.add('translate-x-0');
                });
                
                // Update button state
                menuButton.setAttribute('aria-expanded', 'true');
                hamburgerIcon.classList.add('hidden');
                closeIcon.classList.remove('hidden');
                
                // Prevent body scroll
                document.body.style.overflow = 'hidden';
                
                // Focus first menu item for accessibility
                setTimeout(() => {
                    const firstMenuItem = mobileMenuDrawer.querySelector('a');
                    if (firstMenuItem) firstMenuItem.focus();
                }, 300);
                
            } else {
                // Close menu
                mobileMenuOverlay.classList.remove('opacity-100');
                mobileMenuOverlay.classList.add('opacity-0');
                mobileMenuDrawer.classList.remove('translate-x-0');
                mobileMenuDrawer.classList.add('-translate-x-full');
                
                // Update button state
                menuButton.setAttribute('aria-expanded', 'false');
                hamburgerIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
                
                // Restore body scroll
                document.body.style.overflow = '';
                
                // Hide menu after animation
                setTimeout(() => {
                    mobileMenu.classList.remove('flex');
                    mobileMenu.classList.add('hidden');
                }, 300);
            }
        }
        
        // Close mobile menu function
        function closeMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            if (!mobileMenu.classList.contains('hidden')) {
                toggleMobileMenu();
            }
        }
        
        // Enhanced mobile menu event handlers
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
            
            // Close menu when clicking overlay
            if (mobileMenuOverlay) {
                mobileMenuOverlay.addEventListener('click', function(event) {
                    if (event.target === mobileMenuOverlay) {
                        closeMobileMenu();
                    }
                });
            }
            
            // Handle escape key to close mobile menu
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const mobileMenu = document.getElementById('mobile-menu');
                    const menuButton = document.querySelector('[aria-controls="mobile-menu"]');
                    
                    if (!mobileMenu.classList.contains('hidden')) {
                        closeMobileMenu();
                        menuButton.focus();
                    }
                }
            });
            
            // Handle keyboard navigation within mobile menu
            const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
            if (mobileMenuDrawer) {
                mobileMenuDrawer.addEventListener('keydown', function(event) {
                    const focusableElements = mobileMenuDrawer.querySelectorAll('a, button');
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];
                    
                    if (event.key === 'Tab') {
                        if (event.shiftKey) {
                            // Shift + Tab
                            if (document.activeElement === firstElement) {
                                event.preventDefault();
                                lastElement.focus();
                            }
                        } else {
                            // Tab
                            if (document.activeElement === lastElement) {
                                event.preventDefault();
                                firstElement.focus();
                            }
                        }
                    }
                });
            }
        });
        
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
<body class="bg-gray-100 min-h-screen font-sans antialiased">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary-600 text-white px-4 py-2 rounded-md z-50 transition-all">
        Skip to main content
    </a>
    
    <!-- Navigation Header -->
    <header role="banner">
        <nav class="bg-blue-600 shadow-lg" role="navigation" aria-label="Main navigation">
            <div class="container-responsive">
                <div class="flex justify-between h-16">
                    <!-- Logo and Brand -->
                    <div class="flex items-center">
                        <a href="index.php" class="flex-shrink-0 flex items-center touch-target" aria-label="College Auction Home">
                            <span class="text-white text-xl font-bold">College Auction</span>
                        </a>
                    </div>

                <!-- Enhanced Desktop Navigation -->
                <nav class="hidden md:flex md:items-center md:space-x-2 lg:space-x-4" role="navigation" aria-label="Desktop navigation">
                    <!-- Always visible links -->
                    <a href="index.php" class="<?php echo getNavLinkClasses('index.php'); ?> touch-target nav-link-enhanced" aria-current="<?php echo isActiveNav('index.php') ? 'page' : 'false'; ?>">
                        <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Home</span>
                    </a>

                    <?php if (isLoggedIn()): ?>
                        <!-- Logged-in user navigation with enhanced styling -->
                        <a href="create_item.php" class="<?php echo getNavLinkClasses('create_item.php'); ?> touch-target nav-link-enhanced" aria-current="<?php echo isActiveNav('create_item.php') ? 'page' : 'false'; ?>">
                            <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <span class="hidden md:inline">List New Item</span>
                            <span class="md:hidden">List</span>
                        </a>
                        
                        <a href="my_bids.php" class="<?php echo getNavLinkClasses('my_bids.php'); ?> touch-target nav-link-enhanced" aria-current="<?php echo isActiveNav('my_bids.php') ? 'page' : 'false'; ?>">
                            <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="hidden md:inline">My Bids</span>
                            <span class="md:hidden">Bids</span>
                        </a>
                        
                        <!-- Enhanced Notifications with improved badge -->
                        <a href="notifications.php" class="<?php echo getNavLinkClasses('notifications.php'); ?> relative touch-target nav-link-enhanced group" aria-current="<?php echo isActiveNav('notifications.php') ? 'page' : 'false'; ?>">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19H6a2 2 0 01-2-2V7a2 2 0 012-2h5m5 0v5" />
                                </svg>
                                <span class="hidden lg:inline">Notifications</span>
                                <span class="lg:hidden">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19H6a2 2 0 01-2-2V7a2 2 0 012-2h5m5 0v5" />
                                    </svg>
                                </span>
                            </div>
                            <?php 
                            $unread_count = getUnreadNotificationCount(getCurrentUserId());
                            ?>
                            <span class="notification-badge absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-semibold shadow-lg transform transition-transform group-hover:scale-110" 
                                  style="display: <?php echo $unread_count > 0 ? 'flex' : 'none'; ?>"
                                  aria-label="<?php echo $unread_count; ?> unread notifications">
                                <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                            </span>
                        </a>
                        
                        <?php if (isAdmin()): ?>
                            <!-- Enhanced Admin navigation -->
                            <a href="admin/index.php" class="<?php echo getNavLinkClasses('index.php', 'admin'); ?> bg-red-600 hover:bg-red-700 focus:bg-red-700 touch-target nav-link-enhanced admin-link" aria-current="<?php echo isActiveNav('index.php', 'admin') ? 'page' : 'false'; ?>">
                                <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="hidden lg:inline">Admin Panel</span>
                                <span class="lg:hidden">Admin</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Enhanced User dropdown -->
                        <div class="relative ml-4 lg:ml-6">
                            <div class="flex items-center space-x-2 lg:space-x-4">
                                <!-- User greeting (hidden on smaller screens) -->
                                <span class="hidden xl:block text-blue-100 text-sm font-medium" aria-label="Current user">
                                    Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>
                                </span>
                                
                                <!-- User avatar/icon -->
                                <div class="hidden lg:flex items-center justify-center h-8 w-8 rounded-full bg-blue-500 text-white text-sm font-semibold">
                                    <?php echo strtoupper(substr(getCurrentUsername(), 0, 1)); ?>
                                </div>
                                
                                <!-- Logout button -->
                                <a href="auth/logout.php" class="<?php echo getNavLinkClasses('logout.php', 'auth'); ?> touch-target nav-link-enhanced logout-link">
                                    <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span class="hidden md:inline">Logout</span>
                                    <span class="md:hidden">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Enhanced Guest user navigation -->
                        <a href="auth/login.php" class="<?php echo getNavLinkClasses('login.php', 'auth'); ?> touch-target nav-link-enhanced" aria-current="<?php echo isActiveNav('login.php', 'auth') ? 'page' : 'false'; ?>">
                            <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>Login</span>
                        </a>
                        
                        <a href="auth/register.php" class="<?php echo getNavLinkClasses('register.php', 'auth'); ?> touch-target nav-link-enhanced register-link" aria-current="<?php echo isActiveNav('register.php', 'auth') ? 'page' : 'false'; ?>">
                            <svg class="h-4 w-4 mr-2 hidden lg:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span>Register</span>
                        </a>
                    <?php endif; ?>
                </nav>

                <!-- Enhanced Mobile menu button with proper touch targets -->
                <div class="md:hidden flex items-center">
                    <button 
                        onclick="toggleMobileMenu()" 
                        class="relative text-blue-100 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 rounded-md p-2 touch-target-comfortable transition-all duration-200"
                        aria-label="Toggle mobile menu"
                        aria-expanded="false"
                        aria-controls="mobile-menu"
                        style="min-width: 48px; min-height: 48px;"
                    >
                        <!-- Hamburger Icon -->
                        <svg id="hamburger-icon" class="h-6 w-6 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        
                        <!-- Close Icon (hidden by default) -->
                        <svg id="close-icon" class="h-6 w-6 hidden transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                </div>
            </div>

            <!-- Enhanced Mobile Navigation Menu - Slide-out Drawer -->
            <div id="mobile-menu" class="hidden md:hidden fixed inset-0 z-50" role="navigation" aria-label="Mobile navigation">
                <!-- Overlay -->
                <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 opacity-0 transition-opacity duration-300"></div>
                
                <!-- Slide-out Drawer -->
                <div id="mobile-menu-drawer" class="fixed top-0 left-0 h-full w-80 max-w-sm bg-gradient-to-b from-blue-600 to-blue-800 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between p-4 border-b border-blue-500">
                        <div class="flex items-center">
                            <span class="text-white text-lg font-bold">College Auction</span>
                        </div>
                        <button 
                            onclick="closeMobileMenu()" 
                            class="text-blue-100 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 rounded-md p-2 touch-target-comfortable"
                            aria-label="Close mobile menu"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Navigation Links -->
                    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                        <!-- Always visible links -->
                        <a href="index.php" 
                           class="<?php echo getMobileNavLinkClasses('index.php'); ?> flex items-center space-x-3 touch-target-comfortable rounded-lg" 
                           aria-current="<?php echo isActiveNav('index.php') ? 'page' : 'false'; ?>"
                           onclick="closeMobileMenu()">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Home</span>
                        </a>

                        <?php if (isLoggedIn()): ?>
                            <!-- Logged-in user mobile navigation -->
                            <a href="create_item.php" 
                               class="<?php echo getMobileNavLinkClasses('create_item.php'); ?> flex items-center space-x-3 touch-target-comfortable rounded-lg" 
                               aria-current="<?php echo isActiveNav('create_item.php') ? 'page' : 'false'; ?>"
                               onclick="closeMobileMenu()">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <span>List New Item</span>
                            </a>
                            
                            <a href="my_bids.php" 
                               class="<?php echo getMobileNavLinkClasses('my_bids.php'); ?> flex items-center space-x-3 touch-target-comfortable rounded-lg" 
                               aria-current="<?php echo isActiveNav('my_bids.php') ? 'page' : 'false'; ?>"
                               onclick="closeMobileMenu()">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>My Bids</span>
                            </a>
                            
                            <!-- Mobile Notifications with enhanced styling -->
                            <a href="notifications.php" 
                               class="<?php echo getMobileNavLinkClasses('notifications.php'); ?> flex items-center justify-between touch-target-comfortable rounded-lg" 
                               aria-current="<?php echo isActiveNav('notifications.php') ? 'page' : 'false'; ?>"
                               onclick="closeMobileMenu()">
                                <div class="flex items-center space-x-3">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19H6a2 2 0 01-2-2V7a2 2 0 012-2h5m5 0v5" />
                                    </svg>
                                    <span>Notifications</span>
                                </div>
                                <?php 
                                $unread_count = getUnreadNotificationCount(getCurrentUserId());
                                ?>
                                <span class="notification-badge bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-medium" 
                                      style="display: <?php echo $unread_count > 0 ? 'flex' : 'none'; ?>"
                                      aria-label="<?php echo $unread_count; ?> unread notifications">
                                    <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                                </span>
                            </a>
                            
                            <?php if (isAdmin()): ?>
                                <!-- Admin mobile navigation with distinct styling -->
                                <a href="admin/index.php" 
                                   class="<?php echo getMobileNavLinkClasses('index.php', 'admin'); ?> bg-red-600 hover:bg-red-700 flex items-center space-x-3 touch-target-comfortable rounded-lg" 
                                   aria-current="<?php echo isActiveNav('index.php', 'admin') ? 'page' : 'false'; ?>"
                                   onclick="closeMobileMenu()">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Admin Panel</span>
                                </a>
                            <?php endif; ?>
                            
                            <!-- User info section -->
                            <div class="border-t border-blue-500 mt-6 pt-6">
                                <div class="flex items-center space-x-3 px-3 py-2 text-blue-100 text-sm" role="text" aria-label="Current user">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                                </div>
                                
                                <a href="auth/logout.php" 
                                   class="<?php echo getMobileNavLinkClasses('logout.php', 'auth'); ?> flex items-center space-x-3 touch-target-comfortable rounded-lg mt-2"
                                   onclick="closeMobileMenu()">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Logout</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Guest user mobile navigation -->
                            <a href="auth/login.php" 
                               class="<?php echo getMobileNavLinkClasses('login.php', 'auth'); ?> flex items-center space-x-3 touch-target-comfortable rounded-lg" 
                               aria-current="<?php echo isActiveNav('login.php', 'auth') ? 'page' : 'false'; ?>"
                               onclick="closeMobileMenu()">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                <span>Login</span>
                            </a>
                            
                            <a href="auth/register.php" 
                               class="<?php echo getMobileNavLinkClasses('register.php', 'auth'); ?> flex items-center space-x-3 touch-target-comfortable rounded-lg" 
                               aria-current="<?php echo isActiveNav('register.php', 'auth') ? 'page' : 'false'; ?>"
                               onclick="closeMobileMenu()">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>Register</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main content container -->
    <main id="main-content" class="flex-1" role="main">