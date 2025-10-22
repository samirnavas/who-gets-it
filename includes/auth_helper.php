<?php
/**
 * Authentication Helper Functions
 * Provides utility functions for authentication checks
 */

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null Username if logged in, null otherwise
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Require authentication - redirect to login if not logged in
 * @param string $redirect_url URL to redirect to after login
 */
function requireAuth($redirect_url = '') {
    if (!isLoggedIn()) {
        $login_url = '/auth/login.php';
        if (!empty($redirect_url)) {
            $login_url .= '?redirect=' . urlencode($redirect_url);
        }
        header('Location: ' . $login_url);
        exit();
    }
}

/**
 * Redirect if already logged in
 * @param string $redirect_url URL to redirect to if logged in
 */
function redirectIfLoggedIn($redirect_url = '/index.php') {
    if (isLoggedIn()) {
        header('Location: ' . $redirect_url);
        exit();
    }
}
?>